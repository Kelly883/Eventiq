import { expect, test } from '@playwright/test';

const API_HEADERS = {
  'access-control-allow-origin': 'http://localhost:3000',
  'access-control-allow-credentials': 'true',
  'access-control-allow-methods': 'GET, POST, OPTIONS',
  'access-control-allow-headers': 'Authorization, Content-Type, X-XSRF-TOKEN, X-Device-Token',
};

const preflightResponse = (route: import('@playwright/test').Route) =>
  route.fulfill({ status: 204, headers: API_HEADERS });

test.describe('Admin bootstrap registration', () => {
  test.beforeEach(async ({ page }) => {
    await page.route('**/api/auth/me', (route) => {
      if (route.request().method() === 'OPTIONS') return preflightResponse(route);
      return route.fulfill({
        status: 401,
        contentType: 'application/json',
        headers: API_HEADERS,
        body: JSON.stringify({ message: 'Unauthenticated.' }),
      });
    });
  });

  test('redirects to login without briefly showing bootstrap form when setup is complete', async ({ page }) => {
    await page.addInitScript(() => {
      const state = window as Window & { adminBootstrapFormShown?: boolean };
      state.adminBootstrapFormShown = false;
      new MutationObserver((mutations) => {
        for (const mutation of mutations) {
          for (const node of mutation.addedNodes) {
            if (
              node instanceof Element &&
              (node.matches('[data-testid="admin-bootstrap-form"]') ||
                node.querySelector('[data-testid="admin-bootstrap-form"]'))
            ) {
              state.adminBootstrapFormShown = true;
            }
          }
        }
      }).observe(document, { childList: true, subtree: true });
    });
    await page.route('**/api/auth/admin-setup', (route) => {
      if (route.request().method() === 'OPTIONS') return preflightResponse(route);
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        headers: API_HEADERS,
        body: JSON.stringify({ required: false, admin_count: 1, max_admins: 1 }),
      });
    });

    await page.goto('/admin/register');
    await page.waitForURL('**/login');

    await expect(page.getByTestId('admin-bootstrap-form')).toHaveCount(0);
    await expect(page.locator('#global-toast-container')).toContainText('Admin Panel > Users');
    const formWasShown = await page.evaluate(
      () => (window as Window & { adminBootstrapFormShown?: boolean }).adminBootstrapFormShown,
    );
    expect(formWasShown).toBe(false);
  });

  test('shows the bootstrap form when no administrator exists', async ({ page }) => {
    await page.route('**/api/auth/admin-setup', (route) => {
      if (route.request().method() === 'OPTIONS') return preflightResponse(route);
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        headers: API_HEADERS,
        body: JSON.stringify({ required: true, admin_count: 0, max_admins: 1 }),
      });
    });

    await page.goto('/admin/register');

    await expect(page.getByTestId('admin-bootstrap-form')).toBeVisible();
  });

  test('keeps the bootstrap form hidden when setup status cannot be verified', async ({ page }) => {
    let statusAvailable = false;
    await page.route('**/api/auth/admin-setup', (route) => {
      if (route.request().method() === 'OPTIONS') return preflightResponse(route);
      if (!statusAvailable) {
        return route.abort('failed');
      }
      return route.fulfill({
        status: 200,
        contentType: 'application/json',
        headers: API_HEADERS,
        body: JSON.stringify({ required: true, admin_count: 0, max_admins: 1 }),
      });
    });

    await page.goto('/admin/register');
    await expect(page.getByRole('alert')).toHaveText('Unable to verify administrator setup. Please try again.');
    await expect(page.getByTestId('admin-bootstrap-form')).toHaveCount(0);

    statusAvailable = true;
    await page.getByRole('button', { name: 'Retry setup check' }).click();
    await expect(page.getByTestId('admin-bootstrap-form')).toBeVisible();
  });
});
