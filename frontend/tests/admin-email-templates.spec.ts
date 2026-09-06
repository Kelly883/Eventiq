import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:3000';

async function loginViaUi(page, email, password) {
  await page.goto(`${BASE_URL}/login`, { waitUntil: 'domcontentloaded', timeout: 60000 });
  await page.waitForSelector('#login-email', { timeout: 60000 });
  await page.fill('#login-email', email);
  await page.fill('#login-password', password);
  await page.click('button[type="submit"]');
  await page.waitForTimeout(3000);
}

test.describe('Admin email template flow', () => {
  test.beforeEach(async ({ context }) => {
    await context.addInitScript(() => {
      window.localStorage.clear();
      window.sessionStorage.clear();
    });
  });

  test('non-admin is denied access to /admin/settings/email-templates', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/settings/email-templates`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/access-denied');
  });

  test('admin can access email templates through settings menu', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const emailLink = page.locator('nav a[href="/admin/settings/email-templates"]');
    await expect(emailLink).toBeVisible({ timeout: 30000 });

    await emailLink.click();
    await page.waitForTimeout(2000);

    expect(page.url()).toContain('/admin/settings/email-templates');
    await expect(page.locator('h1')).toContainText('Email Templates');
  });

  test('page fetches and displays the template list', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/settings/email-templates`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(3000);

    await expect(page.locator('h1')).toContainText('Email Templates');
    await expect(page.locator('text=Templates')).toBeVisible();
  });

  test('settings and push template routes behave correctly', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');

    await page.goto(`${BASE_URL}/settings/delivery-preferences`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    await expect(page.locator('h1')).toContainText('Delivery Preferences');

    await page.goto(`${BASE_URL}/admin/settings/push-templates`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/access-denied');

    await page.goto(`${BASE_URL}/settings/delivery-preferences`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    await expect(page.locator('h1')).toContainText('Delivery Preferences');
  });
});
