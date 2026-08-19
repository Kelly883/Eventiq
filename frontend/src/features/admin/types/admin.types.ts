export type UserRole = 'admin' | 'organizer' | 'attendee';

export type UserStatus = 'active' | 'suspended' | 'pending';

export type EventStatus = 'published' | 'draft' | 'cancelled' | 'flagged';

export type PaymentStatus = 'completed' | 'pending' | 'failed' | 'refunded';

export type SettingCategory = 'platform' | 'fraud' | 'payments' | 'notifications' | 'system';

export type TargetType = 'user' | 'event' | 'order' | 'payout';

export type TrendDirection = 'up' | 'down' | 'flat';

export type AlertType = 'payment_failure' | 'fraud_spike' | 'system_error' | 'payout_failure' | 'low_inventory';

export type AlertSeverity = 'critical' | 'warning' | 'info';

export interface AdminUser {
  readonly id: string;
  readonly email: string;
  readonly name: string;
  readonly role: UserRole;
  readonly status: UserStatus;
  readonly registeredAt: string;
  readonly lastLoginAt: string;
  readonly emailVerified?: boolean;
  readonly twoFactorEnabled?: boolean;
  readonly permissions?: string[];
  readonly suspensionReason?: string;
  readonly suspensionDate?: string;
}

export interface AdminEvent {
  readonly id: string;
  readonly title: string;
  readonly organizerId: string;
  readonly organizerName: string;
  readonly status: EventStatus;
  readonly attendeeCount: number;
  readonly ticketsSold: number;
  readonly revenue: number;
  readonly startDatetime?: string;
  readonly endDatetime?: string;
  readonly venueName?: string;
  readonly capacity?: number;
  readonly description?: string;
  readonly createdAt: string;
  readonly flagReason?: string;
  readonly flagDate?: string;
}

export interface AdminPayment {
  readonly id: string;
  readonly orderId: string;
  readonly amount: number;
  readonly currency: string;
  readonly paymentMethod: string;
  readonly status: PaymentStatus;
  readonly gatewayResponseCode: string;
  readonly timestamp: string;
  readonly buyerEmail: string;
  readonly fraudRiskScore: number;
  readonly refundedAmount?: number;
  readonly isFullyRefunded?: boolean;
  readonly settledAt?: string;
  readonly fraudDetectionMethod?: string;
}

export interface AdminSettings {
  readonly id: string;
  readonly settingKey: string;
  readonly settingValue: Record<string, unknown>;
  readonly description: string;
  readonly category: SettingCategory;
  readonly isEditable: boolean;
  readonly lastModifiedBy?: string;
  readonly lastModifiedAt?: string;
}

/**
 * AuditLog represents an admin-facing audit trail entry.
 *
 * Note: `performedByName` is not a direct database column. It must be resolved
 * on the backend by eager-loading the `user` relationship and mapping `user.name`.
 * If the relationship is not loaded, this field will be null or a placeholder.
 */
export interface AuditLog {
  readonly id: string;
  readonly userId: string;
  readonly action: string;
  readonly targetType: TargetType;
  readonly targetId: string;
  readonly description?: string;
  readonly metadata: Record<string, unknown>;
  readonly createdAt: string;
  readonly performedByName: string;
}

export interface DashboardMetrics {
  readonly period: string;
  readonly totalRevenue: number;
  readonly activeEvents: number;
  readonly pendingApprovals: number;
  readonly flaggedTransactions: number;
  readonly failedPayouts: number;
  readonly revenueTrend: TrendDirection;
  readonly eventsTrend: TrendDirection;
  readonly transactionsTrend: TrendDirection;
}

export interface DashboardAlert {
  readonly id: string;
  readonly type: AlertType;
  readonly severity: AlertSeverity;
  readonly title: string;
  readonly description: string;
  readonly actionUrl: string;
  readonly timestamp: string;
}

export interface AdminPayout {
  readonly id: string;
  readonly organizerId: string;
  readonly organizerName: string;
  readonly status: 'pending' | 'calculated' | 'approved' | 'processing' | 'completed' | 'failed';
  readonly grossRevenue: number;
  readonly refundsDeducted: number;
  readonly netRevenue: number;
  readonly platformCommissionAmount: number;
  readonly payoutAmount: number;
  readonly currency: string;
  readonly payoutMethod: string;
  readonly failureReason?: string;
  readonly retryCount: number;
  readonly canRetry: boolean;
  readonly settlementPeriodStart: string;
  readonly settlementPeriodEnd: string;
  readonly calculatedAt: string;
  readonly completedAt?: string;
}

export interface AdminUserUpdate {
  readonly role?: UserRole;
  readonly status?: UserStatus;
  readonly suspensionReason?: string;
  readonly suspensionDate?: string;
}

export interface AdminEventFlagRequest {
  readonly reason: string;
}

export interface AdminEventCancelRequest {
  readonly reason: string;
}

export interface PaymentRefundRequest {
  readonly amount: number;
  readonly reason: string;
}

export interface TicketPurgeResponse {
  readonly success: boolean;
  readonly message: string;
  readonly ticketId: string;
  readonly checkInsPreserved: number;
}
