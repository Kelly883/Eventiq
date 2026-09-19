const { chromium } = require('playwright');

(async () => {
  const browser = await chromium.launch({ headless: true });
  const page = await browser.newPage();
  const errors = [];
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  page.on('pageerror', err => errors.push('PAGEERROR: ' + err.message));

  await page.goto('http://localhost:3000/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'organizer@test.test');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  const token = await page.evaluate(() => sessionStorage.getItem('auth_token'));
  console.log('Token after login:', token ? token.substring(0, 20) + '...' : 'NONE');

  const url = page.url();
  console.log('URL after login:', url);

  await page.goto('http://localhost:3000/dashboard', { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);

  const token2 = await page.evaluate(() => sessionStorage.getItem('auth_token'));
  console.log('Token on dashboard:', token2 ? token2.substring(0, 20) + '...' : 'NONE');

  const bodyText = await page.evaluate(() => document.body.innerText.substring(0, 200));
  console.log('Body text:', bodyText);

  console.log('ERRORS:', errors.length ? errors.join('\n') : 'none');

  await page.screenshot({ path: '/tmp/dashboard-debug.png', fullPage: true });
  await browser.close();
})();
