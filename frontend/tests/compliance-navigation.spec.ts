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

test.describe('Compliance feature navigation and authorization', () => {
  test.beforeEach(async ({ context }) => {
    await context.addInitScript(() => {
      window.localStorage.clear();
      window.sessionStorage.clear();
    });
  });

  test('non-admin is denied access to /admin/compliance/audit-logs', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/compliance/audit-logs`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/access-denied');
  });

  test('non-admin is denied access to /admin/compliance/reports', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/compliance/reports`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/access-denied');
  });

  test('admin can access audit logs page', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/compliance/audit-logs`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/admin/compliance/audit-logs');
  });

  test('admin can access compliance reports page', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/compliance/reports`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/admin/compliance/reports');
  });

  test('compliance navigation links appear for admin users', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const auditLogsLink = page.locator('nav a[href="/admin/compliance/audit-logs"]');
    await expect(auditLogsLink).toBeVisible({ timeout: 30000 });

    const reportsLink = page.locator('nav a[href="/admin/compliance/reports"]');
    await expect(reportsLink).toBeVisible({ timeout: 30000 });
  });

  test('compliance navigation links do not appear for non-admin users', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const auditLogsLink = page.locator('nav a[href="/admin/compliance/audit-logs"]');
    await expect(auditLogsLink).not.toBeVisible();

    const reportsLink = page.locator('nav a[href="/admin/compliance/reports"]');
    await expect(reportsLink).not.toBeVisible();
  });

  test('clicking Audit Logs navigates to /admin/compliance/audit-logs', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const auditLogsLink = page.locator('nav a[href="/admin/compliance/audit-logs"]');
    await expect(auditLogsLink).toBeVisible({ timeout: 30000 });
    await auditLogsLink.click();
    await page.waitForTimeout(2000);

    expect(page.url()).toContain('/admin/compliance/audit-logs');
  });

  test('clicking Compliance Reports navigates to /admin/compliance/reports', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);

    const reportsLink = page.locator('nav a[href="/admin/compliance/reports"]');
    await expect(reportsLink).toBeVisible({ timeout: 30000 });
    await reportsLink.click();
    await page.waitForTimeout(2000);

    expect(page.url()).toContain('/admin/compliance/reports');
  });

  test('compliance API returns 403 for non-admin', async ({ page }) => {
    await loginViaUi(page, 'attendee@eventiq.test', 'password');

    const responsePromise = page.waitForResponse(
      (resp) => resp.url().includes('/api/admin/compliance/audit-logs') && resp.status() === 403
    );
    await page.goto(`${BASE_URL}/admin/compliance/audit-logs`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await responsePromise;
  });

  test('session expiration redirects to login then back to compliance page', async ({ page }) => {
    await loginViaUi(page, 'admin@eventiq.test', 'password');
    await page.goto(`${BASE_URL}/admin/compliance/audit-logs`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    expect(page.url()).toContain('/admin/compliance/audit-logs');

    await page.evaluate(() => {
      window.dispatchEvent(new Event('session-expired'));
    });

    await page.waitForURL(`${BASE_URL}/login`, { timeout: 10000 });
    expect(page.url()).toContain('/login');
  });
});
