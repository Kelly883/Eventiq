import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:3000';

test('login completes and redirects', async ({ page }) => {
  page.on('console', msg => console.log('CONSOLE:', msg.type(), msg.text()));
  page.on('request', req => console.log('REQUEST:', req.method(), req.url()));
  page.on('response', res => console.log('RESPONSE:', res.status(), res.url()));

  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForTimeout(2000);
  await page.fill('#login-email', 'admin@eventiq.test');
  await page.fill('#login-password', 'password');
  await page.click('button[type="submit"]');

  await page.waitForURL(/\/dashboard|\/admin/, { timeout: 60000 });
  console.log('FINAL URL:', page.url());
  await page.screenshot({ path: 'test-results/after-login-redirect.png', fullPage: true });
});
