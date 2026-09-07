import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:3000';

test.describe('Settings Routes and Deep Linking', () => {
  test.beforeEach(async ({ page }) => {
    // Ensure clean session state before each test
    await page.context().clearCookies();
    await page.goto(BASE_URL);
  });

  test('logged-out /settings/accessibility redirects to /login', async ({ page }) => {
    const response = await page.goto(`${BASE_URL}/settings/accessibility`);
    expect(response?.status()).toBe(200); // SPA returns 200 for all routes

    await page.waitForURL('**/login');
    expect(page.url()).toContain('/login');
  });

  test('deep link: /settings/accessibility while logged out → login → redirect back', async ({ page }) => {
    await page.goto(`${BASE_URL}/settings/accessibility`);
    await page.waitForURL('**/login');

    // Verify the "from" state is preserved in URL (it's in history state, not URL)
    const loginUrl = page.url();
    expect(loginUrl).toContain('/login');

    // Fill login form
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');

    // Wait for redirect back to accessibility settings
    await page.waitForURL('**/settings/accessibility', { timeout: 15000 });
    expect(page.url()).toContain('/settings/accessibility');
  });

  test('settings menu contains links to all three pages', async ({ page }) => {
    // Login first
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    // Navigate to settings
    await page.click('text=⚙️ Settings');
    await page.waitForURL('**/settings');

    // Check sidebar links exist
    await expect(page.locator('text=Accessibility')).toBeVisible();
    await expect(page.locator('text=Language & Region')).toBeVisible();
    await expect(page.locator('text=Device Sync')).toBeVisible();
  });

  test('clicking Accessibility navigates to correct page', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    await page.click('text=⚙️ Settings');
    await page.waitForURL('**/settings');

    await page.click('text=Accessibility');
    await page.waitForURL('**/settings/accessibility');
    expect(page.url()).toContain('/settings/accessibility');
  });

  test('clicking Language & Region navigates to correct page', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    await page.click('text=⚙️ Settings');
    await page.waitForURL('**/settings');

    await page.click('text=Language & Region');
    await page.waitForURL('**/settings/language');
    expect(page.url()).toContain('/settings/language');
  });

  test('clicking Device Sync navigates to correct page', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    await page.click('text=⚙️ Settings');
    await page.waitForURL('**/settings');

    await page.click('text=Device Sync');
    await page.waitForURL('**/settings/device-localization');
    expect(page.url()).toContain('/settings/device-localization');
  });

  test('back button returns to settings menu from accessibility', async ({ page }) => {
    await page.goto(`${BASE_URL}/login`);
    await page.fill('input[name="email"]', 'admin@eventiq.test');
    await page.fill('input[name="password"]', 'password');
    await page.click('button[type="submit"]');
    await page.waitForURL('**/dashboard', { timeout: 15000 });

    await page.click('text=⚙️ Settings');
    await page.waitForURL('**/settings');

    await page.click('text=Accessibility');
    await page.waitForURL('**/settings/accessibility');

    await page.goBack();
    await page.waitForURL('**/settings');
    expect(page.url()).toContain('/settings');
  });

  test('deep link: /settings/language in new tab while logged out → login → language page', async ({ page, context }) => {
    // Open new tab with deep link
    const newPage = await context.newPage();
    await newPage.goto(`${BASE_URL}/settings/language`);
    await newPage.waitForURL('**/login');

    // Login in new tab
    await newPage.fill('input[name="email"]', 'admin@eventiq.test');
    await newPage.fill('input[name="password"]', 'password');
    await newPage.click('button[type="submit"]');

    await newPage.waitForURL('**/settings/language', { timeout: 15000 });
    expect(newPage.url()).toContain('/settings/language');

    await newPage.close();
  });

  test('all three settings routes require authentication', async ({ page }) => {
    const routes = [
      '/settings/accessibility',
      '/settings/language',
      '/settings/device-localization',
    ];

    for (const route of routes) {
      await page.goto(`${BASE_URL}${route}`);
      await page.waitForURL('**/login');
      expect(page.url()).toContain('/login');
    }
  });
});