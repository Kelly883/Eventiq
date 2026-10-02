import { test, expect } from '@playwright/test';

const BASE_URL = 'http://localhost:3000';

test.describe('Auth Reusable Components', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto(`${BASE_URL}/auth-components`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
  });

  test('renders AuthCard centered with title', async ({ page }) => {
    const card = page.locator('.auth-card');
    await expect(card).toBeVisible();
    await expect(card.locator('.auth-card__title')).toHaveText('Component Test Page');
  });

  test('FormField shows error state when error prop is provided', async ({ page }) => {
    const input = page.locator('#test-form-field');
    const error = page.locator('#test-form-field-error');

    await input.fill('ab');
    await expect(input).toHaveClass(/auth-field__input--error/);
    await expect(error).toBeVisible();
    await expect(error).toHaveText('Too short');

    await input.fill('abc');
    await expect(input).not.toHaveClass(/auth-field__input--error/);
    await expect(error).not.toBeVisible();
  });

  test('PasswordVisibilityToggle toggles password visibility', async ({ page }) => {
    const passwordInput = page.locator('#test-password');
    const toggle = page.locator('.auth-password-toggle');

    await passwordInput.fill('secret123');
    await expect(passwordInput).toHaveAttribute('type', 'password');

    await toggle.click();
    await expect(passwordInput).toHaveAttribute('type', 'text');

    await toggle.click();
    await expect(passwordInput).toHaveAttribute('type', 'password');
  });

  test('PasswordStrengthMeter updates in real-time', async ({ page }) => {
    const passwordInput = page.locator('#test-password');
    const meterLabel = page.locator('.auth-strength__label');

    await passwordInput.fill('weak');
    await expect(meterLabel).toHaveText('Weak');

    await passwordInput.fill('medium1');
    await expect(meterLabel).toHaveText('Medium');

    await passwordInput.fill('Strong1');
    await expect(meterLabel).toHaveText('Strong');
  });

  test('SubmitButton shows spinner when loading', async ({ page }) => {
    const submitBtn = page.locator('.auth-submit');
    await expect(submitBtn).toBeVisible();

    await submitBtn.click();
    await expect(page.locator('.auth-submit__spinner')).toBeVisible();
    await expect(submitBtn).toBeDisabled();

    await page.waitForTimeout(2500);
    await expect(page.locator('.auth-submit__spinner')).not.toBeVisible();
    await expect(submitBtn).not.toBeDisabled();
  });

  test('SubmitButton is disabled when disabled prop is toggled', async ({ page }) => {
    const submitBtn = page.locator('.auth-submit');
    const toggleBtn = page.locator('button:has-text("Disable Submit Button")');

    await expect(submitBtn).not.toBeDisabled();

    await toggleBtn.click();
    await expect(submitBtn).toBeDisabled();

    await toggleBtn.click();
    await expect(submitBtn).not.toBeDisabled();
  });

  test('all components are importable from the barrel', async ({ page }) => {
    await page.goto(`${BASE_URL}/auth-components`, { waitUntil: 'domcontentloaded', timeout: 60000 });
    await page.waitForTimeout(2000);
    await expect(page.locator('.auth-card')).toBeVisible();
    await expect(page.locator('.auth-field')).toBeVisible();
    await expect(page.locator('.auth-password-toggle')).toBeVisible();
    await expect(page.locator('.auth-strength')).toBeVisible();
    await expect(page.locator('.auth-submit')).toBeVisible();
  });
});
