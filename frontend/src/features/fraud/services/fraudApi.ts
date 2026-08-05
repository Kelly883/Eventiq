import { api } from '../../../lib/api';
import type { AxiosResponse } from 'axios';

export interface FraudTransactionInput {
  user_id?: number | null;
  userId?: number | null;
  email?: string | null;
  amount: number;
  currency?: string;
  reference: string;
  provider: 'paystack' | 'flutterwave';
  ip?: string | null;
  sessionId?: string | null;
  session_id?: string | null;
  deviceId?: string | null;
  device_id?: string | null;
  ticketTierId?: number | null;
  ticket_tier_id?: number | null;
  qrCode?: string | null;
  qr_code?: string | null;
  ticketCount?: number;
  ticket_count?: number;
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

export interface FraudAlertResponse {
  id: string | number;
  user_id?: string | number;
  order_id?: string | number;
  event_type: string;
  risk_level: 'low' | 'medium' | 'high' | 'critical';
  status: 'pending' | 'reviewed' | 'resolved' | 'dismissed' | 'escalated';
  risk_score: number;
  description?: string;
  metadata?: Record<string, unknown>;
  created_at: string;
  updated_at: string;
}

export interface FraudDashboardStatsResponse {
  total_alerts_today: number;
  pending_review: number;
  critical_alerts: number;
  resolved_today: number;
  avg_risk_score: number;
  fraud_prevention_rate: number;
  flagged_revenue: number;
}

type UnwrapAxios<T> = T extends AxiosResponse<infer D> ? D : T;

function data<T>(promise: Promise<AxiosResponse<T>>): Promise<T> {
  return promise.then((res) => res.data);
}

export const fraudApi = {
  detectFraudRisk: (payload: FraudTransactionInput): Promise<FraudDecision> =>
    data(api.post<FraudDecision>('/fraud/detect', payload)),

  verifyPaystackTransaction: (reference: string) =>
    data(api.get(`/fraud/transactions/paystack/${encodeURIComponent(reference)}`)),

  verifyFlutterwaveTransaction: (transactionId: string) =>
    data(api.get(`/fraud/transactions/flutterwave/${encodeURIComponent(transactionId)}`)),

  checkVelocity: (userId: number, amount: number) =>
    data(api.post('/fraud/velocity', { user_id: userId, amount })),

  detectDuplicateTickets: (ticketTierId: number, qrCode: string) =>
    data(api.post('/fraud/duplicate-tickets', { ticket_tier_id: ticketTierId, qr_code: qrCode })),

  checkIpReputation: (ip: string) =>
    data(api.post('/fraud/ip', { ip })),

  checkDeviceFingerprint: (deviceId: string) =>
    data(api.post('/fraud/device', { device_id: deviceId })),

  getTransactionDetails: (reference: string, provider?: 'paystack' | 'flutterwave') =>
    data(api.get(`/fraud/event/${encodeURIComponent(reference)}`, { params: provider ? { provider } : undefined })),

  listAlerts: (params?: Record<string, unknown>) =>
    data(api.get<FraudAlertResponse[]>('/fraud/alerts', { params })),

  getAlert: (alertId: string | number) =>
    data(api.get<FraudAlertResponse>(`/fraud/alerts/${alertId}`)),

  updateAlertStatus: (alertId: string | number, payload: { status: string; notes?: string; decision?: string }) =>
    data(api.patch<FraudAlertResponse>(`/fraud/alerts/${alertId}`, payload)),

  getDashboardStats: () =>
    data(api.get<FraudDashboardStatsResponse>('/fraud/dashboard/stats')),

  runManualCheck: (checkId: string, payload: Record<string, unknown>) =>
    data(api.post(`/fraud/checks/${checkId}/run`, payload)),
};
