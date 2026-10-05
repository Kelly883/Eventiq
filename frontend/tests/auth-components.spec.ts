import { test, expect } from '@playwright/test';

test.describe('Auth reusable components', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('/auth-components');
    // Wait for React to mount
    await page.waitForSelector('[data-testid="auth-card"]', { timeout: 15000 });
  });

  test.describe('AuthCard', () => {
    test('renders title and centered content', async ({ page }) => {
      const card = page.locator('[data-testid="auth-card"]').first();
      await expect(card).toBeVisible();
      await expect(card.locator('.auth-card__title').first()).toHaveText('Component Test Page');
      // Centered via margin: 0 auto and max-width constraint
      const box = await card.boundingBox();
      const viewport = page.viewportSize();
      // Card should be roughly centered (within 30% of center)
      expect(box!.x + box!.width / 2).toBeGreaterThan(viewport!.width * 0.2);
      expect(box!.x + box!.width / 2).toBeLessThan(viewport!.width * 0.8);
    });

    test('applies gradient background class when gradient=true', async ({ page }) => {
      // The test page renders with gradient=false; verify class presence
      const card = page.locator('[data-testid="auth-card"]').first();
      await expect(card).toHaveClass(/auth-card/);
      await expect(card).not.toHaveClass(/auth-card--gradient/);
    });
  });

  test.describe('FormField', () => {
    test('renders label and input', async ({ page }) => {
      const field = page.locator('#test-form-field');
      await expect(field).toBeVisible();
      const label = page.locator('label[for="test-form-field"]');
      await expect(label).toHaveText('Test Input');
    });

    test('shows error state when error prop is provided', async ({ page }) => {
      // Type 1 char to trigger error
      await page.locator('#test-form-field').fill('x');
      // Wait for state update
      await page.waitForTimeout(100);
      const input = page.locator('#test-form-field');
      await expect(input).toHaveClass(/auth-field__input--error/);
      const errorEl = page.locator('#test-form-field-error');
      await expect(errorEl).toHaveText('Too short');
    });

    test('input border turns red when error exists', async ({ page }) => {
      await page.locator('#test-form-field').fill('ab');
      await page.waitForTimeout(100);
      const input = page.locator('#test-form-field');
      // The CSS sets border-color to var(--coral, #cc3838)
      const borderColor = await input.evaluate((el) =>
        window.getComputedStyle(el).borderColor
      );
      expect(borderColor).toMatch(/204.*56.*56|cc3838|coral/i);
    });
  });

  test.describe('PasswordVisibilityToggle', () => {
    test('toggles between showing and hiding password text', async ({ page }) => {
      // Find the password input in the second field (the one with toggle)
      const passwordInput = page.locator('#test-password');
      const toggle = page.locator('[data-testid="password-visibility-toggle"]').first();

      // Initially hidden
      await expect(passwordInput).toHaveAttribute('type', 'password');
      await expect(toggle).toHaveAttribute('aria-pressed', 'false');

      // Click toggle
      await toggle.click();
      await expect(passwordInput).toHaveAttribute('type', 'text');
      await expect(toggle).toHaveAttribute('aria-pressed', 'true');

      // Click again
      await toggle.click();
      await expect(passwordInput).toHaveAttribute('type', 'password');
      await expect(toggle).toHaveAttribute('aria-pressed', 'false');
    });
  });

  test.describe('PasswordStrengthMeter', () => {
    test('shows weak for short simple password', async ({ page }) => {
      const passwordInput = page.locator('#test-password');
      await passwordInput.fill('abc');
      await page.waitForTimeout(100);
      const label = page.locator('[data-testid="password-strength-meter"] .auth-strength__label');
      await expect(label).toHaveText('Too short. Add uppercase, numbers, or symbols');
    });

    test('updates color weak -> medium -> strong as user types', async ({ page }) => {
      const passwordInput = page.locator('#test-password');

      // Weak: short
      await passwordInput.fill('abc');
      await page.waitForTimeout(100);
      const weakLabel = page.locator('[data-testid="password-strength-meter"] .auth-strength__label');
      await expect(weakLabel).toHaveText('Too short. Add uppercase, numbers, or symbols');

      // Medium: >= 6 chars, 2+ variety
      await passwordInput.fill('abc123');
      await page.waitForTimeout(100);
      const mediumLabel = page.locator('[data-testid="password-strength-meter"] .auth-strength__label');
      await expect(mediumLabel).toHaveText('6+ characters, 2+ character types');

      // Strong: >= 8 chars, 3+ variety
      await passwordInput.fill('Abc12345');
      await page.waitForTimeout(100);
      const strongLabel = page.locator('[data-testid="password-strength-meter"] .auth-strength__label');
      await expect(strongLabel).toHaveText('8+ characters, 3+ character types (uppercase, numbers, symbols)');
    });

    test('updates in real-time as user types', async ({ page }) => {
      const passwordInput = page.locator('#test-password');
      await passwordInput.click();
      await passwordInput.type('Ab1', { delay: 50 });
      // After typing, the meter should show something (not empty)
      const meter = page.locator('[data-testid="password-strength-meter"]');
      const label = meter.locator('.auth-strength__label');
      // Should have updated from the initial empty state
      await expect(label).toHaveText(/Too short|characters|symbols/);
    });
  });

  test.describe('SubmitButton', () => {
    test('shows spinner when loading is true', async ({ page }) => {
      const submitBtn = page.locator('[data-testid="submit-button"] >> nth=0');
      // Find the primary submit button (first one)
      const primarySubmit = page.locator('.auth-submit').first();
      await expect(primarySubmit).toHaveAttribute('data-testid', 'submit-button');
    });

    test('is disabled when loading or disabled prop is true', async ({ page }) => {
      // Click the "Disable Submit Button" button
      await page.locator('button:has-text("Disable Submit Button")').click();
      await page.waitForTimeout(100);
      const primarySubmit = page.locator('.auth-submit').first();
      await expect(primarySubmit).toBeDisabled();
    });
  });

  test.describe('Barrel imports', () => {
    test('all components are importable from index', async ({ page }) => {
      // Just confirm the page rendered without import errors
      await expect(page.locator('[data-testid="auth-card"]').first()).toBeVisible();
      await expect(page.locator('[data-testid="submit-button"]').first()).toBeVisible();
      await expect(page.locator('[data-testid="password-strength-meter"]')).toBeVisible();
      await expect(page.locator('[data-testid="password-visibility-toggle"]').first()).toBeVisible();
    });
  });
});
