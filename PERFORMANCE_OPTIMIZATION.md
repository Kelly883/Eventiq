# Performance Optimization Summary

This change set implements targeted performance improvements for checkout, payments, GraphQL, and offline sync.

## Database indexes

Migration: `backend/database/migrations/2026_07_23_000001_add_performance_indexes.php`

Added indexes:
- `payments(order_id, status, gateway)` for order payment status lookups
- `payments(gateway_reference)` for webhook/reference verification
- `payments(gateway, gateway_reference, status)` for fraud/transaction lookups
- `orders(user_id, status, created_at)` for organizer dashboard filters
- `offline_sync_inbox(status, next_retry_at, created_at)` for due queue scans
- `offline_sync_inbox(client_id, status, created_at)` for client/status history lookups

## Checkout query optimization

File: `backend/app/Features/Checkout/Http/Controllers/CheckoutController.php`

`createPaymentIntent()` now:
- Groups request items by tier before validation
- Batch-locks all needed `ticket_tiers` in one query
- Batch-locks all needed `ticket_inventory` rows in one query
- Validates and prices from the preloaded maps to avoid per-item query loops

This removes per-item lock/query churn and improves performance under multi-item orders.

## Payment + fraud HTTP resilience

Files:
- `backend/app/Features/Payment/Services/PaystackService.php`
- `backend/app/Features/Payment/Services/FlutterwaveService.php`
- `backend/app/Features/Fraud/Services/FraudDetectionService.php`

Added:
- `timeout(5)` default for outbound payment/fraud HTTP calls
- Retry with exponential backoff
- Configurable values in:
  - `backend/config/payment.php`
  - `backend/config/fraud.php`

## Frontend query caching

File: `frontend/src/lib/queryClient.ts`

Added cache profiles:
- `critical` (short stale time + window focus refetch)
- `standard` (2 minute stale time, no window focus refetch)
- `analytics` (5 minute stale time, no window focus refetch)

Default behavior now avoids aggressive refetch-on-focus for non-critical queries.

## Offline sync batching

File: `backend/app/Features/OfflineSync/Services/OfflineSyncEngine.php`

`applyDueQueue()` now processes due records in chunks (`offline_sync.apply_batch_size`, default `25`) and logs progress metrics for long-running queue drains.
