# TODO - Admin + Compliance + Payouts Structure

## Phase 1: Confirm existing payouts feature
- [x] Verified frontend payouts folder exists
- [x] Verified backend payouts feature exists

## Phase 2: Add missing Admin feature folders/files
- [x] Create `frontend/src/features/admin/` structure (pages/components/hooks/types/utils)
- [x] Create `backend/app/Features/admin/` structure (controllers/policy/models/route)
- [x] Add `backend/routes/admin.php` and wire routes


## Phase 3: Add missing Compliance feature folders/files
- [ ] Create `frontend/src/features/compliance/` structure (pages/components/hooks/types/services)
- [x] Create `backend/app/Features/Compliance/` structure (controllers/services/requests/jobs)

- [ ] Reuse existing `App\Models\AuditLog` + existing audit logs migration


## Phase 4: Compile-safe scaffolding
- [ ] Ensure new frontend modules export correctly from index files
- [ ] Ensure backend namespaces/imports/route inclusion are correct

## Phase 5: Basic sanity checks
- [ ] Run frontend build/typecheck (if available)
- [ ] Run `php artisan route:list` (if available)

