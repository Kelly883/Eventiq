import type {
  AdminUser,
  AdminEvent,
  AdminPayment,
  AdminSettings,
  AdminPayout,
  AuditLog,
  DashboardMetrics,
  DashboardAlert,
  TicketPurgeResponse,
} from './admin.types';

export interface PaginatedResponse<T> {
  readonly data: T[];
  readonly currentPage: number;
  readonly lastPage: number;
  readonly perPage: number;
  readonly total: number;
}

export interface ApiResponse<T> {
  readonly data: T;
  readonly message?: string;
}

export interface AdminUsersResponse extends PaginatedResponse<AdminUser> {}

export interface AdminEventsResponse extends PaginatedResponse<AdminEvent> {}

export interface AdminPaymentsResponse extends PaginatedResponse<AdminPayment> {}

export interface AdminPayoutsResponse extends PaginatedResponse<AdminPayout> {}

export interface AdminSettingsResponse extends PaginatedResponse<AdminSettings> {}

export interface AuditLogsResponse extends PaginatedResponse<AuditLog> {}

export interface DashboardMetricsResponse {
  readonly data: DashboardMetrics;
}

export interface DashboardAlertsResponse {
  readonly data: DashboardAlert[];
}

export interface TicketPurgeResponse {
  readonly success: boolean;
  readonly message: string;
  readonly ticketId: string;
  readonly checkInsPreserved: number;
}
