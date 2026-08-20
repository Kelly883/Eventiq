# Feature Mapping

This document maps frontend feature folders to backend feature folders and their bounded contexts.

## Naming Convention

- **Frontend:** kebab-case (`events-calendar`, `ticket-inventory`)
- **Backend:** kebab-case (`events-calendar`, `ticket-inventory`) — kept in sync via explicit PSR-4 mappings in `composer.json` so PHP namespaces remain PascalCase.

## Feature Map

| Frontend | Backend | Bounded Context | Notes |
|---|---|---|---|
| `accessibility-localization` | — | Accessibility & localization preferences | Backend handled via `app/Http/Controllers/` and shared preferences |
| `admin` | `admin` | Platform administration | Admin controllers, models, and policies |
| `analytics` | `analytics` | Event analytics and sales metrics | |
| `auth` | — | Authentication | Backend in `app/Http/Controllers/AuthController.php` |
| `check-in` | `check-in` | Venue check-in and ticket validation | |
| `checkout` | `checkout` | Order creation, payment intent, cart | |
| `compliance` | `compliance` | Audit logs, compliance reports, data export | |
| `dashboard` | `dashboard` | Organizer and user dashboards | |
| `developer` | — | Developer tools / API keys | API keys backend in `api-keys` |
| `email-notifications` | `email-notifications` | Email template management | |
| `events-calendar` | `events-calendar` | Calendar views, availability, date filtering | |
| `events` | — | Event CRUD, browsing, categorization | Backend event controllers in `app/Http/Controllers/` |
| `fraud` | `fraud` | Fraud detection and alerting | |
| `notifications` | `notifications` | Push notification preferences | |
| `offline` | `offline-sync` | Offline sync engine and conflict resolution | |
| `organizer-profile` | `organizer-profile` | Organizer profile settings and branding | |
| `payment` | `payment` | Payment gateways, methods, webhooks | |
| `payouts` | `payouts` | Organizer payouts and settlement policies | |
| `pricing` | `pricing` | Pricing windows and tier pricing | |
| `push-notifications` | `push-notifications` | Push notification templates and device tokens | |
| `qr-code-ticketing` | `qr-code-ticketing` | QR code generation, venue check-in, encryption | |
| `refunds` | `refunds` | Refund requests, appeals, policies | |
| `roles` | — | Role and permission management | Backend in `app/Http/Controllers/Admin/` |
| `ticket-delivery` | `delivery` | Ticket delivery events and preferences | |
| `ticket-inventory` | `inventory` | Ticket inventory counts and adjustments | |
| `ticketing` | `ticketing` | Ticket tier configuration and management | Organizer-side |
| `tickets` | `tickets` | User ticket instances and detail views | Attendee-side |

## Merged / Removed Features

- `ticketInventory` (frontend) — duplicate of `ticket-inventory`. Merged into `ticket-inventory` on 2026-08-20.

## Subfolder Standards

Not every feature needs every subfolder. Use only what the feature requires:

### Frontend (`src/features/*/`)
- `components/` — Reusable UI pieces
- `pages/` — Route-level pages
- `hooks/` — Custom React hooks
- `types/` — TypeScript types/interfaces
- `services/` — API clients and business logic
- `constants/` — Feature-specific constants
- `store/` — State management (Zustand, etc.)
- `utils/` — Pure helper functions
- `context/` — React context providers (only when needed)
- `validation/` — Form validation schemas (only when needed)

### Backend (`app/Features/*/`)
- `Controllers/` or `Http/Controllers/` — HTTP controllers
- `Models/` — Eloquent models
- `Policies/` — Authorization policies
- `Requests/` or `Http/Requests/` — Form requests
- `Resources/` or `Http/Resources/` — API resources
- `Routes/` — Route files (only when feature owns routes)
- `Services/` — Business logic services
- `Jobs/` — Queued jobs
- `Enums/` — Enumerations
- `Contracts/` — Interfaces/contracts
- `DTOs/` — Data transfer objects
- `Mails/` — Mailable classes
- `Events/` — Domain events
