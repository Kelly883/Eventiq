const { chromium } = require('playwright');
const fs = require('fs');

const BASE = 'http://localhost:3000';
const API = 'http://localhost:8000';

async function main() {
  const browser = await chromium.launch({ headless: true });
  const context = await browser.newContext({
    viewport: { width: 375, height: 812 },
    deviceScaleFactor: 2,
  });
  const page = await context.newPage();

  // Disable cache to ensure fresh code
  await page.route('**/*', route => route.continue({ headers: { ...route.request().headers(), 'cache-control': 'no-cache' } }));

  // Login via UI form (proper flow that stores token via AuthContext)
  await page.goto(BASE + '/login', { waitUntil: 'networkidle' });
  await page.fill('input[name="email"]', 'organizer@test.test');
  await page.fill('input[name="password"]', 'password123');
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);

  // Verify token was stored
  const token = await page.evaluate(() => sessionStorage.getItem('auth_token'));
  console.log('Token stored:', token ? token.substring(0, 20) + '...' : 'NONE');

  // Navigate to dashboard
  await page.goto(BASE + '/dashboard', { waitUntil: 'networkidle' });
  await page.waitForTimeout(3000);

  // Mobile screenshots
  await page.screenshot({ path: '/tmp/dashboard-mobile-375.png', fullPage: true });
  console.log('Mobile 375px screenshot saved');

  await page.setViewportSize({ width: 320, height: 568 });
  await page.waitForTimeout(1000);
  await page.screenshot({ path: '/tmp/dashboard-mobile-320.png', fullPage: true });
  console.log('Mobile 320px screenshot saved');

  await page.setViewportSize({ width: 430, height: 932 });
  await page.waitForTimeout(1000);
  await page.screenshot({ path: '/tmp/dashboard-mobile-430.png', fullPage: true });
  console.log('Mobile 430px screenshot saved');

  // Desktop screenshots
  await page.setViewportSize({ width: 1280, height: 800 });
  await page.waitForTimeout(1000);
  await page.screenshot({ path: '/tmp/dashboard-desktop-1280.png', fullPage: true });
  console.log('Desktop 1280px screenshot saved');

  await page.setViewportSize({ width: 1024, height: 768 });
  await page.waitForTimeout(1000);
  await page.screenshot({ path: '/tmp/dashboard-desktop-1024.png', fullPage: true });
  console.log('Desktop 1024px screenshot saved');

  // Check for console errors
  const errors = [];
  page.on('console', msg => { if (msg.type() === 'error') errors.push(msg.text()); });
  await page.waitForTimeout(500);

  await browser.close();
  console.log('Done. Errors:', errors.length ? errors : 'none');
}

main().catch(e => { console.error(e); process.exit(1); });
