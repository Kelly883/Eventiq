/* Verify SessionModal + ToastContainer geometry across breakpoints.
 *
 * Usage:  npm run build && npm run check:session-modal
 *
 * Serves frontend/dist on an ephemeral port, opens it in headless Firefox
 * (Chrome's renderer cannot execute in this sandbox), dispatches the real
 * 'session-extended' window event — the same one LoginPage fires on
 * successful login — and asserts modal/toast geometry at every required
 * breakpoint (320–1440px).
 *
 * Console noise that is expected in a static preview with no API backend
 * (CORS blocks, auth-restore failure) is filtered from the error check.
 */
import { firefox } from 'playwright';
import { createServer } from 'node:http';
import { readFileSync, existsSync } from 'node:fs';
import { join, extname } from 'node:path';

const DIST = join(process.cwd(), 'dist');
if (!existsSync(join(DIST, 'index.html'))) {
  console.error('dist/ not found — run `npm run build` first.');
  process.exit(2);
}

const MIME = {
  '.html': 'text/html', '.js': 'text/javascript', '.css': 'text/css',
  '.svg': 'image/svg+xml', '.png': 'image/png', '.json': 'application/json',
};

const server = createServer((req, res) => {
  let p = req.url.split('?')[0];
  if (p === '/') p = '/index.html';
  const file = join(DIST, p);
  if (existsSync(file)) {
    res.writeHead(200, { 'content-type': MIME[extname(file)] || 'application/octet-stream' });
    res.end(readFileSync(file));
  } else {
    res.writeHead(404);
    res.end('not found');
  }
});
await new Promise((r) => server.listen(0, r)); // ephemeral port — no EADDRINUSE
const PORT = server.address().port;

const WIDTHS = [320, 360, 375, 390, 414, 768, 1024, 1280, 1440];
const BENIGN = ['Cross-Origin Request Blocked', 'Unable to restore the current session'];
let failures = 0;

const browser = await firefox.launch();

for (const width of WIDTHS) {
  const page = await browser.newPage({ viewport: { width, height: 800 } });
  const errors = [];
  page.on('pageerror', (e) => errors.push(String(e).slice(0, 120)));
  page.on('console', (m) => { if (m.type() === 'error') errors.push(m.text().slice(0, 120)); });

  await page.goto(`http://localhost:${PORT}/`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  // The session-extended listener only exists after App.jsx mounts.
  await page.waitForSelector('#root > *', { timeout: 30000 });
  await page.waitForTimeout(600);

  await page.evaluate(() => window.dispatchEvent(new CustomEvent('session-extended')));
  await page.waitForSelector('.session-modal', { timeout: 5000 });

  // Below 375px the modal intentionally scales the icon down to 40px
  // (see the max-width: 374px rule in SessionModal.css).
  const expectedIcon = width < 375 ? 40 : 48;

  const m = await page.evaluate(() => {
    const q = (s) => document.querySelector(s);
    const r = (el) => (el ? el.getBoundingClientRect() : null);
    const modal = q('.session-modal');
    const backdrop = q('.session-modal-backdrop');
    const iconSvg = q('.session-modal-icon svg');
    const close = q('.session-modal-close');
    const closeSvg = q('.session-modal-close svg');
    const rm = r(modal);
    const ris = r(iconSvg);
    const rc = r(close);
    const rcs = r(closeSvg);
    return {
      modal: rm && { w: rm.width, h: rm.height, left: rm.left, right: rm.right },
      backdropZ: getComputedStyle(backdrop).zIndex,
      iconW: ris?.width,
      iconH: ris?.height,
      closeW: rc?.width,
      closeH: rc?.height,
      closeBg: getComputedStyle(close).backgroundColor,
      closeSvgW: rcs?.width,
      title: q('.session-modal-title')?.textContent,
      hOverflow: document.documentElement.scrollWidth > document.documentElement.clientWidth,
      vw: document.documentElement.clientWidth,
    };
  });

  const near = (v, target, tol) => Math.abs(v - target) <= tol;
  const relevant = errors.filter((e) => !BENIGN.some((b) => e.includes(b)));
  const checks = {
    'modal exists + title': Boolean(m.modal) && m.title === 'Session Extended',
    'fits viewport, no h-overflow':
      m.modal.left >= -0.5 && m.modal.right <= m.vw + 0.5 && !m.hOverflow,
    'compact (<240px tall)': m.modal.h < 240,
    [`icon ~${expectedIcon}px`]: near(m.iconW, expectedIcon, 3) && near(m.iconH, expectedIcon, 3),
    'icon never enormous (<=56px)': m.iconW <= 56,
    'close ~32px': near(m.closeW, 32, 3) && near(m.closeH, 32, 3),
    'close svg ~18px': near(m.closeSvgW, 18, 2),
    'close not browser-default': m.closeBg === 'rgba(0, 0, 0, 0)',
    'backdrop z 1000': m.backdropZ === '1000',
    'no unexpected page errors': relevant.length === 0,
  };
  const failed = Object.entries(checks).filter(([, ok]) => !ok).map(([n]) => n);
  if (failed.length) failures++;
  console.log(
    `[${failed.length ? 'FAIL' : 'PASS'}] ${width}px modal=${Math.round(m.modal.w)}x${Math.round(m.modal.h)} icon=${m.iconW.toFixed(1)} close=${m.closeW.toFixed(1)}` +
      (failed.length ? ` -> ${failed.join(', ')}` : '') +
      (relevant.length ? ` errors: ${JSON.stringify(relevant.slice(0, 2))}` : '')
  );
  await page.close();
}

// Toast container CSS check (mount real DOM, measure) at mobile + desktop
for (const width of [320, 1280]) {
  const page = await browser.newPage({ viewport: { width, height: 800 } });
  await page.goto(`http://localhost:${PORT}/`, { waitUntil: 'domcontentloaded', timeout: 30000 });
  await page.waitForSelector('#root > *', { timeout: 30000 });
  await page.waitForTimeout(600);

  const t = await page.evaluate(() => {
    const el = document.createElement('div');
    el.id = 'global-toast-container';
    el.innerHTML =
      '<div class="toast-item"><div class="toast-row">' +
      '<div class="toast-icon"><svg viewBox="0 0 24 24" width="20" height="20"></svg></div>' +
      '<div class="toast-body"><p class="toast-title">Session Extended</p>' +
      '<p class="toast-description">Your session will remain active.</p></div>' +
      '<button class="toast-close"><svg class="toast-close-icon" viewBox="0 0 24 24"></svg></button>' +
      '</div></div>';
    document.body.appendChild(el);
    const cs = getComputedStyle(el);
    const ri = el.querySelector('.toast-icon').getBoundingClientRect();
    const rc = el.querySelector('.toast-close').getBoundingClientRect();
    const out = {
      pos: cs.position,
      z: cs.zIndex,
      bottom: cs.bottom,
      iconW: Math.round(ri.width),
      closeW: Math.round(rc.width),
      closeH: Math.round(rc.height),
    };
    el.remove();
    return out;
  });

  // Mobile: bottom offset 88px clears the fixed bottom nav; desktop: 24px.
  const expectedBottom = width < 640 ? '88px' : '24px';
  const checks = {
    'container fixed': t.pos === 'fixed',
    'z-index 200': t.z === '200',
    'bottom clears bottom nav': t.bottom === expectedBottom,
    'icon 20px': t.iconW === 20,
    'close 28px': t.closeW === 28 && t.closeH === 28,
  };
  const failed = Object.entries(checks).filter(([, ok]) => !ok).map(([n]) => n);
  if (failed.length) failures++;
  console.log(
    `[${failed.length ? 'FAIL' : 'PASS'}] ${width}px toast pos=${t.pos} z=${t.z} bottom=${t.bottom} icon=${t.iconW} close=${t.closeW}` +
      (failed.length ? ` -> ${failed.join(', ')}` : '')
  );
  await page.close();
}

await browser.close();
server.close();
console.log(failures === 0 ? '\nALL CHECKS PASSED' : `\n${failures} viewport group(s) FAILED`);
process.exit(failures === 0 ? 0 : 1);
