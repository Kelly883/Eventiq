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
