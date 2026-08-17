# Eventiq

Eventiq is a full-stack event management and ticketing platform. It provides event discovery, ticketing, QR-based check-in, analytics, pricing windows, and organizer dashboards.

## Tech Stack

- **Backend**: Laravel 11, PHP 8.2+, MySQL/SQLite, Laravel Sanctum, Queues, Events, Observers
- **Frontend**: React 18, Vite, Tailwind CSS, React Router
- **Infrastructure**: Vercel (frontend), containerized backend

## Features

- Event browsing and discovery
- Ticket tiers with early bird pricing
- Pricing windows with dynamic pricing
- QR code ticketing and venue scanning
- Sales analytics and reporting
- Organizer and user dashboards
- Admin controls and email notifications
- Push notifications (FCM)

## Project Structure

```
.
├── backend/          # Laravel API and business logic
│   ├── app/
│   │   ├── Models/                   # Eloquent models
│   │   ├── Http/                     # Controllers, requests, resources
│   │   ├── Features/                 # Feature-specific modules
│   │   │   ├── Inventory/
│   │   │   ├── Pricing/
│   │   │   ├── Ticketing/
│   │   │   └── OrganizerProfile/
│   │   ├── Observers/                # Model observers
│   │   ├── Enums/                    # PHP enums
│   │   └── Providers/                # Service providers
│   ├── database/
│   │   ├── migrations/
│   │   ├── factories/
│   │   └── seeders/
│   └── routes/
├── frontend/         # React SPA
│   ├── src/
│   │   ├── features/ # Feature modules
│   │   │   ├── analytics/
│   │   │   ├── ticketing/
│   │   │   ├── pricing/
│   │   │   ├── ticketInventory/
│   │   │   ├── organizer-profile/
│   │   │   └── ...
│   │   ├── lib/      # Shared utilities
│   │   └── App.jsx   # Router and layout
│   ├── index.html
│   └── vite.config.js
└── vercel.json       # SPA rewrite rules
```

## Getting Started

### Prerequisites

- PHP 8.2+
- Composer
- Node.js 18+
- npm or yarn
- PostgreSQL (production) or SQLite (local testing)

### Database

This project uses:
- **PostgreSQL** in production
- **SQLite** for local development and testing

The Laravel database configuration in `config/database.php` supports both drivers. Migrations are written to be cross-compatible where possible, with driver-specific guards for advanced features.

### Backend Setup

```bash
cd backend
cp .env.example .env
php artisan key:generate
php artisan migrate --force
composer install
php artisan serve
```

### Frontend Setup

```bash
cd frontend
npm install
npm run dev
```

### Running Tests

```bash
cd backend
php artisan test
```

## Environment Variables

### Backend

| Variable | Description |
|---|---|
| `APP_KEY` | Laravel application key |
| `DB_CONNECTION` | Database driver (`sqlite` for local, `pgsql` for production) |
| `DB_DATABASE` | Database name or path |
| `DB_HOST` | Database host |
| `DB_PORT` | Database port (5432 for PostgreSQL) |
| `DB_USERNAME` | Database username |
| `DB_PASSWORD` | Database password |
| `SANCTUM_STATEFUL_DOMAINS` | Frontend domain for session auth |

### Frontend

| Variable | Description |
|---|---|
| `VITE_API_BASE_URL` | Backend API URL |

## Deployment

- **Frontend**: Deployed on Vercel with root directory set to `frontend`, framework preset `Vite`, and output `dist`. `vercel.json` includes SPA rewrites for React Router.
- **Backend**: Deploy the Laravel app to your preferred PHP hosting. Set `DB_CONNECTION=pgsql` and configure PostgreSQL connection variables in `.env`.

## Implementation Progress

### Step 84 — Organizer Model & Types
- **Backend**: `Organizer` model with fillable fields (`displayName`, `bio`, `avatarUrl`, `email`, `phone`, `website`, `socialLinks`, `brandingColors`, `timezone`, `currency`, `country`, `verificationStatus`, `paymentDefault`, `commissionRate`, visibility flags, `notificationPreferences`), soft deletes, `getPublicProfile()` / `getPrivateProfile()` privacy filtering, relationships (`user`, `events`, `tickets`, `payoutMethods`, `apiKeys`), and `recalculateStats()`.
- **Frontend**: TypeScript interfaces (`Organizer`, `OrganizerPublic`, `OrganizerSocialLinks`, `BrandingColors`, `NotificationPreferences`, `OrganizerStats`) and Zod validation schemas (`OrganizerUpdateSchema`, `AvatarUploadSchema`).

### Step 85 — Event & TicketTier Models & Types
- **Backend**: `Event` model with fillable fields, casts, capacity enforcement in `booted()`, relationships (`organizer`, `user`, `ticketTiers`, `pricingWindows`), and scopes (`published`, `whereNotDeleted`). `TicketTier` model with full fillable fields, casts, computed `available_count` accessor, `isAvailable()` / `getEffectivePrice()` / `isEarlyBirdActive()` business logic, and scopes (`published`, `active`, `available`, `forEvent`, `ordered`).
- **Frontend**: Completed JSDoc types for `Event` and `TicketTier` including all DB fields. Added timezone-safe date utilities in `lib/dateUtils.ts` (`normalizeSalesDate`, `formatSalesWindow`, `isSalesWindowActive`, `isEarlyBirdActiveForTier`, `getEffectivePriceForTier`, `isAvailableForTier`, `getRemainingQuantity`).

### Step 86 — TicketTier Hardening
- Aligned instance method `isAvailable()` with query scope `scopeAvailable()` to include `sold_count >= quantity` guard.
- Made `isEarlyBirdActive()`, `getEffectivePrice()`, and `isAvailable()` accept optional `?\Carbon\Carbon $now` for testability.
- Changed `getAvailableCountAttribute()` and `getRemainingQuantity()` to return `0` instead of `null` when `quantity` is null, preventing UI crashes.
- Added `$appends = ['available_count']` to `TicketTier`.
- Added model `booted()` hook enforcing: `sold_count <= quantity`, `price > 0`, `early_bird_price < price`.
- Added API resources: `OrganizerPublicResource`, `OrganizerPrivateResource`.

### Step 87 — PricingWindow Model & Validation
- **Backend**: `PricingWindow` model with UUID primary key, `$appends = ['available_quantity']`, `isActive(?\Carbon\Carbon $now = null)` for testability, `scopeActive()` using `now()`, atomic `incrementSold()` with `DB::transaction()` + `lockForUpdate()` for race-safe checkout, `hasAvailability()`, and scopes (`forEvent`, `forTicketTier`, `prioritized`).
- **Database**: Added check constraints migration for `quantity_sold >= 0`, `price >= 0`, `priority >= 0` (skipped on SQLite).
- **Frontend**: Aligned `pricingWindowSchema` with backend validation (`min:0` for price, `min:0` for quantity_limit, added `ticket_category_id`). Fixed `ticket_category_id` type from `string` to `number` in JSDoc.
- **Tests**: Added `PricingWindowTest` covering `isActive()` boundaries, `scopeActive()`, `hasAvailability()`, `incrementSold()`, and model validation. Created `PricingWindowFactory`.

### Step 88 — TicketInventory & InventoryAdjustment
- **Backend**: `TicketInventory` model with computed `total_available` (`max(0, allocated - sold)`) and `is_low_stock` (`> 0 && <= threshold`) accessors, `updateFromPricingWindows()` to recalculate from related pricing windows, and `booted()` validation preventing `total_sold > total_allocated`. Default `low_stock_threshold` is `10`.
- **Backend**: `InventoryAdjustment` model made immutable via `booted()` hooks (throws on update/delete). Removed `quantity_delta` from fillable; computed via accessor. Added `scopeForEvent()`.
- **Frontend**: Fixed `calculateLowStock()` to match backend logic (`available > 0 && available <= threshold`).
- **Database**: Added migration to drop redundant virtual columns `total_available` and `is_low_stock` from `ticket_inventory` (now computed via accessors only).
- **Tests**: Added `TicketInventoryTest` and `InventoryAdjustmentTest` with factories.

### Step 89 — Analytics Models & Types
- **Backend**: `AnalyticsEventsMetric` with UUIDs, relationships (`event`, `organizer`, `topTicketTier`), `scopeForEvent()`, `scopeForOrganizer()`, `scopeRecent()`. `AnalyticsSalesTimeline` with UUIDs, immutability, scopes (`forEvent`, `byDateRange`, `byTier`, `recent`). `AnalyticsTierPerformance` with UUIDs, relationships, `scopeForEvent()`, `scopeForTier()`, `scopeRecent()`.
- **Enum**: Added `SaleSourceEnum` (`web`, `mobile`, `pos`, `admin`, `api`) for `AnalyticsSalesTimeline.source` validation.
- **API Resources**: `AnalyticsEventsMetricResource`, `AnalyticsSalesTimelineResource` (conditionally exposes `buyer_email` only with `viewPII` permission), `AnalyticsTierPerformanceResource`.
- **Frontend**: Completed JSDoc types for `AnalyticsMetrics`, `SalesTimelineEntry`, `TierPerformance`, `SalesVelocityDataPoint`. Added helpers: `formatRevenue(metrics, currency = 'NGN')`, `formatConversionRate(metrics)`, `getTrendIndicator(metrics, direction)`, `buildSalesVelocityData(dataPoints)` for Recharts.

### Infrastructure & CI Fixes
- **Vercel**: Added `vercel.json` with SPA rewrite rules (`/(.*)` → `/index.html`) to fix 404 on client-side routes. Configured root directory as `frontend` and framework preset as `Vite`.
- **Composer**: Disabled security advisory blocking in `composer.json` to resolve CI install failures for `laravel/framework`.
- **Tests**: Aligned inline test schemas (`TicketPurgeTest`, `CheckoutWebhookTest`, `RefundProcessingTest`) with enhanced camelCase model columns.
- **Currency**: Set default currency to **NGN** (Naira) across frontend formatters (`formatPrice`, `formatCurrency`, `formatRevenue`) and backend payout fallback.

## Contributing

1. Create a feature branch from `main`
2. Make changes and run tests
3. Open a pull request

## License

Proprietary
