# Repair Broken Migrations (permissions, calendar views, delivery, fraud_events)

## Goal
Bring the SQLite database to a consistent, migration-clean state where `php artisan migrate` runs without errors.

## Current Broken State
- Migrations recorded as ran: up to `2026_07_22_070102` only.
- `2026_07_22_081001_create_events_calendar_summary_table.php` has unresolved Git conflict markers (PHP syntax error) → breaks the entire pending batch.
- `delivery_preferences` table: MISSING.
- `fraud_events` table: MISSING.
- Calendar views: dropped (none exist).
- `events_calendar_summary`: MISSING.

## Plan Steps
- [x] Step 1: Repair `2026_07_22_081001_create_events_calendar_summary_table.php` (remove conflict markers).
- [x] Step 2: Verify the file passes `php -l` syntax check.
- [ ] Step 3: Create consolidated repair script `repair_broken_migrations_066.php` that:
  - Creates `delivery_preferences` table (all columns later migrations expect)
  - Creates `fraud_events` table (unified schema)
  - Recreates calendar views (`calendar_event_availability_view`, `calendar_date_availability_summary_view`, `calendar_events_availability`)
  - Creates + seeds `events_calendar_summary`
- [ ] Step 4: Run the repair script.
- [ ] Step 5: Run `php artisan migrate` to apply remaining pending migrations.
- [ ] Step 6: Verify all 4 target areas (permissions, calendar views, delivery, fraud_events) are consistent.
- [ ] Step 7: Clean up temporary scan/inspect scripts.

