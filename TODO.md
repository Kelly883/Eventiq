# Step 65 - Ticket Tiers Integrity & Concurrency Improvements

## Progress Tracking

### [x] 1. Create Migration: `update_ticket_tiers_for_integrity_step65`
   - [x] Sync existing `available_count` values
   - [x] Add CHECK constraint `sold_count <= quantity`
   - [x] Add `deleted_at` index for soft-delete filtered queries
   - [x] Add composite index `(event_id, deleted_at)` for soft-delete scoped queries
   - [x] Add composite index `(event_id, id)` for partition-aware queries
   - [x] Add composite index `(event_id, is_active, sales_start_date, sales_end_date)` for availability checks

### [x] 2. Update `TicketTier` Model
   - [x] Remove `available_count` from `$fillable`
   - [x] Add `available_count` as read-only cast

### [x] 3. Add Concurrency Protection in `CheckoutController`
   - [x] Use `lockForUpdate()` when checking tier availability
   - [x] Use `lockForUpdate()` when fetching TicketInventory

### [x] 4. Add Concurrency Protection in `WebhookController`
   - [x] Add `TicketTier` import
   - [x] Use `lockForUpdate()` on TicketTier when incrementing `sold_count`
   - [x] Use `lockForUpdate()` on TicketInventory when incrementing `sold_quantity`

### [x] 5. Add Validation in `UpdateTicketTiersRequest`
   - [x] Add `withValidator` with custom after-validation hook preventing past `sales_start_date` for new tiers
   - [x] Add validation ensuring `sold_count <= quantity` for existing tiers

### [x] 6. All Changes Complete ✅

