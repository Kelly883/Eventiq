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

test.describe('Settings pages navigation and authentication', () => {
  test.beforeEach(async ({ context }) => {
    await context.addInitScript(() => {
      window.localStorage.clear();
      window.sessionStorage.clear();
    });
  });

  test('settings routes exist and load pages for authenticated users', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');

    await page.goto(`${BASE_URL}/settings/accessibility`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/accessibility');

    await page.goto(`${BASE_URL}/settings/language`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/language');

    await page.goto(`${BASE_URL}/settings/device-localization`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/device-localization');
  });

  test('settings routes redirect to login when not authenticated', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings/accessibility`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');

    await page.goto(`${BASE_URL}/settings/language`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');

    await page.goto(`${BASE_URL}/settings/device-localization`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');
  });

  test('settings menu contains accessibility, language, and device sync links', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/settings`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const accessibilityLink = page.locator('nav a[href="/settings/accessibility"]');
    await expect(accessibilityLink).toBeVisible({ timeout: 30000 });

    const languageLink = page.locator('nav a[href="/settings/language"]');
    await expect(languageLink).toBeVisible({ timeout: 30000 });

    const deviceSyncLink = page.locator('nav a[href="/settings/device-localization"]');
    await expect(deviceSyncLink).toBeVisible({ timeout: 30000 });
  });

  test('clicking settings menu links navigates to the correct page', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/settings`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    await page.click('nav a[href="/settings/accessibility"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/accessibility');

    await page.click('nav a[href="/settings/language"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/language');

    await page.click('nav a[href="/settings/device-localization"]');
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/settings/device-localization');
  });

  test('deep linking to settings/accessibility preserves destination through login', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings/accessibility`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');

    await page.waitForSelector('#login-email', { timeout: 60000 });
    await page.fill('#login-email', 'attendee@eventiq.test');
    await page.fill('#login-password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    expect(page.url()).toContain('/settings/accessibility');
  });

  test('deep linking to settings/language preserves destination through login', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings/language`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');

    await page.waitForSelector('#login-email', { timeout: 60000 });
    await page.fill('#login-email', 'attendee@eventiq.test');
    await page.fill('#login-password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    expect(page.url()).toContain('/settings/language');
  });

  test('deep linking to settings/device-localization preserves destination through login', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings/device-localization`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/login');

    await page.waitForSelector('#login-email', { timeout: 60000 });
    await page.fill('#login-email', 'attendee@eventiq.test');
    await page.fill('#login-password', 'password');
    await page.click('button[type="submit"]');
    await page.waitForTimeout(3000);

    expect(page.url()).toContain('/settings/device-localization');
  });
});
