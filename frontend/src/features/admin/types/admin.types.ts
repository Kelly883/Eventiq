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
  readonly createdAt?: string;
  readonly updatedAt?: string;
}

export interface AuditLog {
  readonly id: string;
  readonly userId: string;
  readonly action: string;
  readonly targetType: TargetType;
  readonly targetId: string;
  readonly description: string;
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
