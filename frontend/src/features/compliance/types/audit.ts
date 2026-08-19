export type ComplianceClassification = 'public' | 'internal' | 'confidential' | 'restricted';

export type AuditLogAction =
  | 'user_login'
  | 'user_logout'
  | 'user_suspended'
  | 'event_created'
  | 'event_approved'
  | 'event_flagged'
  | 'event_cancelled'
  | 'payment_processed'
  | 'payment_refunded'
  | 'refund_requested'
  | 'refund_approved'
  | 'refund_rejected'
  | 'payout_approved'
  | 'payout_rejected'
  | 'ticket_checked_in'
  | 'ticket_voided'
  | 'fraud_flagged'
  | 'fraud_approved'
  | 'admin_setting_changed'
  | 'user_permission_changed'
  | 'data_export_requested';

export type AuditLogTargetType = 'user' | 'event' | 'order' | 'payout' | 'refund' | 'payment' | 'setting';

export type AuditLogStatus = 'success' | 'failure' | 'warning' | 'pending';

export interface AuditLogGeolocation {
  readonly ip_address?: string;
  readonly [key: string]: unknown;
}

export interface AuditLogJsonData {
  readonly [key: string]: unknown;
}

export interface AuditLog {
  readonly id: string;
  readonly userId: string;
  readonly action: AuditLogAction;
  readonly targetType: AuditLogTargetType;
  readonly targetId: string;
  readonly description?: string;
  readonly targetName?: string | null;
  readonly geolocation: AuditLogGeolocation | null;
  readonly requestData: AuditLogJsonData | null;
  readonly responseData: AuditLogJsonData | null;
  readonly changedFields: AuditLogJsonData | null;
  readonly status: AuditLogStatus;
  readonly complianceClassification: ComplianceClassification;
  readonly metadata: AuditLogJsonData;
  readonly createdAt: string;
  readonly updatedAt: string;
  readonly deletedAt?: string;
}

export interface AuditLogFilter {
  readonly dateRange?: {
    readonly start: string;
    readonly end: string;
  };
  readonly action?: AuditLogAction;
  readonly targetType?: AuditLogTargetType;
  readonly status?: AuditLogStatus;
  readonly classification?: ComplianceClassification;
  readonly search?: string;
  readonly perPage?: number;
}

export interface AuditLogMetrics {
  readonly totalEvents: number;
  readonly successRate: number;
  readonly failedCount: number;
  readonly retentionDaysRemaining: number;
}

export interface AuditLogListResponse {
  readonly data: AuditLog[];
  readonly metrics: AuditLogMetrics;
}

export interface AuditLogPaginatedListResponse {
  readonly data: AuditLog[];
  readonly metrics: AuditLogMetrics;
  readonly total: number;
  readonly perPage: number;
  readonly currentPage: number;
  readonly lastPage: number;
}

export interface AuditLogDetailsResponse {
  readonly data: AuditLog;
}
