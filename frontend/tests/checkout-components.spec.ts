import { test, expect } from '@playwright/test';

test.describe.configure({ retries: 0, timeout: 60000 });

test.describe('Checkout Components', () => {
  test.beforeEach(async ({ page }) => {
    await page.goto('http://localhost:3000/checkout-components');
    await page.waitForLoadState('networkidle');
  });

  test('OrderSummary displays subtotal, fees, tax, and total correctly', async ({ page }) => {
    await expect(page.locator('#sec-order-summary')).toBeVisible();
    await expect(page.locator('#sec-order-summary')).toContainText('$9,000.00');
    await expect(page.locator('#sec-order-summary')).toContainText('$1,200.00');
    await expect(page.locator('#sec-order-summary')).toContainText('$300.00');
    await expect(page.locator('#sec-order-summary')).toContainText('$11,400.00');
  });

  test('OrderSummary shows all line items with quantities and prices', async ({ page }) => {
    await expect(page.locator('#sec-order-summary')).toContainText('2x');
    await expect(page.locator('#sec-order-summary')).toContainText('1x');
    await expect(page.locator('#sec-order-summary')).toContainText('General Admission');
    await expect(page.locator('#sec-order-summary')).toContainText('VIP');
  });

  test('PaymentForm renders a pay button and validates email', async ({ page }) => {
    const paySection = page.locator('#sec-payment');
    await expect(paySection).toBeVisible();

    const payButton = page.locator('#sec-payment button').first();
    await expect(payButton).toBeVisible();
    await expect(payButton).toContainText(/Pay with|Processing/);

    const emailInput = page.locator('#payment-email');
    await emailInput.fill('not-an-email');
    await payButton.click();
    await expect(page.locator('#email-error')).toBeVisible();
    await expect(page.locator('#email-error')).toContainText('valid email');
  });

  test('PaymentForm submission triggers onError callback', async ({ page }) => {
    await page.locator('#payment-email').fill('buyer@example.com');
    await page.locator('#sec-payment button').first().click();
    await page.waitForTimeout(300);
    await expect(page.locator('#checkout-cb-log')).toContainText('payment:error');
  });

  test('TicketCard displays event name, date, venue, and ticket tier', async ({ page }) => {
    const ticketSection = page.locator('#sec-ticket-card');
    await expect(ticketSection).toBeVisible();
    await expect(ticketSection).toContainText('Jazz Night Live');
    await expect(ticketSection).toContainText('December 15, 2026');
    await expect(ticketSection).toContainText('The Blue Note, New York');
    await expect(ticketSection).toContainText('VIP');
  });

  test('TicketCard shows the QR code', async ({ page }) => {
    await expect(page.locator('#sec-ticket-card svg')).toBeVisible();
  });

  test('TicketCard has download PDF and add to wallet buttons', async ({ page }) => {
    const ticketSection = page.locator('#sec-ticket-card');
    await expect(ticketSection.getByRole('button', { name: /Download PDF/ })).toBeVisible();
    await expect(ticketSection.getByRole('button', { name: /Add to Wallet/ })).toBeVisible();
  });

  test('QRCodeDisplay renders a QR code from the data string', async ({ page }) => {
    await expect(page.locator('#sec-qr svg')).toHaveCount(2);
  });

  test('QRCodeDisplay respects the size prop', async ({ page }) => {
    const large = page.locator('#sec-qr svg').first();
    const small = page.locator('#sec-qr svg').nth(1);
    await expect(large).toHaveAttribute('width', '160');
    await expect(small).toHaveAttribute('width', '80');
  });

  test('QRCodeDisplay shows fallback when data is empty', async ({ page }) => {
    await expect(page.locator('#sec-qr')).toContainText('QR code not available');
  });

  test('CartItemRow shows ticket tier, event, quantity, price, and line total', async ({ page }) => {
    const cartSection = page.locator('#sec-cart-item');
    await expect(cartSection).toContainText('General Admission');
    await expect(cartSection).toContainText('Rock Fest 2026');
    await expect(cartSection).toContainText('2');
    await expect(cartSection).toContainText('$5,000.00');
  });

  test('CartItemRow +/- buttons call onQuantityChange', async ({ page }) => {
    const cartSection = page.locator('#sec-cart-item');
    const increaseBtn = cartSection.getByRole('button', {
      name: /Increase General Admission quantity/,
    });
    const decreaseBtn = cartSection.getByRole('button', {
      name: /Decrease General Admission quantity/,
    });

    await increaseBtn.click();
    await page.waitForTimeout(200);
    await expect(page.locator('#checkout-cb-log')).toContainText('cart:quantityChange');

    await decreaseBtn.click();
    await page.waitForTimeout(200);
    await expect(page.locator('#checkout-cb-log')).toContainText('cart:quantityChange');
  });

  test('CartItemRow remove button calls onRemove', async ({ page }) => {
    const cartSection = page.locator('#sec-cart-item');
    const removeBtn = cartSection.getByRole('button', {
      name: /Remove General Admission from cart/,
    });
    await removeBtn.click();
    await page.waitForTimeout(200);
    await expect(page.locator('#checkout-cb-log')).toContainText('cart:remove');
  });

  test('OrderConfirmation renders after simulated payment', async ({ page }) => {
    await page.locator('text=Simulate successful payment').click();
    await expect(page.locator('#sec-confirmation')).toContainText('Order placed');
    await expect(page.locator('#sec-confirmation')).toContainText('$11,400.00');
    await expect(page.locator('#sec-confirmation')).toContainText('order_123');
    await expect(page.getByRole('button', { name: /View tickets/ })).toBeVisible();
    await expect(page.getByRole('button', { name: /Continue shopping/ })).toBeVisible();
  });
});

