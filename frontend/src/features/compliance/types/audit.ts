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
  | 'refund.requested'
  | 'refund_approved'
  | 'refund_rejected'
  | 'payout_approved'
  | 'payout_rejected'
  | 'ticket_checked_in'
  | 'ticket_voided'
  | 'ticket.purged'
  | 'fraud_flagged'
  | 'fraud_approved'
  | 'admin_setting_changed'
  | 'user_permission_changed'
  | 'data_export_requested'
  | 'check_in';

export type AuditLogTargetType = 'user' | 'event' | 'order' | 'payout' | 'refund' | 'payment' | 'setting' | 'ticket';

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
  readonly performedByName?: string | null;
  readonly action: AuditLogAction;
  readonly targetType: AuditLogTargetType;
  readonly targetId: string;
  readonly targetName?: string | null;
  readonly description?: string;
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

export function isAuditLogAction(value: string): value is AuditLogAction {
  return [
    'user_login',
    'user_logout',
    'user_suspended',
    'event_created',
    'event_approved',
    'event_flagged',
    'event_cancelled',
    'payment_processed',
    'payment_refunded',
    'refund.requested',
    'refund_approved',
    'refund_rejected',
    'payout_approved',
    'payout_rejected',
    'ticket_checked_in',
    'ticket_voided',
    'ticket.purged',
    'fraud_flagged',
    'fraud_approved',
    'admin_setting_changed',
    'user_permission_changed',
    'data_export_requested',
    'check_in',
  ].includes(value);
}

export function isAuditLogTargetType(value: string): value is AuditLogTargetType {
  return ['user', 'event', 'order', 'payout', 'refund', 'payment', 'setting', 'ticket'].includes(value);
}

export function isAuditLogStatus(value: string): value is AuditLogStatus {
  return ['success', 'failure', 'warning', 'pending'].includes(value);
}

export function isComplianceClassification(value: string): value is ComplianceClassification {
  return ['public', 'internal', 'confidential', 'restricted'].includes(value);
}

export function normalizeAuditLog(raw: Partial<AuditLog> & { id: string }): AuditLog {
  const action = isAuditLogAction(raw.action ?? '') ? raw.action! : 'user_login';
  const targetType = isAuditLogTargetType(raw.targetType ?? '') ? raw.targetType! : 'user';
  const status = isAuditLogStatus(raw.status ?? '') ? raw.status! : 'success';
  const complianceClassification = isComplianceClassification(raw.complianceClassification ?? '')
    ? raw.complianceClassification!
    : 'internal';

  return {
    id: raw.id,
    userId: raw.userId ?? '',
    performedByName: raw.performedByName ?? null,
    action,
    targetType,
    targetId: raw.targetId ?? '',
    targetName: raw.targetName ?? null,
    description: raw.description,
    geolocation: raw.geolocation ?? null,
    requestData: raw.requestData ?? null,
    responseData: raw.responseData ?? null,
    changedFields: raw.changedFields ?? null,
    status,
    complianceClassification,
    metadata: raw.metadata ?? {},
    createdAt: raw.createdAt ?? new Date().toISOString(),
    updatedAt: raw.updatedAt ?? new Date().toISOString(),
    deletedAt: raw.deletedAt,
  };
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
