/**
 * Checkout service — thin wrapper over the checkout API.
 *
 * In development / sandbox these resolve against localStorage or a mocked
 * endpoint; in production they hit the real Laravel checkout endpoints.
 */

const API_BASE = import.meta.env.VITE_API_BASE_URL || '';

async function request(path, { method = 'GET', body, headers = {} } = {}) {
  const response = await fetch(`${API_BASE}${path}`, {
    method,
    headers: { 'Content-Type': 'application/json', ...headers },
    body: body ? JSON.stringify(body) : null,
    credentials: 'include',
  });
  if (!response.ok) {
    const err = new Error(`Checkout API ${method} ${path} failed (${response.status})`);
    err.status = response.status;
    throw err;
  }
  if (response.status === 204) return null;
  return response.json();
}

export const checkoutService = {
  /** Fetch the current cart from the server. */
  async getCart() {
    return request('/api/cart');
  },

  /** Persist the cart to the server. */
  async saveCart(cart) {
    return request('/api/cart', { method: 'PUT', body: { cart } });
  },

  /** Create a checkout session / payment intent. */
  async createCheckoutSession({ items, total, currency, email, gateway }) {
    return request('/api/checkout/session', {
      method: 'POST',
      body: { items, total, currency, email, gateway },
    });
  },

  /** Verify a payment after the gateway redirect/callback. */
  async verifyPayment({ reference, gateway }) {
    return request(`/api/payment/verify?reference=${encodeURIComponent(reference)}&gateway=${encodeURIComponent(gateway)}`);
  },
};

