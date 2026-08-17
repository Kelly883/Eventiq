export type FraudRiskLevel = 'low' | 'medium' | 'high' | 'critical';

export type FraudAlertStatus = 'pending' | 'reviewed' | 'resolved' | 'dismissed' | 'escalated';

export type FraudEventType =
  | 'suspicious_login'
  | 'multiple_failed_payments'
  | 'unusual_location'
  | 'ticket_scalping'
  | 'duplicate_order'
  | 'high_velocity_purchases'
  | 'blacklisted_ip'
  | 'stolen_card'
  | 'chargeback_risk'
  | 'identity_mismatch';

export interface FraudAlert {
  id: string | number;
  user_id?: string | number;
  order_id?: string | number;
  event_type: FraudEventType;
  risk_level: FraudRiskLevel;
  status: FraudAlertStatus;
  risk_score: number;
  description?: string;
  metadata?: Record<string, unknown>;
  assignee_id?: string | number;
  reviewed_at?: string;
  resolved_at?: string;
  created_at: string;
  updated_at: string;
}

export interface FraudCheckRequest {
  user_id: string | number;
  order_id?: string | number;
  event_id?: string | number;
  ip_address?: string;
  user_agent?: string;
  email?: string;
  phone?: string;
  billing_address?: FraudAddress;
  payment_method?: {
    brand?: string;
    last4?: string;
    fingerprint?: string;
  };
  order_amount?: number;
  ticket_quantity?: number;
}

export interface FraudCheckResult {
  passed: boolean;
  risk_level: FraudRiskLevel;
  risk_score: number;
  triggered_rules: FraudRuleResult[];
  alert_id?: string | number;
  requires_manual_review: boolean;
}

export interface FraudRuleResult {
  rule_code: string;
  rule_name: string;
  description: string;
  risk_score_contribution: number;
  triggered: boolean;
  metadata?: Record<string, unknown>;
}

export interface FraudAddress {
  line1?: string;
  line2?: string;
  city?: string;
  state?: string;
  postal_code?: string;
  country?: string;
}

export interface FraudTransactionReview {
  alert_id: string | number;
  decision: 'approve' | 'reject' | 'flag' | 'escalate';
  notes?: string;
  reviewer_id: string | number;
}

export interface FraudDashboardStats {
  total_alerts_today: number;
  pending_review: number;
  critical_alerts: number;
  resolved_today: number;
  avg_risk_score: number;
  fraud_prevention_rate: number;
  flagged_revenue: number;
}

export interface FraudAlertFilters {
  status?: FraudAlertStatus[];
  risk_level?: FraudRiskLevel[];
  event_type?: FraudEventType[];
  date_from?: string;
  date_to?: string;
  user_id?: string | number;
  order_id?: string | number;
  assignee_id?: string | number;
  search?: string;
}

export type RiskLevel = 'low' | 'medium' | 'high';

export type EventType =
  | 'suspicious_login'
  | 'multiple_failed_payments'
  | 'unusual_location'
  | 'ticket_scalping'
  | 'duplicate_order'
  | 'high_velocity_purchases'
  | 'blacklisted_ip'
  | 'stolen_card'
  | 'chargeback_risk'
  | 'identity_mismatch';

export type DetectionMethod = 'rule_based' | 'ml_model' | 'manual_review' | 'velocity_check' | 'device_fingerprint' | 'geolocation';

export type FraudEventStatus = 'flagged' | 'reviewed' | 'approved' | 'rejected' | 'auto_blocked';

export interface FraudFactors {
  readonly duplicateTicketDetected: boolean;
  readonly velocityCheckFailed: boolean;
  readonly paymentPatternSuspicious: boolean;
  readonly deviceFingerprintMismatch: boolean;
  readonly geolocationAnomaly: boolean;
  readonly cardTestingPattern: boolean;
  readonly highRiskPaymentMethod: boolean;
}

export interface PaymentDetails {
  readonly cardLast4: string;
  readonly issuer: string;
  readonly country: string;
  readonly cardFingerprint: string;
}

export interface VelocityMetrics {
  readonly ordersIn24h: number;
  readonly totalSpendIn24h: number;
  readonly averageOrderValue: number;
  readonly ordersInLastHour: number;
}

export interface DeviceInfo {
  readonly ipAddress: string;
  readonly userAgent: string;
  readonly deviceFingerprint: string;
  readonly country: string;
  readonly city: string;
}

export interface DuplicateTicketInfo {
  readonly matchingTicketIds: string[];
  readonly matchingQRCodes: string[];
  readonly matchingEventIds: string[];
}

export interface FraudEvent {
  readonly id: string;
  readonly orderId: string | null;
  readonly userId: string;
  readonly eventType: EventType;
  readonly riskScore: number;
  readonly riskLevel: RiskLevel;
  readonly detectionMethod: DetectionMethod;
  readonly fraudFactors: FraudFactors | null;
  readonly paymentDetails: PaymentDetails | null;
  readonly velocityMetrics: VelocityMetrics | null;
  readonly deviceInfo: DeviceInfo | null;
  readonly duplicateTicketInfo: DuplicateTicketInfo | null;
  readonly status: FraudEventStatus;
  readonly reviewedBy: string | null;
  readonly reviewNotes: string | null;
  readonly reviewedAt: string | null;
  readonly createdAt: string;
  readonly updatedAt: string;
}
