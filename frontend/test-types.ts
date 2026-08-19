import type {
  AdminUser,
  AdminEvent,
  AdminPayment,
  AdminSettings,
  AuditLog,
  DashboardMetrics,
  DashboardAlert,
  UserRole,
  UserStatus,
  EventStatus,
  PaymentStatus,
  SettingCategory,
  TargetType,
  TrendDirection,
  AlertType,
  AlertSeverity,
} from './src/features/admin/types/admin.types';
import type {
  PaginatedResponse,
  ApiResponse,
  AdminUsersResponse,
  AdminEventsResponse,
  AdminPaymentsResponse,
  AdminSettingsResponse,
  AuditLogsResponse,
  DashboardMetricsResponse,
  DashboardAlertsResponse,
} from './src/features/admin/types/api-responses.types';

const user: AdminUser = {
  id: '1',
  email: 'test@example.com',
  name: 'Test User',
  role: 'admin',
  status: 'active',
  registeredAt: '2024-01-01T00:00:00Z',
  lastLoginAt: '2024-01-02T00:00:00Z',
};

const event: AdminEvent = {
  id: '1',
  title: 'Test Event',
  organizerId: '1',
  organizerName: 'Test Organizer',
  status: 'published',
  attendeeCount: 100,
  ticketsSold: 50,
  revenue: 5000,
  createdAt: '2024-01-01T00:00:00Z',
};

const payment: AdminPayment = {
  id: '1',
  orderId: '1',
  amount: 100,
  currency: 'USD',
  paymentMethod: 'card',
  status: 'completed',
  gatewayResponseCode: '00',
  timestamp: '2024-01-01T00:00:00Z',
  buyerEmail: 'buyer@example.com',
  fraudRiskScore: 0,
};

const setting: AdminSettings = {
  id: '1',
  settingKey: 'platform_name',
  settingValue: { value: 'Test' },
  description: 'Platform name',
  category: 'platform',
  isEditable: true,
};

const log: AuditLog = {
  id: '1',
  userId: '1',
  action: 'user_suspended',
  targetType: 'user',
  targetId: '1',
  description: 'User suspended',
  metadata: {},
  createdAt: '2024-01-01T00:00:00Z',
  performedByName: 'Admin',
};

const metrics: DashboardMetrics = {
  period: '7d',
  totalRevenue: 10000,
  activeEvents: 50,
  pendingApprovals: 5,
  flaggedTransactions: 2,
  failedPayouts: 1,
  revenueTrend: 'up',
  eventsTrend: 'flat',
  transactionsTrend: 'up',
};

const alert: DashboardAlert = {
  id: '1',
  type: 'payment_failure',
  severity: 'critical',
  title: 'Payment Failure',
  description: 'Multiple payment failures detected',
  actionUrl: '/admin/payments',
  timestamp: '2024-01-01T00:00:00Z',
};

const paginated: PaginatedResponse<AdminUser> = {
  data: [user],
  currentPage: 1,
  lastPage: 1,
  perPage: 10,
  total: 1,
};

const apiRes: ApiResponse<AdminSettings> = {
  data: setting,
  message: 'Success',
};

console.log(JSON.stringify({ user, event, payment, setting, log, metrics, alert, paginated, apiRes }, null, 2));
