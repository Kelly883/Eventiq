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
  deviceId?: string | null;
  ticketTierId?: number | null;
  qrCode?: string | null;
  ticketCount?: number;
}

export interface FraudDecision {
  decision: 'approve' | 'review' | 'block';
  risk_score: number;
  flags: string[];
  sift: { score: number; reported: boolean };
  velocity: { exceeded: boolean; count_1h: number | null; count_24h: number | null; checked: boolean };
  card_testing: { suspected: boolean; checked: boolean };
  device_check: { suspected: boolean; count: number; limit: number; checked: boolean };
  ip_check: { suspected: boolean; count: number; limit: number; checked: boolean };
  ticket_limit: { count: number; limit: number; exceeded: boolean };
  duplicate_ticket: boolean;
}

async function request<T>(path: string, options: RequestInit = {}): Promise<T> {
  // Backend uses Sanctum personal access tokens (Bearer header), not
  // cookie-based SPA sessions - confirmed against AuthController::login,
  // which issues $user->createToken(...)->plainTextToken. Matches the
  // token storage convention in src/lib/api.ts (same localStorage key).
  const token = (() => {
    try {
      return localStorage.getItem('authToken');
    } catch {
      return null;
    }
  })();

  const response = await fetch(`${API_BASE_URL}${path}`, {
    ...options,
    headers: {
      'Content-Type': 'application/json',
      Accept: 'application/json',
      ...(token ? { Authorization: `Bearer ${token}` } : {}),
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
  /**
   * Performs full fraud risk assessment on the backend.
   */
  detectFraudRisk(transaction: FraudTransactionInput): Promise<FraudDecision> {
    return request<FraudDecision>('/api/fraud/detect', {
      method: 'POST',
      body: JSON.stringify(transaction),
    });
  },

  /**
   * Verifies a Paystack transaction by reference.
   */
  verifyPaystackTransaction(reference: string) {
    return request(`/api/fraud/transactions/paystack/${encodeURIComponent(reference)}`);
  },

  /**
   * Verifies a Flutterwave transaction by ID.
   */
  verifyFlutterwaveTransaction(transactionId: string) {
    return request(`/api/fraud/transactions/flutterwave/${encodeURIComponent(transactionId)}`);
  },

  /**
   * Checks the checkout velocity for a given user.
   */
  checkVelocity(userId: number, amount: number) {
    return request('/api/fraud/velocity', {
      method: 'POST',
      body: JSON.stringify({ user_id: userId, amount }),
    });
  },

  /**
   * Checks for duplicate ticket signatures or QR codes on a given ticket tier.
   */
  detectDuplicateTickets(ticketTierId: number, qrCode: string) {
    return request('/api/fraud/duplicate-tickets', {
      method: 'POST',
      body: JSON.stringify({ ticket_tier_id: ticketTierId, qr_code: qrCode }),
    });
  },

  /**
   * Checks the reputation and transaction count of a given IP.
   */
  checkIpReputation(ip: string) {
    return request('/api/fraud/ip', {
      method: 'POST',
      body: JSON.stringify({ ip }),
    });
  },

  /**
   * Checks the transaction count/history of a given device ID.
   */
  checkDeviceFingerprint(deviceId: string) {
    return request('/api/fraud/device', {
      method: 'POST',
      body: JSON.stringify({ device_id: deviceId }),
    });
  },

  /**
   * Retrieves transaction / fraud event details by event ID or payment reference.
   */
  getTransactionDetails(reference: string, provider?: 'paystack' | 'flutterwave') {
    const queryParam = provider ? `?provider=${encodeURIComponent(provider)}` : '';
    return request(`/api/fraud/event/${encodeURIComponent(reference)}${queryParam}`);
  },

  /**
   * Checks for suspicious account activity metrics.
   */
  checkSuspiciousAccountActivity(userId: number) {
    // Can leverage velocity checks or basic account health queries
    return this.checkVelocity(userId, 0);
  },
};
