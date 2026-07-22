# Step 60 Fixes - COMPLETED ✅

## Issues Fixed:

### 1. ✅ Migration (`2026_07_20_010014_create_pricing_windows_table_for_step60.php`)
- Added missing composite indexes: `idx_windows_active_daterange` (`[is_active, start_date_time, end_date_time]`) and `idx_windows_event_active` (`[event_id, is_active]`)
- Changed FK `ticket_category_id` from `ON DELETE CASCADE` → `ON DELETE SET NULL` (safer for soft-delete scenarios)

### 2. ✅ Supplemental Migration (`2026_07_21_020002_add_pricing_windows_indexes_and_fix_fk.php`)
- Created to apply the index & FK changes to the existing database (since Step 60 migration was already run)

### 3. ✅ PricingWindow Model (`App\Features\Pricing\Models\PricingWindow.php`)
- Updated `$fillable` to match migration schema: `id`, `event_id`, `ticket_category_id`, `window_name`, `start_date_time`, `end_date_time`, `price`, `quantity_limit`, `quantity_sold`, `is_active`, `priority`
- Added `SoftDeletes` trait
- Added UUID auto-generation via `boot()` method
- Added `$incrementing = false` and `$keyType = 'string'`
- Added casts for all columns including `deleted_at`
- Added relationships: `event()`, `ticketTier()`
- Added scopes: `scopeActive()`, `scopeForEvent()`, `scopeForTicketTier()`, `scopePrioritized()`
- Added methods: `hasAvailability()`, `incrementSold()` (atomic race-safe)

### 4. ✅ StorePricingWindowRequest
- Updated validation rules: `window_name`, `ticket_category_id`, `start_date_time`, `end_date_time`, `price`, `quantity_limit`, `is_active`, `priority`

### 5. ✅ UpdatePricingWindowRequest
- Updated validation rules with `sometimes` prefix for partial updates

### 6. ✅ PricingWindowResource
- Explicitly defines all fields returned in API responses
- Added computed field `has_availability`

### 7. ✅ PricingWindowPolicy
- Implemented authorization logic for `viewAny`, `view`, `create`, `update`, `delete`, `restore`, `forceDelete`

### 8. ✅ PricingWindowController
- Full CRUD implementation: `index`, `store`, `show`, `update`, `destroy`, `restore`
- Uses `PricingWindowResource` for response formatting
- Supports filtering by `active_only` and `ticket_category_id`
- Supports pagination

### 9. ✅ Verification
- Migration ran successfully
- All 14 columns confirmed: `id`, `event_id`, `ticket_category_id`, `window_name`, `start_date_time`, `end_date_time`, `price`, `quantity_limit`, `quantity_sold`, `is_active`, `priority`, `created_at`, `updated_at`, `deleted_at`
- All 8 indexes confirmed (including the 2 new composite indexes)
- Default values verified: `is_active=0`, `quantity_sold=0`, `priority=0`

---

## Priority Fixes Applied (2026-07-22)

### 🔴 CRITICAL: `incrementSold()` Race Condition Fix
- **File:** `backend/app/Features/Pricing/Models/PricingWindow.php`
- Replaced stale `$this->quantity_sold` + `update()` with SQL-level atomic `increment()` + `whereColumn`
- Now uses `whereNull('quantity_limit')` or `whereColumn('quantity_sold', '<', DB::raw('quantity_limit'))` to prevent overselling under concurrent requests
- Calls `$this->refresh()` after successful increment to keep model in sync with DB

### 🔴 Timezone Consistency Fix
- **File:** `backend/app/Features/Pricing/Models/PricingWindow.php`
- Changed `scopeActive()` from `now()` (PHP timezone-dependent) to `DB::raw('NOW()')` (DB timezone)

### 🟡 `quantity_sold` Forced to 0 on Creation
- **File:** `backend/app/Features/Pricing/Controllers/PricingWindowController.php`
- Added `$data['quantity_sold'] = 0;` in `store()` method to always start at 0
- **File:** `backend/app/Features/Pricing/Requests/StorePricingWindowRequest.php`
- Removed `quantity_sold` from validation rules
- **File:** `backend/app/Features/Pricing/Requests/UpdatePricingWindowRequest.php`
- Removed `quantity_sold` from validation rules

### 🟡 Overlap Detection Added
- **File:** `backend/app/Features/Pricing/Requests/StorePricingWindowRequest.php`
- Added overlap validation rule that checks for existing active windows with overlapping date ranges for the same `event_id + ticket_category_id`
- Checks start/end date overlaps (start between, end between, or fully enclosing)
- Only applies when the new window is set to active

### 🟡 Missing Performance Indexes Added
- **File:** `backend/database/migrations/2026_07_22_010000_add_pricing_windows_performance_indexes.php`
- Migration ran successfully ✅
- `idx_windows_tier_active_daterange` — composite `[ticket_category_id, is_active, start_date_time, end_date_time]` for tier-specific active window queries
- `idx_windows_event_priority_start` — composite `[event_id, priority, start_date_time]` for prioritized sorting

### 🟢 `hasAvailability()` Fresh Data Fix
- **File:** `backend/app/Features/Pricing/Models/PricingWindow.php`
- Now always fetches fresh data from DB via `static::find($this->id)` to avoid stale model state
- Added `use Illuminate\Support\Facades\DB;` import

