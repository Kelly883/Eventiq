# Navigation Schema

> Auto-generated from Phase 5D Integration Architecture.
> This is the single source of truth for routing and navigation.

## Auth Flow
- Login style: `modal`
- Login route: `/login`
- Post-login route: `/dashboard`
- Post-logout route: `/`
- Unauthorized behavior: `show-modal`

## Public Routes
- `/` → **EventBrowsePage** ((auth)) [PublicLayout]
- `/register` → **RegisterPage** ((auth)) [PublicLayout]
- `/forgot-password` → **ForgotPasswordPage** ((auth)) [PublicLayout]
- `/reset-password` → **ResetPasswordPage** ((auth)) [PublicLayout]

## Protected Routes
- `/dashboard` → **OrganizerDashboardPage** ((app)) [AppShell] — nav slot: `sidebar-dashboard`
- `/events` → **EventBrowsePage** ((app)) [AppShell] — nav slot: `sidebar-events`
- `/events/:eventId` → **EventDetailPage** ((app)) [AppShell] — parent: `/events`
- `/events/category/:categoryId` → **CategoryBrowsePage** ((app)) [AppShell] — parent: `/events`
- `/events/calendar` → **EventCalendarPage** ((app)) [AppShell] — parent: `/events`
- `/cart` → **CartPage** ((app)) [AppShell] — nav slot: `sidebar-checkout`
- `/checkout` → **CheckoutPage** ((app)) [AppShell]
- `/order/:orderId/confirmation` → **OrderConfirmationPage** ((app)) [AppShell] — parent: `/order/:orderId`
- `/my-tickets` → **UserTicketsPage** ((app)) [AppShell]
- `/my-tickets/:ticketId` → **TicketDetailPage** ((app)) [AppShell] — parent: `/my-tickets`
- `/my-tickets/:ticketId/status` → **TicketStatusPage** ((app)) [AppShell] — parent: `/my-tickets/:ticketId`
- `/my-tickets/:ticketId/refund-request` → **UserRefundRequestPage** ((app)) [AppShell] — parent: `/my-tickets/:ticketId`
- `/my-tickets/:ticketId/refund-status` → **UserRefundStatusPage** ((app)) [AppShell] — parent: `/my-tickets/:ticketId`
- `/organizer/:organizerId` → **OrganizerPublicProfilePage** ((app)) [AppShell] — parent: `/organizer`
- `/organizer/profile/edit` → **OrganizerProfileEditPage** ((app)) [AppShell] — parent: `/organizer/profile`
- `/organizer/profile/settings` → **OrganizerProfileSettingsPage** ((app)) [AppShell] — parent: `/organizer/profile`
- `/organizer/events` → **OrganizerEventListPage** ((app)) [AppShell] — parent: `/organizer`
- `/organizer/events/create` → **EventCreatePage** ((app)) [AppShell] — parent: `/organizer/events`
- `/organizer/events/:eventId/edit` → **EventEditPage** ((app)) [AppShell] — parent: `/organizer/events/:eventId`
- `/organizer/events/:eventId/pricing` → **EventPricingConfigPage** ((app)) [AppShell] — parent: `/organizer/events/:eventId`
- `/organizer/events/:eventId/inventory` → **TicketInventoryDashboardPage** ((app)) [AppShell] — parent: `/organizer/events/:eventId`
- `/organizer/events/:eventId/analytics` → **SalesAnalyticsDashboardPage** ((app)) [AppShell] — parent: `/organizer/events/:eventId`
- `/organizer/events/:eventId/analytics/detailed` → **DetailedAnalyticsPage** ((app)) [AppShell] — parent: `/organizer/events/:eventId/analytics`
- `/organizer/analytics/compare` → **AnalyticsComparisonPage** ((app)) [AppShell] — parent: `/organizer/analytics`
- `/organizer/payouts` → **OrganizerPayoutDashboardPage** ((app)) [AppShell] — parent: `/organizer`
- `/organizer/settings/payments` → **OrganizerPaymentSettingsPage** ((app)) [AppShell] — parent: `/organizer/settings`
- `/venue/check-in/:eventId` → **VenueCheckInPage** ((app)) [AppShell] — parent: `/venue/check-in`
- `/settings/permissions` → **UserPermissionsPage** ((app)) [AppShell] — parent: `/settings`
- `/settings/delivery-preferences` → **DeliverySettingsPage** ((app)) [AppShell] — parent: `/settings`
- `/settings/accessibility` → **AccessibilitySettingsPage** ((app)) [AppShell] — parent: `/settings`
- `/settings/language` → **LanguagePreferencePage** ((app)) [AppShell] — parent: `/settings`
- `/settings/device-localization` → **DeviceLocalizationSyncPage** ((app)) [AppShell] — parent: `/settings`
- `/settings/payment-methods` → **PaymentMethodsPage** ((app)) [AppShell] — parent: `/settings`
- `/developer` → **DeveloperPortalPage** ((app)) [AppShell]

## Admin Routes
- `/admin/dashboard` → **AdminDashboardPage**
- `/admin/users` → **AdminUserManagementPage**
- `/admin/events` → **AdminEventModerationPage**
- `/admin/payments` → **AdminPaymentReconciliationPage**
- `/admin/roles` → **AdminRoleManagementPage**
- `/admin/fraud/dashboard` → **FraudDetectionDashboardPage**
- `/admin/delivery/dashboard` → **AdminDeliveryDashboardPage**
- `/admin/refunds/dashboard` → **AdminRefundDashboardPage**
- `/admin/settlements/dashboard` → **AdminSettlementDashboardPage**
- `/admin/settings/email-templates` → **AdminEmailTemplateManagementPage**
- `/admin/settings/push-templates` → **AdminPushTemplateManagementPage**
- `/admin/compliance/audit-logs` → **AuditLogsViewerPage**
- `/admin/compliance/reports` → **ComplianceReportsPage**

## Modal Routes (no standalone page)
- **LoginPage** — triggered by: Any protected page (unauthorized access)
- **PricingPreviewModal** — triggered by: EventPricingConfigPage (click preview button)
- **AdjustInventoryModal** — triggered by: TicketInventoryDashboardPage (click adjust inventory)
- **EventDetailWidget** — triggered by: OrganizerDashboardPage (click event card)
- **SalesActivityFeed** — triggered by: OrganizerDashboardPage (click activity feed)
- **CalendarDayDetailModal** — triggered by: EventCalendarPage (click calendar day)
- **FraudTransactionReviewModal** — triggered by: FraudDetectionDashboardPage (click review transaction)
- **UserTicketsDashboardPage** — triggered by: UserTicketsPage (click dashboard view)
- **UserDashboardPage** — triggered by: UserTicketsPage (click overview)
- **PushNotificationSettingsSection** — triggered by: Settings pages (click notification settings)

## Web Navigation (sidebar)
- `/dashboard` — Dashboard (icon: home, order: 0)
- `/events` — Events (icon: calendar, order: 1)
- `/cart` — Cart (icon: shopping-cart, order: 2)
- `/admin/dashboard` — Admin (icon: settings, order: 3)

## Validation Notes
- ⚠️ loginStyle is "modal" but "LoginPage" claims route "/login" → Converted login page to modal stub
- ❌ Route conflict: "/organizer/events/:eventId/pricing" claimed by both "EventPricingConfigPage" and "PricingPreviewModal" → Demoted "PricingPreviewModal" to modal (kept "EventPricingConfigPage" as page)
- ❌ Route conflict: "/organizer/events/:eventId/inventory" claimed by both "TicketInventoryDashboardPage" and "AdjustInventoryModal" → Demoted "AdjustInventoryModal" to modal (kept "TicketInventoryDashboardPage" as page)
- ❌ Route conflict: "/dashboard" claimed by both "OrganizerDashboardPage" and "EventDetailWidget" → Demoted "EventDetailWidget" to modal (kept "OrganizerDashboardPage" as page)
- ❌ Route conflict: "/dashboard" claimed by both "OrganizerDashboardPage" and "SalesActivityFeed" → Demoted "SalesActivityFeed" to modal (kept "OrganizerDashboardPage" as page)
- ❌ Route conflict: "/events/calendar" claimed by both "EventCalendarPage" and "CalendarDayDetailModal" → Demoted "CalendarDayDetailModal" to modal (kept "EventCalendarPage" as page)
- ❌ Route conflict: "/admin/fraud/dashboard" claimed by both "FraudDetectionDashboardPage" and "FraudTransactionReviewModal" → Demoted "FraudTransactionReviewModal" to modal (kept "FraudDetectionDashboardPage" as page)
- ❌ Route conflict: "/my-tickets" claimed by both "UserTicketsPage" and "UserTicketsDashboardPage" → Demoted "UserTicketsDashboardPage" to modal (kept "UserTicketsPage" as page)
- ❌ Route conflict: "/dashboard" claimed by both "OrganizerDashboardPage" and "UserDashboardPage" → Demoted "UserDashboardPage" to modal (kept "OrganizerDashboardPage" as page)
- ❌ Route conflict: "/settings/delivery-preferences" claimed by both "DeliverySettingsPage" and "PushNotificationSettingsSection" → Demoted "PushNotificationSettingsSection" to modal (kept "DeliverySettingsPage" as page)
- ⚠️ Sidebar overflow: more than 8 sidebar items claimed → Moved "AdminUserManagementPage" to secondary nav
- ⚠️ Sidebar overflow: more than 8 sidebar items claimed → Moved "AdminEventModerationPage" to secondary nav
- ⚠️ Sidebar overflow: more than 8 sidebar items claimed → Moved "AdminPaymentReconciliationPage" to secondary nav
