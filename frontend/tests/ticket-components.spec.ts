import { test, expect } from '@playwright/test';

test.describe.configure({ retries: 0, timeout: 60000 });

test.describe('Ticket Components', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://localhost:3000/ticket-components');
    await page.waitForLoadState('networkidle');
  });

  test('TicketTierCard displays all tier information', async ({ page }) => {
    const card1 = page.locator('[data-testid="ticket-tier-0"]');
    await expect(card1).toBeVisible();
    
    await expect(card1.locator('.ticket-tier-card__tier-name')).toContainText('Early Bird Special');
    await expect(card1.locator('#tier-price-0')).toHaveValue('50.00');
    await expect(card1.locator('#tier-quantity-0')).toHaveValue('100');
    await expect(card1.locator('#tier-start-0')).toHaveValue('2026-01-01');
    await expect(card1.locator('#tier-end-0')).toHaveValue('2026-12-31');
    await expect(card1.locator('.input-limits')).toContainText('Min: 1');
    await expect(card1.locator('.input-limits')).toContainText('Max: 4');
    await expect(card1.locator('.benefits-display__text')).toContainText('main stage sessions');
    await expect(card1.locator('.ticket-tier-card__image')).toHaveAttribute('src', /unsplash/);
  });

  test('Early bird badge appears only when early bird is active', async ({ page }) => {
    const card1 = page.locator('[data-testid="ticket-tier-0"]');
    await expect(card1.locator('.early-bird-badge')).toBeVisible();
    await expect(card1.locator('.early-bird-badge')).toContainText('Early Bird');
    
    const card2 = page.locator('[data-testid="ticket-tier-1"]');
    await expect(card2.locator('.early-bird-badge')).not.toBeVisible();
  });

  test('EarlyBirdBadge calculates and displays discount percentage', async ({ page }) => {
    const badgeItems = page.locator('.test-badge-item');
    await expect(badgeItems).toHaveCount(6);
    
    const badge1 = badgeItems.nth(0);
    await expect(badge1.locator('.early-bird-badge')).toContainText('30% Off');
    await expect(badge1.locator('.early-bird-badge__savings')).toContainText('Save $15.00');
    
    const badge2 = badgeItems.nth(1);
    await expect(badge2.locator('.early-bird-badge')).toContainText('25% Off');
    await expect(badge2.locator('.early-bird-badge__savings')).toContainText('Save $18.75');
    
    const badge3 = badgeItems.nth(2);
    await expect(badge3.locator('.early-bird-badge')).toContainText('50% Off');
    await expect(badge3.locator('.early-bird-badge__savings')).toContainText('Save $50.00');
    
    const badge4 = badgeItems.nth(3);
    await expect(badge4.locator('.early-bird-badge')).not.toBeVisible();
    
    const badge5 = badgeItems.nth(4);
    await expect(badge5.locator('.early-bird-badge')).not.toBeVisible();
    
    const badge6 = badgeItems.nth(5);
    await expect(badge6.locator('.early-bird-badge')).not.toBeVisible();
  });

  test('TicketTierForm validates all fields', async ({ page }) => {
    const form = page.locator('.ticket-tier-form');
    await expect(form).toBeVisible();
    
    const submitBtn = page.locator('.ticket-tier-form__submit');
    await expect(submitBtn).toBeDisabled();
    
    await expect(page.locator('[role="alert"]')).toBeVisible();
  });

  test('Form submission calls the callback with correct data', async ({ page }) => {
    await page.click('.test-btn:not(.test-btn--danger)');
    await page.waitForTimeout(500);
    
    const submitBtn = page.locator('.ticket-tier-form__submit');
    await expect(submitBtn).not.toBeDisabled();
    
    await submitBtn.click();
    await page.waitForTimeout(500);
    
    const logEntries = page.locator('.test-log-entry');
    await expect(logEntries.last()).toContainText('Form submitted');
  });

  test('BenefitsDisplay truncates long text and has expand button', async ({ page }) => {
    const shortCard = page.locator('.test-benefits-grid .test-card').first();
    await expect(shortCard.locator('.benefits-display__text')).toContainText('Access to all sessions and expo hall.');
    await expect(shortCard.locator('.benefits-display__expand')).not.toBeVisible();
    
    const longCard = page.locator('.test-benefits-grid .test-card').nth(1);
    await expect(longCard.locator('.benefits-display__expand')).toBeVisible();
    await expect(longCard.locator('.benefits-display__text')).toContainText('…');
    
    await longCard.locator('.benefits-display__expand').click();
    await page.waitForTimeout(200);
    
    await expect(longCard.locator('.benefits-display__collapse')).toBeVisible();
    await expect(longCard.locator('.benefits-display__text')).not.toContainText('…');
  });

  test('PricingComparison shows multiple tiers side-by-side', async ({ page }) => {
    const comparison = page.locator('.pricing-comparison');
    await expect(comparison).toBeVisible();
    
    await expect(comparison.locator('.pricing-comparison__title')).toContainText('Compare Ticket Tiers');
    
    const cards = comparison.locator('.pricing-comparison__card');
    await expect(cards).toHaveCount(3);
    
    await expect(cards.nth(0).locator('.ticket-tier-card__tier-name')).toContainText('Early Bird Special');
    await expect(cards.nth(1).locator('.ticket-tier-card__tier-name')).toContainText('Regular');
    await expect(cards.nth(2).locator('.ticket-tier-card__tier-name')).toContainText('VIP');
    
    await expect(cards.nth(0).locator('#tier-price-0')).toHaveValue('50.00');
    await expect(cards.nth(1).locator('#tier-price-1')).toHaveValue('75.00');
    await expect(cards.nth(2).locator('#tier-price-2')).toHaveValue('199.00');
  });

  test('All components handle loading states', async ({ page }) => {
    await page.check('input[type="checkbox"] >> nth=1');
    await page.waitForTimeout(500);
    
    await expect(page.locator('.skeleton').first()).toBeVisible();
  });

  test('Edge cases: missing image shows placeholder', async ({ page }) => {
    const noImageCard = page.locator('[data-testid="ticket-tier-2"]');
    await expect(noImageCard).toBeVisible();
    await expect(noImageCard.locator('.ticket-tier-card__placeholder')).toBeVisible();
  });

  test('Edge cases: null dates handled', async ({ page }) => {
    const noDatesCard = page.locator('[data-testid="ticket-tier-3"]');
    await expect(noDatesCard).toBeVisible();
    await expect(noDatesCard.locator('#tier-start-3')).toHaveValue('');
    await expect(noDatesCard.locator('#tier-end-3')).toHaveValue('');
  });

  test('Edge cases: very long descriptions truncate', async ({ page }) => {
    const longCard = page.locator('.test-benefits-grid .test-card').nth(1);
    await expect(longCard.locator('.benefits-display__text')).toContainText('…');
    await expect(longCard.locator('.benefits-display__expand')).toBeVisible();
  });
});