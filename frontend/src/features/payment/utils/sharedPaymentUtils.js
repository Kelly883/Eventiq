// Shared payment utilities (browser-side).

export function buildGatewayPayload({ gateway, amount, currency, customer, reference }) {
  return { gateway, amount, currency, customer, reference };
}

export function buildPaymentStatusUrl({ reference }) {
  return `/payment/status?reference=${encodeURIComponent(reference)}`;
}

