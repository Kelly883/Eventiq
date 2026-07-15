/**
 * Frontend client for the backend's fraud detection endpoints.
 *
 * Per the intended architecture, this talks ONLY to our Laravel backend -
 * never directly to Sift, Paystack, or Flutterwave. The backend holds all
 * secret keys and is the sole source of truth for fraud decisions.
 */

const API_BASE_URL = import.meta.env.VITE_API_BASE_URL || '';

export interface FraudTransactionInput {
  userId?: number | null;
  email?: string | null;
  amount: number;
  currency?: string;
  reference: string;
  provider: 'paystack' | 'flutterwave';
  ip?: string | null;
  sessionId?: string | null;
}

export interface FraudDecision {
  decision: 'approve' | 'review' | 'block';
  risk_score: number;
  flags: string[];
  sift: { score: number; reported: boolean };
  velocity: { exceeded: boolean; count_1h: number | null; count_24h: number | null; checked: boolean };
  card_testing: { suspected: boolean; checked: boolean };
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    credentials: 'include', // Sanctum SPA auth relies on the session cookie
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...options.headers,
    },
  });

  if (!response.ok) {
    const body = await response.json().catch(() => null);
    throw new Error(body?.message || `Fraud API request failed: ${response.status}`);
  }

  return response.json();
}

export const fraudApi = {
  detectFraudRisk(transaction: FraudTransactionInput): Promise<FraudDecision> {
    return request<FraudDecision>('/api/fraud/detect', {
      method: 'POST',
      body: JSON.stringify(transaction),
    });
  },

  verifyPaystackTransaction(reference: string) {
    return request(`/api/fraud/transactions/paystack/${encodeURIComponent(reference)}`);
  },

  verifyFlutterwaveTransaction(transactionId: string) {
    return request(`/api/fraud/transactions/flutterwave/${encodeURIComponent(transactionId)}`);
  },

  checkVelocity(userId: number, amount: number) {
    return request('/api/fraud/velocity', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, amount }),
    });
  },

  detectDuplicateTickets(ticketTierId: number, qrCode: string) {
    return request('/api/fraud/duplicate-tickets', {
      method: 'POST',
      body: JSON.stringify({ ticket_tier_id: ticketTierId, qr_code: qrCode }),
    });
  },

  getTransactionDetails(reference: string, provider: 'paystack' | 'flutterwave') {
    return provider === 'paystack'
      ? fraudApi.verifyPaystackTransaction(reference)
      : fraudApi.verifyFlutterwaveTransaction(reference);
  },
};
