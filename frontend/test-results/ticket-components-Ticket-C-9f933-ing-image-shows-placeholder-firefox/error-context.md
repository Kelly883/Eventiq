# Instructions

- Following Playwright test failed.
- Explain why, be concise, respect Playwright best practices.
- Provide a snippet of code with the fix, if possible.

# Test info

- Name: ticket-components.spec.ts >> Ticket Components >> Edge cases: missing image shows placeholder
- Location: tests/ticket-components.spec.ts:126:3

# Error details

```
Error: page.goto: NS_ERROR_CONNECTION_REFUSED
Call log:
  - navigating to "http://localhost:3000/ticket-components", waiting until "load"

```

# Test source

```ts
  1   | import { test, expect } from '@playwright/test';
  2   | 
  3   | test.describe.configure({ retries: 0, timeout: 60000 });
  4   | 
  5   | test.describe('Ticket Components', () => {
  6   |   test.beforeEach(async ({ page }) => {
> 7   |     await page.goto('http://localhost:3000/ticket-components');
      |                ^ Error: page.goto: NS_ERROR_CONNECTION_REFUSED
  8   |     await page.waitForLoadState('networkidle');
  9   |   });
  10  | 
  11  |   test('TicketTierCard displays all tier information', async ({ page }) => {
  12  |     const card1 = page.locator('[data-testid="ticket-tier-0"]');
  13  |     await expect(card1).toBeVisible();
  14  |     
  15  |     await expect(card1.locator('.ticket-tier-card__tier-name')).toContainText('Early Bird Special');
  16  |     await expect(card1.locator('#tier-price-0')).toHaveValue('50.00');
  17  |     await expect(card1.locator('#tier-quantity-0')).toHaveValue('100');
  18  |     await expect(card1.locator('#tier-start-0')).toHaveValue('2026-01-01');
  19  |     await expect(card1.locator('#tier-end-0')).toHaveValue('2026-12-31');
  20  |     await expect(card1.locator('.input-limits')).toContainText('Min: 1');
  21  |     await expect(card1.locator('.input-limits')).toContainText('Max: 4');
  22  |     await expect(card1.locator('.benefits-display__text')).toContainText('main stage sessions');
  23  |     await expect(card1.locator('.ticket-tier-card__image')).toHaveAttribute('src', /unsplash/);
  24  |   });
  25  | 
  26  |   test('Early bird badge appears only when early bird is active', async ({ page }) => {
  27  |     const card1 = page.locator('[data-testid="ticket-tier-0"]');
  28  |     await expect(card1.locator('.early-bird-badge')).toBeVisible();
  29  |     await expect(card1.locator('.early-bird-badge')).toContainText('Early Bird');
  30  |     
  31  |     const card2 = page.locator('[data-testid="ticket-tier-1"]');
  32  |     await expect(card2.locator('.early-bird-badge')).not.toBeVisible();
  33  |   });
  34  | 
  35  |   test('EarlyBirdBadge calculates and displays discount percentage', async ({ page }) => {
  36  |     const badgeItems = page.locator('.test-badge-item');
  37  |     await expect(badgeItems).toHaveCount(6);
  38  |     
  39  |     const badge1 = badgeItems.nth(0);
  40  |     await expect(badge1.locator('.early-bird-badge')).toContainText('30% Off');
  41  |     await expect(badge1.locator('.early-bird-badge__savings')).toContainText('Save $15.00');
  42  |     
  43  |     const badge2 = badgeItems.nth(1);
  44  |     await expect(badge2.locator('.early-bird-badge')).toContainText('25% Off');
  45  |     await expect(badge2.locator('.early-bird-badge__savings')).toContainText('Save $18.75');
  46  |     
  47  |     const badge3 = badgeItems.nth(2);
  48  |     await expect(badge3.locator('.early-bird-badge')).toContainText('50% Off');
  49  |     await expect(badge3.locator('.early-bird-badge__savings')).toContainText('Save $50.00');
  50  |     
  51  |     const badge4 = badgeItems.nth(3);
  52  |     await expect(badge4.locator('.early-bird-badge')).not.toBeVisible();
  53  |     
  54  |     const badge5 = badgeItems.nth(4);
  55  |     await expect(badge5.locator('.early-bird-badge')).not.toBeVisible();
  56  |     
  57  |     const badge6 = badgeItems.nth(5);
  58  |     await expect(badge6.locator('.early-bird-badge')).not.toBeVisible();
  59  |   });
  60  | 
  61  |   test('TicketTierForm validates all fields', async ({ page }) => {
  62  |     const form = page.locator('.ticket-tier-form');
  63  |     await expect(form).toBeVisible();
  64  |     
  65  |     const submitBtn = page.locator('.ticket-tier-form__submit');
  66  |     await expect(submitBtn).toBeDisabled();
  67  |     
  68  |     await expect(page.locator('[role="alert"]')).toBeVisible();
  69  |   });
  70  | 
  71  |   test('Form submission calls the callback with correct data', async ({ page }) => {
  72  |     await page.click('.test-btn:not(.test-btn--danger)');
  73  |     await page.waitForTimeout(500);
  74  |     
  75  |     const submitBtn = page.locator('.ticket-tier-form__submit');
  76  |     await expect(submitBtn).not.toBeDisabled();
  77  |     
  78  |     await submitBtn.click();
  79  |     await page.waitForTimeout(500);
  80  |     
  81  |     const logEntries = page.locator('.test-log-entry');
  82  |     await expect(logEntries.last()).toContainText('Form submitted');
  83  |   });
  84  | 
  85  |   test('BenefitsDisplay truncates long text and has expand button', async ({ page }) => {
  86  |     const shortCard = page.locator('.test-benefits-grid .test-card').first();
  87  |     await expect(shortCard.locator('.benefits-display__text')).toContainText('Access to all sessions and expo hall.');
  88  |     await expect(shortCard.locator('.benefits-display__expand')).not.toBeVisible();
  89  |     
  90  |     const longCard = page.locator('.test-benefits-grid .test-card').nth(1);
  91  |     await expect(longCard.locator('.benefits-display__expand')).toBeVisible();
  92  |     await expect(longCard.locator('.benefits-display__text')).toContainText('…');
  93  |     
  94  |     await longCard.locator('.benefits-display__expand').click();
  95  |     await page.waitForTimeout(200);
  96  |     
  97  |     await expect(longCard.locator('.benefits-display__collapse')).toBeVisible();
  98  |     await expect(longCard.locator('.benefits-display__text')).not.toContainText('…');
  99  |   });
  100 | 
  101 |   test('PricingComparison shows multiple tiers side-by-side', async ({ page }) => {
  102 |     const comparison = page.locator('.pricing-comparison');
  103 |     await expect(comparison).toBeVisible();
  104 |     
  105 |     await expect(comparison.locator('.pricing-comparison__title')).toContainText('Compare Ticket Tiers');
  106 |     
  107 |     const cards = comparison.locator('.pricing-comparison__card');
```