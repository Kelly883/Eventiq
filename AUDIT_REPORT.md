# EventIQ Comprehensive Audit Report

**Date:** 2026-09-18
**Scope:** Full backend + frontend audit per `AUDIT_PROMPT.md`
**Working Directory:** `/root/Eventiq`

---

## Section A: Confirmed Bugs

---

### Bug #1: Restore overlap logic blocks inactive windows incorrectly
- **Severity**: High
- **Files**: `backend/app/Features/Pricing/Controllers/PricingWindowController.php:291-312`
- **Root cause**: The `restore()` method runs the overlap check unconditionally, regardless of whether the window being restored has `is_active = false`. After restore, an inactive window will remain inactive, so it cannot create a functional overlap with any other active window. The check should be conditional on `$window->is_active`.
- **Exact failing condition**: Restoring an inactive soft-deleted window that overlaps with an active window returns 409 even though the restored window will remain inactive.
- **Impact**: 409 error on valid restore operation; prevents organizers from restoring inactive archived windows.
- **Call chain**: `routes/api.php:115` → `PricingWindowController::restore()` → overlap query at line 292 → 409 response
- **Fix**: Guard the overlap check with `if ($window->is_active)` before running the query, consistent with how `store()` and `update()` gate overlap checks on the window's active state.

---

### Bug #2: Frontend-backend contract drift — snake_case vs camelCase field names
- **Severity**: Critical
- **Files**:
  - `backend/app/Features/Compliance/Controllers/AuditLogController.php:32-39`
  - `backend/app/Features/Compliance/Services/AuditLogService.php:92-121`
  - `frontend/src/features/compliance/types/audit.ts:48-74`
  - `frontend/src/features/compliance/hooks/useAuditLogs.js:27-28`
- **Root cause**: The `AuditLogController::index` returns raw Eloquent model arrays via `$results->items()`. The underlying `App\Models\AuditLog` model stores all fields in snake_case (`user_id`, `target_type`, `request_data`, `response_data`, `changed_fields`, `compliance_classification`, `ip_address`, `user_agent`, `retention_date`, `retention_reason`, `created_at`, `updated_at`, `deleted_at`). No JsonResource is applied to transform these keys. The frontend `AuditLog` type declares camelCase properties (`userId`, `targetType`, `requestData`, `responseData`, `changedFields`, `complianceClassification`, `ipAddress`, `userAgent`, `retentionDate`, `retentionReason`, `createdAt`, `updatedAt`). The `normalizeAuditLog` function reads `raw.userId`, `raw.targetType`, etc. Since the backend returns snake_case, every camelCase field is `undefined` and falls back to defaults.
- **Exact failing condition**: `normalizeAuditLog` at `audit.ts:167-190` reads camelCase keys from snake_case response → all fields except `id`, `action`, `status`, `source`, and `metadata` are empty or incorrect.
- **Impact**: All audit log fields except a few are empty or incorrect in the frontend. The UI displays blank values for user, entity, dates, IP addresses, etc.
- **Call chain**: `routes/api.php:8` → `auth:sanctum + role:admin` → `AuditLogController::index` → `AuditLogService::filter()` → raw Eloquent items → `response()->json(['data' => ...])` → axios → `complianceService.getAuditLogs` → `useAuditLogs` → `normalizeAuditLog` → default values for all camelCase fields.
- **Fix**: Apply `AuditLogResource` in `AuditLogController` to wrap items in `AuditLogResource::collection()` for index/export, and `new AuditLogResource($log)` for show. Alternatively, fix `normalizeAuditLog` to accept snake_case input.

---

### Bug #3: Analytics endpoints missing authorization — data leak
- **Severity**: Critical
- **Files**: `backend/app/Features/Analytics/Controllers/AnalyticsController.php:108-145`
- **Root cause**: `getSummary()` (line 108) and `getDetailed()` (line 127) contain no ownership check at all — they only read `$eventId` from the route and return data immediately. Any user authenticated via `auth:sanctum` can call these endpoints for any `{event}` ID and receive analytics data. `getSalesVelocity` (line 16) correctly checks event ownership with `$ownsEvent` and aborts 403 if the user does not own the event.
- **Exact failing condition**: Any authenticated user can enumerate event analytics by guessing event IDs.
- **Impact**: Data leak — any authenticated user can access any event's analytics.
- **Call chain**: `Analytics/Routes/api.php:7` → `auth:sanctum` → `AnalyticsController::getSummary(eventId)` → returns data with no ownership gate.
- **Fix**: Add the same `$ownsEvent` check from `getSalesVelocity` (lines 19–23) to both `getSummary` and `getDetailed` before returning data.

---

### Bug #4: `OrganizerProfileController::auditLog` — `$isOrganizer` always evaluates to `true`
- **Severity**: Critical
- **Files**: `backend/app/Features/OrganizerProfile/Controllers/OrganizerProfileController.php:430`
- **Root cause**: `$isOrganizer = $user->hasRole('organizer') || $user->hasRole('Organizer') || true;` — the `|| true` literal makes the entire expression always `true`. The subsequent fallback role-pivot check (lines 432–435) is therefore dead code — it can never execute. Any authenticated user who can reach this endpoint (protected by `bearer` middleware) can read any organizer's audit log.
- **Exact failing condition**: Any authenticated user can read any organizer's audit log via `GET /api/organizers/me/audit-log`.
- **Impact**: Confidential audit log data exposed to any authenticated user.
- **Call chain**: `OrganizerProfile/Routes/api.php:27` → `bearer` middleware → `OrganizerProfileController::auditLog()` → `$isOrganizer = true` → audit log returned for any user.
- **Fix**: Remove the `|| true` literal.

---

### Bug #5: `OfflineSyncController` — `organizer_id = user.id` type mismatch
- **Severity**: High
- **Files**: `backend/app/Features/OfflineSync/Controllers/OfflineSyncController.php:97-98, 146-147`
- **Root cause**: `$eventsQuery = Event::where('organizer_id', $user->id)->orWhere('user_id', $user->id);` — `events.organizer_id` is a foreign key referencing `organizers.id` (integer auto-increment). `user.id` is a UUID string. Comparing `organizer_id` (integer) to `user.id` (UUID string) will never match. The intent is to find events where the authenticated user is the organizer, which requires joining through the `organizers` table.
- **Exact failing condition**: `where('organizer_id', $user->id)` is effectively a no-op due to type mismatch.
- **Impact**: The offline sync endpoints return no tickets for organizer users.
- **Call chain**: `OfflineSync/Routes/api.php` → `OfflineSyncController::sync()` or `getTicketsForOfflineSync()` → broken query.
- **Fix**: Replace with `Event::whereHas('organizer', fn($q) => $q->where('user_id', $user->id))`.

---

### Bug #6: `OrganizerPolicy::update` misses `userId` fallback column
- **Severity**: High
- **Files**: `backend/app/Policies/OrganizerPolicy.php:18`
- **Root cause**: `OrganizerPolicy::update` checks `$user->id === $organizer->user_id`. The `organizers` table also has a `userId` column (added in migration `2026_07_20_010007`) that is used as a fallback throughout the codebase. A user whose organizer profile was created/updated via the `userId` column would fail the `OrganizerPolicy` check and receive a 403.
- **Exact failing condition**: Users with `userId`-linked organizer profiles receive 403 from `Gate::authorize('update', $organizer)`.
- **Impact**: Legitimate organizer users with `userId`-linked profiles are incorrectly denied.
- **Fix**: Replace line 18 with `return $user->id === ($organizer->user_id ?? $organizer->userId);`.

---

### Bug #7: `WebhookPolicy` and `ApiKeyPolicy` miss `role_id` and pivot checks
- **Severity**: High
- **Files**:
  - `backend/app/Policies/WebhookPolicy.php:15,20,25,30,35`
  - `backend/app/Policies/ApiKeyPolicy.php:15,20,25,30,35,40`
- **Root cause**: Both policies use `$user->role?->name === 'organizer'` and `$user->role?->name === 'admin'` exclusively. This only checks the loaded `role` relationship on User. If `role_id` is set but `role` is not eager-loaded, `$user->role` is `null` and the check returns `false`.
- **Exact failing condition**: Users with `role_id` set but no eager-loaded `role` relation are incorrectly denied API key / webhook access.
- **Impact**: Authorization bypass/denial depending on whether the `role` relation is loaded.
- **Fix**: Replace all `$user->role?->name === 'organizer'` with `$user->hasRole('organizer')` and `$user->role?->name === 'admin'` with `$user->hasRole('admin')`.

---

### Bug #8: `PricingWindowPolicy::ownsEvent` — no fallback when `event->organizer` is not eager-loaded; misses `userId` fallback
- **Severity**: High
- **Files**: `backend/app/Features/Pricing/Policies/PricingWindowPolicy.php:25-34`
- **Root cause**: `ownsEvent` accesses `$event->organizer->user_id` without checking if the relation is loaded, and does not fall back to `$event->organizer->userId`.
- **Exact failing condition**: If the `organizer` relation is not loaded on `$event`, accessing `$event->organizer` triggers a lazy-load query. If the relation is null (organizer soft-deleted), the check returns `false` even if the user is the organizer owner. The comparison also only checks `user_id` and does not fall back to `userId`.
- **Impact**: PricingWindowPolicy may return `false` for legitimate organizers in edge cases.
- **Fix**: Use `$event->organizer?->user_id === $user->id || $event->organizer?->userId === $user->id` with a `whereHas` fallback when the relation is not loaded.

---

### Bug #9: `EventPolicy::viewAny` — unnecessary DB query even when relationship is loaded
- **Severity**: Medium
- **Files**: `backend/app/Policies/EventPolicy.php:11-14, 34-37`
- **Root cause**: `$user->organizer()->exists()` calls the query builder (not the loaded relation). Calling `->exists()` on a `HasOne` relation always issues `SELECT EXISTS(...)` against the database, even if `$user->relationLoaded('organizer')` is true.
- **Exact failing condition**: Every call to `Gate::authorize('viewAny', Event::class)` or `Gate::authorize('create', Event::class)` issues an unnecessary `SELECT EXISTS` query.
- **Impact**: Performance — one extra query per request on event listing pages.
- **Fix**: Add `relationLoaded('organizer')` short-circuit before the `->exists()` call.

---

### Bug #10: `AuditLogsTable` component references non-existent fields
- **Severity**: High
- **Files**: `frontend/src/features/compliance/components/AuditLogsTable.jsx:40-41`
- **Root cause**: Line 40: `{l.entity ?? '—'}` — `entity` does not exist on the `AuditLog` type. The equivalent field is `targetType`. Line 41: `{l.created_at ?? '—'}` — `created_at` does not exist. The type has `createdAt`.
- **Exact failing condition**: Even after fixing the backend to return camelCase, these fields will still be `undefined` because they reference wrong property names.
- **Impact**: Audit log table always shows dashes for entity and date columns.
- **Fix**: Change `l.entity` → `l.targetType` and `l.created_at` → `l.createdAt`.

---

### Bug #11: `PricingWindow` restore test encodes buggy behavior
- **Severity**: Medium
- **Files**: `backend/tests/Feature/PricingEndpointTest.php:627-651`
- **Root cause**: The test `test_restoring_soft_deleted_window_checks_overlap` creates the window to restore with `is_active => false` and expects 409. This encodes the buggy behavior that restoring an inactive overlapping window should fail. The correct behavior is that restoring an inactive window should succeed (201), while restoring an active overlapping window should fail (409).
- **Exact failing condition**: Test passes but validates incorrect business logic.
- **Impact**: Test suite passes despite the bug; the test must be updated when the bug is fixed.
- **Fix**: Update the test to expect 201 when restoring an inactive overlapping window, and add a new test for restoring an active overlapping window expecting 409.

---

## Section B: Code Smells & Risk Patterns

---

### Smell #1: Overlap logic duplicated across create, update, and restore
- **Files**: `PricingWindowController.php:126-138, 224-237, 292-305`
- **Issue**: The same 10-line overlap query is copy-pasted three times. Any future change to the overlap algorithm must be applied in three places.
- **Fix**: Extract to a private method `hasOverlap($eventId, $categoryId, $startDate, $endDate, $excludeWindowId = null)`.

---

### Smell #2: SQLite-only overlap trigger — PostgreSQL relies solely on application-level checks
- **Files**: `backend/database/migrations/2026_09_16_000005_add_pricing_window_overlap_trigger.php:11-65`
- **Issue**: The overlap prevention trigger is only created when `Schema::getConnection()->getDriverName() === 'sqlite'`. On PostgreSQL, there is no database-level enforcement of the "one active window per tier per event" rule.
- **Impact**: Data integrity risk on production (PostgreSQL).
- **Fix**: Add a PostgreSQL partial unique index or exclusion constraint, or move the overlap enforcement to a model event observer that runs on both databases.

---

### Smell #3: `whereBetween` overlap query uses inclusive boundaries
- **Files**: `PricingWindowController.php:131-136, 230-235, 298-303`
- **Issue**: `whereBetween('start_date_time', [$startDate, $endDate])` is inclusive on both ends. If two windows share an exact boundary, they are considered overlapping.
- **Impact**: Prevents adjacent/back-to-back pricing windows for the same tier.

---

### Smell #4: Four distinct role-check patterns in use across the codebase
- **Files**: Multiple policies and controllers
- **Issue**: No centralized helper exists. Every controller reimplements its own variant. This makes it easy to miss a check path (as happened in WebhookPolicy and ApiKeyPolicy).
- **Fix**: Centralize role checking in `User::hasRole()` and use it consistently.

---

### Smell #5: `$with = ['organizer']` on Event model is a global eager-load that may mask soft-delete issues
- **Files**: `backend/app/Models/Event.php:24`
- **Issue**: Every `Event` query always eager-loads the organizer. If the organizer is soft-deleted, `$event->organizer` is `null` — the `whereHas` query in the controller will still correctly return `false`, but the policy's `$event->organizer->user_id` access will be `null` and the ownership check fails silently.
- **Impact**: EventPolicy::view and PricingWindowPolicy::ownsEvent will deny access for events whose organizer has been soft-deleted.
- **Fix**: Either remove the global `$with` and use explicit eager-loading where needed, or use `with(['organizer' => fn($q) => $q->withTrashed()])` when organizer data is needed for ownership checks.

---

### Smell #6: Inconsistent auth patterns across sibling controllers
- **Files**: Multiple controllers
- **Issue**: The project has three patterns: (a) `$this->authorize()` + Gate, (b) `Gate::forUser($user)`, (c) manual `$request->user()` checks. While all are now correct, a future maintainer adding a new controller under `bearer` might accidentally use pattern (a) and introduce a bug.
- **Fix**: Document the canonical pattern for bearer-protected routes.

---

### Smell #7: Duplicate `PricingWindowResource` classes
- **Files**: `backend/app/Http/Resources/PricingWindowResource.php` and `backend/app/Features/Pricing/Resources/PricingWindowResource.php`
- **Issue**: Two separate `PricingWindowResource` classes exist. If a developer updates one but not the other, API contract drift occurs silently.
- **Fix**: Remove the unused `app/Http/Resources/PricingWindowResource.php` or consolidate into a single class.

---

### Smell #8: Unused `PricingWindowPolicy`
- **Files**: `backend/app/Features/Pricing/Policies/PricingWindowPolicy.php`
- **Issue**: The policy exists with `view`, `create`, `update`, `delete`, `restore`, and `forceDelete` methods, but the controller never calls `$this->authorize()` or `Gate::authorize()`. All authorization is done via custom inline methods.
- **Impact**: The policy is dead code and will drift from the controller's actual authorization logic.
- **Fix**: Either wire the policy into the controller or remove the unused policy file.

---

### Smell #9: `authorizeEventOwner` and `authorizeEventAccess` are identical
- **Files**: `PricingWindowController.php:26-48, 54-77`
- **Issue**: Both methods contain identical role-checking and ownership-verification logic.
- **Fix**: Extract the shared logic into a single method and use it everywhere.

---

### Smell #10: Soft-delete column naming inconsistency
- **Files**: `backend/app/Models/Organizer.php:16,66`, `backend/app/Features/OrganizerProfile/Models/OrganizerProfile.php:17,58`, `backend/app/Models/Event.php:15`
- **Issue**: Both `Organizer` and `OrganizerProfile` map to the same `organizers` table but both declare `const DELETED_AT = 'deletedAt'`. The migration `2026_07_04_000300_create_organizers_table.php` does not add any `deleted_at` or `deletedAt` column.
- **Impact**: If `deletedAt` column does not exist in the database, any call to `$organizer->delete()` will silently fail or throw an SQL error.
- **Fix**: Ensure the database column name matches the model's `DELETED_AT` constant, or align both to `deleted_at`.

---

## Section C: Test Gaps

---

### Gap #1: No test for restoring an inactive window that overlaps with an active window
- **Severity**: High
- **File**: `backend/tests/Feature/PricingEndpointTest.php`
- **Issue**: The existing test `test_restoring_soft_deleted_window_checks_overlap` (line 627) expects 409 for an **inactive** window. This test encodes the buggy behavior. A correct test would verify that restoring an **inactive** overlapping window succeeds (201), while restoring an **active** overlapping window fails (409).

---

### Gap #2: No test for concurrent create overlap race condition
- **Severity**: Medium
- **File**: `backend/tests/Feature/PricingEndpointTest.php`
- **Issue**: Two simultaneous POST requests with overlapping dates could both pass the `exists()` check before either commits. The SQLite trigger protects SQLite, but PostgreSQL has no such protection.

---

### Gap #3: No integration tests for auth state transitions
- **Severity**: Medium
- **File**: Multiple test files
- **Issue**: No test verifies the full flow: login → token → bearer → Gate → authorization. The `AUDIT_PROMPT.md` specifically warns that `BearerTokenAuth` doesn't call `Auth::login()`, so Gate user resolver may return null.

---

### Gap #4: No tests for bearer auth + Gate interaction
- **Severity**: Medium
- **File**: Multiple test files
- **Issue**: The `OrganizerPayoutController`, `AdminSettlementController`, and `EmailTemplateController` were recently fixed. Tests should verify these endpoints work with actual Bearer tokens (not just `actingAs($user, 'sanctum')`).

---

### Gap #5: Missing negative tests for AnalyticsController
- **Severity**: Medium
- **File**: `backend/tests/Feature/` (no Analytics test file)
- **Issue**: No tests verify that `getSummary()` and `getDetailed()` reject non-owners.

---

### Gap #6: No contract tests validate field names or shapes
- **Severity**: Medium
- **File**: `backend/tests/Feature/ComplianceApiAuthorizationTest.php`
- **Issue**: Tests assert `assertJsonStructure(['data', 'meta'])` for index and export, and `assertJsonStructure(['data'])` for show. They do not assert that field names inside `data` are camelCase, that `performedByName` is present, or that pagination `meta` contains `total`, `page`, `perPage`.

---

### Gap #7: No test for `GET /api/events/{event}` public show endpoint
- **Severity**: Medium
- **File**: `backend/tests/Feature/PublicEventBrowsingTest.php`
- **Issue**: Route exists but has no feature test. Only pricing is tested for public event access.

---

### Gap #8: Many admin routes lack tests
- **Severity**: High
- **File**: `backend/routes/admin.php`
- **Issue**: 11 admin routes defined but only 3 have feature tests (`roles` CRUD + `ticket purge`). Missing routes include `/admin/dashboard`, `/admin/users`, `/admin/events`, `/admin/payments/reconciliation`, `/admin/tickets`, permission management, audit-log, permission-requests.

---

### Gap #9: No Event restore endpoint test
- **Severity**: High
- **File**: `backend/tests/Feature/OrganizerEventControllerTest.php`
- **Issue**: The test file tests soft-delete (line 268) but has no test for restoring a soft-deleted event.

---

### Gap #10: No test for organizer resolution via all three paths
- **Severity**: Medium
- **File**: `backend/tests/Feature/OrganizerEventControllerTest.php`
- **Issue**: Tests create users with `['role' => 'organizer']` but do not verify all three authorization paths (role_id pivot, legacy `role` column, organizer profile).

---

## Section D: Quick Wins

---

### Win #1: Remove `|| true` from `OrganizerProfileController::auditLog`
- **File**: `backend/app/Features/OrganizerProfile/Controllers/OrganizerProfileController.php:430`
- **Change**: Remove `|| true` literal.

---

### Win #2: Remove capitalized `'Organizer'` role check
- **File**: `backend/app/Features/OrganizerProfile/Controllers/OrganizerProfileController.php:132, 163, 226`
- **Change**: Remove `|| $user->hasRole('Organizer')` — the capitalized variant will never match a lowercase role name.

---

### Win #3: Fix `AuditLogsTable.jsx` field references
- **File**: `frontend/src/features/compliance/components/AuditLogsTable.jsx:40-41`
- **Change**: Change `l.entity` → `l.targetType` and `l.created_at` → `l.createdAt`.

---

### Win #4: Remove duplicate type definitions in `audit.ts`
- **File**: `frontend/src/features/compliance/types/audit.ts:96-108, 194-206`
- **Change**: `AuditLogListResponse` and `AuditLogPaginatedListResponse` are each defined twice in the same file. Remove the duplicates.

---

### Win #5: Remove dead validation fields from `AuditLogIndexRequest`
- **File**: `backend/app/Features/Compliance/Requests/AuditLogIndexRequest.php:19-20`
- **Change**: Remove `entity` and `entity_id` validation rules, or implement them in `AuditLogService::filter()`.

---

### Win #6: Fix `OfflineSyncController` organizer query
- **File**: `backend/app/Features/OfflineSync/Controllers/OfflineSyncController.php:97-98, 146-147`
- **Change**: Replace `where('organizer_id', $user->id)` with `whereHas('organizer', fn($q) => $q->where('user_id', $user->id))`.

---

### Win #7: Add `userId` fallback to `OrganizerPolicy`
- **File**: `backend/app/Policies/OrganizerPolicy.php:18`
- **Change**: Replace `return $user->id === $organizer->user_id;` with `return $user->id === ($organizer->user_id ?? $organizer->userId);`.

---

### Win #8: Change Analytics routes from `auth:sanctum` to `bearer`
- **File**: `backend/app/Features/Analytics/Routes/api.php:7`
- **Change**: Change `auth:sanctum` to `bearer` for consistency with all other organizer routes.

---

### Win #9: Fix misleading `ResetAuthState` comment
- **File**: `backend/app/Http/Middleware/ResetAuthState.php:27-29`
- **Change**: Update the comment to accurately describe that `Auth::user()` is NOT available after `Auth::forgetGuards()` for bearer routes.

---

### Win #10: Fix `test_create_pricing_window_returns_403_for_other_users_event`
- **File**: `backend/tests/Feature/PricingEndpointTest.php:228-247`
- **Change**: The test name says "returns 403" but the actual assertion is 422. Either rename the test or fix the assertion to actually test 403.

---

---

## Section F: Soft-Delete Handling Findings

---

### Bug #19: PricingWindow implicit bindings 404 for soft-deleted windows
- **Severity**: High
- **Files**: `backend/app/Features/Pricing/Controllers/PricingWindowController.php:159, 179, 260`
- **Root cause**: Laravel's implicit route model binding uses `findOrFail()` under the hood, which excludes soft-deleted models. Since `PricingWindow` uses `SoftDeletes`, any soft-deleted pricing window will return a 404 on `show()`, `update()`, and `destroy()`. There is no way to access a soft-deleted window via these bindings.
- **Exact failing condition**: Soft-deleted pricing windows return 404 on show/update/delete.
- **Impact**: Organizers cannot inspect or re-delete soft-deleted pricing windows.
- **Fix**: Change implicit bindings to accept `$id` and use `PricingWindow::withTrashed()->findOrFail($id)` where needed.

---

### Bug #20: EmailTemplate implicit bindings 404 for soft-deleted templates
- **Severity**: High
- **Files**: `backend/app/Features/EmailNotifications/Controllers/EmailTemplateController.php:48, 55, 64, 77`
- **Root cause**: `EmailTemplate` uses `SoftDeletes`. All four endpoints use implicit binding `EmailTemplate $emailTemplate`. Soft-deleted templates will 404 on show, update, destroy, and sendTest. There is no restore endpoint for email templates.
- **Exact failing condition**: Soft-deleted email templates return 404 on all operations.
- **Impact**: Administrators cannot inspect or restore soft-deleted email templates.
- **Fix**: Change parameter to `$id` and manually load with `EmailTemplate::withTrashed()->findOrFail($id)` where needed.

---

### Bug #21: No Event restore endpoint
- **Severity**: High
- **Files**: `backend/app/Http/Controllers/Organizer/EventController.php` (missing method)
- **Root cause**: The `destroy()` method at line 344 soft-deletes events using `$locked->delete()` (line 376). However, there is no corresponding `restore()` method in `EventController` and no route for event restoration.
- **Exact failing condition**: Once an event is soft-deleted, organizers cannot restore it via API.
- **Impact**: Missing feature — events cannot be restored once deleted.
- **Fix**: Add a `restore()` method to `EventController` and a corresponding route.

---

### Bug #22: AdminEventController excludes soft-deleted events
- **Severity**: Medium
- **Files**: `backend/app/Features/admin/Controllers/AdminEventController.php:13`
- **Root cause**: `Event::query()` excludes soft-deleted events by default. Admin users cannot see deleted events in the admin event list.
- **Exact failing condition**: Admin event list does not include soft-deleted events.
- **Impact**: Administrators cannot view or restore deleted events.
- **Fix**: Use `Event::withTrashed()` if admins need to view deleted events.

---

### Smell #13: Organizer model uses custom deletedAt column
- **Severity**: Low
- **Files**: `backend/app/Models/Organizer.php:16`
- **Root cause**: The `Organizer` model uses `deletedAt` (camelCase) instead of the Laravel default `deleted_at`. Any code that manually queries `whereNull('deleted_at')` on organizers will not work.
- **Impact**: Risk of manual query mismatch.
- **Fix**: Ensure all queries on organizers use `deletedAt` or align the model to use `deleted_at`.

---

---

## Section G: Routes & Middleware Findings

---

### Bug #23: PricingWindowController `$eventId` null due to `{event}` vs `$eventId` mismatch
- **Severity**: Critical
- **Files**: `backend/app/Features/Pricing/Controllers/PricingWindowController.php:82, 107, 159, 179, 260, 285, 328`
- **Root cause**: Route parameter `{event}` doesn't match controller parameter `$eventId`. Laravel resolves non-type-hinted parameters by exact name match. Since the route provides `{event}` but the method expects `$eventId`, `$eventId` is always `null`. The `restore` method also has `$id` parameter but route parameter is `{pricing_window}`.
- **Exact failing condition**: All pricing window operations receive `$eventId = null` and `$id = null`, causing orphaned windows, wrong queries, and 404s.
- **Impact**: Every organizer pricing window operation is broken — creates orphaned windows, returns 404s for show/update/destroy.
- **Call chain**: `routes/api.php:114-115` → `PricingWindowController::index(82)` → `$eventId = null` → `PricingWindow::forEvent(null)` → empty/wrong results
- **Fix**: Rename route parameter to `{eventId}` or rename controller parameter to `$event`, or add explicit route model binding.

---

### Bug #24: EventController `$id` null due to `{event}` vs `$id` mismatch
- **Severity**: Critical
- **Files**: `backend/app/Http/Controllers/Organizer/EventController.php:176, 231, 344, 417`
- **Root cause**: `Route::apiResource('events', ...)` inside `prefix('organizer')` generates routes with `{event}` parameter, but controller methods expect `$id`. `Event::find(null)` returns `null`, causing 404 for every request.
- **Exact failing condition**: All organizer event show/update/destroy/banner operations receive `$id = null` and return 404.
- **Impact**: Every organizer event management operation is broken — all return 404.
- **Call chain**: `routes/api.php:102` → `EventController@show(176)` → `Event::find(null)` → 404
- **Fix**: Change controller parameter from `$id` to `$event` or configure explicit route model binding.

---

### Bug #25: PermissionController `$roleId` null due to `{role}` vs `$roleId` mismatch
- **Severity**: Critical
- **Files**: `backend/app/Http/Controllers/Admin/PermissionController.php:38`, `backend/routes/api.php:160`
- **Root cause**: Route parameter `{role}` doesn't match controller parameter `$roleId`. Laravel matches non-type-hinted parameters by name, so `$roleId` will be `null`. The method then calls `Role::findOrFail(null)` which throws a `ModelNotFoundException` → 404.
- **Exact failing condition**: `PUT /api/admin/roles/123/permissions` → `$roleId = null` → 404
- **Impact**: Admin cannot update role permissions.
- **Call chain**: `routes/api.php:160` → `PermissionController@updateRolePermissions(38)` → `Role::findOrFail(null)` → 404
- **Fix**: Rename controller parameter to `$role` or change route parameter to `{roleId}`.

---

### Bug #26: Bearer token authentication broken on most routes
- **Severity**: Critical
- **Files**: 19 route files using `auth:sanctum` instead of `bearer`
- **Root cause**: `auth:sanctum` middleware only checks Sanctum sessions/Personal Access Tokens. The custom Bearer token flow (from `POST /auth/login`) stores tokens in the `sessions` table, not as Sanctum PATs. The `BearerTokenAuth` middleware (aliased as `bearer`) is the only middleware that handles both Sanctum and custom Bearer tokens.
- **Exact failing condition**: Any client using Bearer tokens from the login flow receives 401 on all routes protected by `auth:sanctum` only.
- **Impact**: Bearer token authentication is broken for checkout, payments, refunds, payouts, analytics, compliance, fraud detection, accessibility, localization, API keys, offline sync, push notifications, check-in, email templates, and QR code ticketing.
- **Affected route files**: `routes/api.php` (lines 141, 154, 168), `app/Features/Payment/Routes/api.php`, `app/Features/Checkout/Routes/api.php`, `app/Features/Refunds/Routes/api.php`, `app/Features/Payouts/Routes/api.php`, `app/Features/Analytics/Routes/api.php`, `app/Features/Compliance/Routes/api.php`, `app/Features/Fraud/Routes/api.php`, `app/Features/Accessibility/Routes/api.php`, `app/Features/Localization/Routes/api.php`, `app/Features/ApiKeys/Routes/api.php`, `app/Features/ApiKeys/Routes/developer-api.php`, `app/Features/OfflineSync/Routes/api.php`, `app/Features/PushNotifications/Routes/api.php`, `app/Features/CheckIn/Routes/api.php`, `app/Features/EmailNotifications/Routes/api.php`, `app/Features/QRCodeTicketing/Routes/api.php`
- **Fix**: Replace `auth:sanctum` with `bearer` on all routes that should support both authentication methods, or add `bearer` as a secondary middleware alongside `auth:sanctum`.

---

### Bug #27: `SubstituteBindings` not explicitly registered in Laravel 11 custom config
- **Severity**: Medium
- **Files**: `backend/bootstrap/app.php:16-61`
- **Root cause**: The project uses a custom middleware configuration in `bootstrap/app.php`. The default API middleware stack that includes `SubstituteBindings` is overridden. There is no explicit `SubstituteBindings` registration in the custom configuration.
- **Exact failing condition**: Route model binding for controller type-hints may not work correctly in all cases.
- **Impact**: Implicit route model binding may fail for some routes.
- **Fix**: Add `SubstituteBindings` to the custom middleware stack in `bootstrap/app.php`.

---

### Smell #14: No `can:` middleware used anywhere
- **Severity**: Low (by design)
- **Files**: All route files
- **Issue**: Authorization is handled entirely through controller method logic (`Gate::forUser($request->user())->authorize()`), custom middleware (`role:admin`, `isAdmin`, `CheckRole`), and FormRequest `authorize()` methods. No `can:ability,model` middleware is used.
- **Impact**: Authorization is decentralized and may be inconsistent across routes.
- **Fix**: Consider using `can:` middleware for simple authorization checks to centralize policy enforcement.

---

## Appendix: Full Finding Summary

| # | Bug/Smell/Gap | Severity | Category |
|---|---|---|---|
| 1 | Restore overlap blocks inactive windows | High | Pricing |
| 2 | Frontend-backend snake_case vs camelCase drift | Critical | Frontend Contract |
| 3 | Analytics endpoints missing authorization | Critical | Auth |
| 4 | `auditLog` `$isOrganizer` always true | Critical | Auth |
| 5 | `OfflineSyncController` type mismatch | High | Auth |
| 6 | `OrganizerPolicy` misses `userId` fallback | High | Auth |
| 7 | `WebhookPolicy`/`ApiKeyPolicy` miss role checks | High | Auth |
| 8 | `PricingWindowPolicy::ownsEvent` edge cases | High | Auth |
| 9 | `EventPolicy::viewAny` unnecessary query | Medium | Performance |
| 10 | `AuditLogsTable` wrong field names | High | Frontend |
| 11 | Restore test encodes buggy behavior | Medium | Tests |
| 12 | Overlap logic duplicated | Medium | Code Smell |
| 13 | SQLite-only overlap trigger | Medium | Database |
| 14 | Inclusive `whereBetween` boundaries | Low | Design |
| 15 | Four role-check patterns | Medium | Code Smell |
| 16 | Global `$with = ['organizer']` | Medium | Design |
| 17 | Inconsistent auth patterns | Medium | Code Smell |
| 18 | Duplicate `PricingWindowResource` | Medium | Code Smell |
| 19 | Unused `PricingWindowPolicy` | Low | Dead Code |
| 20 | Identical authorization helpers | Low | Code Smell |
| 21 | Soft-delete column naming | Medium | Database |
| 22 | No inactive window restore test | High | Tests |
| 23 | No race condition test | Medium | Tests |
| 24 | No auth transition integration test | Medium | Tests |
| 25 | No bearer + Gate interaction test | Medium | Tests |
| 26 | No Analytics negative tests | Medium | Tests |
| 27 | No contract tests for field shapes | Medium | Tests |
| 28 | No public event show test | Medium | Tests |
| 29 | Many admin routes untested | High | Tests |
| 30 | No Event restore test | High | Tests |
| 31 | No three-path organizer test | Medium | Tests |
| 32 | `|| true` dead code | Low | Quick Win |
| 33 | Capitalized `'Organizer'` check | Low | Quick Win |
| 34 | Wrong field names in table | Low | Quick Win |
| 35 | Duplicate type definitions | Low | Quick Win |
| 36 | Dead validation fields | Low | Quick Win |
| 37 | Broken organizer query | Low | Quick Win |
| 38 | Missing `userId` fallback | Low | Quick Win |
| 39 | Wrong auth middleware | Low | Quick Win |
| 40 | Misleading comment | Low | Quick Win |
| 41 | Test asserts wrong status | Low | Quick Win |
| 42 | Pricing window overlap trigger missing PostgreSQL | Critical | Database |
| 43 | Order delete trigger missing PostgreSQL | Critical | Database |
| 44 | Calendar view SQLite-specific syntax | Critical | Database |
| 45 | Calendar view PostgreSQL-specific syntax | Critical | Database |
| 46 | FraudEvent SoftDeletes contradiction | High | Database |
| 47 | Missing hasIndex() guard | Medium | Database |
| 48 | triggerExists() PostgreSQL-only | Medium | Database |
| 49 | Inconsistent primary key types | Medium | Database |
| 50 | Unquoted identifiers in raw SQL | Medium | Database |
| 51 | PricingWindow implicit bindings 404 for soft-deleted windows | High | Soft Delete |
| 52 | EmailTemplate implicit bindings 404 for soft-deleted templates | High | Soft Delete |
| 53 | No Event restore endpoint | High | Soft Delete |
| 54 | AdminEventController excludes soft-deleted events | Medium | Soft Delete |
| 55 | Organizer model uses custom deletedAt column | Low | Soft Delete |
| 56 | PricingWindowController parameter mismatch causes null `$eventId` | Critical | Routes |
| 57 | EventController parameter mismatch causes null `$id` | Critical | Routes |
| 58 | PermissionController parameter mismatch causes null `$roleId` | Critical | Routes |
| 59 | Bearer token auth broken on 19 route files | Critical | Routes |
| 60 | `SubstituteBindings` not explicitly registered | Medium | Routes |
| 61 | No `can:` middleware used anywhere | Low | Routes |

---

## Section E: Database & Migration Findings

---

### Bug #12: Pricing window overlap trigger missing for PostgreSQL
- **Severity**: Critical
- **Files**: `backend/database/migrations/2026_09_16_000005_add_pricing_window_overlap_trigger.php:11-65`
- **Root cause**: The overlap prevention trigger is only created when `Schema::getConnection()->getDriverName() === 'sqlite'`. On PostgreSQL, there is no database-level enforcement of the "one active window per tier per event" rule. If a race condition or a direct DB write bypasses the application overlap check, PostgreSQL allows overlapping active windows.
- **Exact failing condition**: On PostgreSQL, two concurrent requests can both pass the `exists()` check before either commits, creating overlapping active windows.
- **Impact**: Data integrity risk on production (PostgreSQL). The SQLite trigger protects SQLite, but PostgreSQL has no such protection.
- **Fix**: Add a PostgreSQL partial unique index or exclusion constraint, or move the overlap enforcement to a model event observer that runs on both databases.

---

### Bug #13: Order delete trigger missing for PostgreSQL
- **Severity**: Critical
- **Files**: `backend/database/migrations/2026_08_03_190000_harden_step66_checkout_schema.php:167-206`
- **Root cause**: The `createOrderDeleteTicketVoidTrigger()` method only creates triggers for SQLite (lines 179-193) and MySQL (lines 195-205). There is no PostgreSQL branch. When an order is deleted on PostgreSQL, associated tickets are not automatically voided.
- **Exact failing condition**: On PostgreSQL, deleting an order does not void associated tickets.
- **Impact**: Data integrity risk on production (PostgreSQL). Tickets remain in an inconsistent state after order deletion.
- **Fix**: Add a PostgreSQL branch to the `createOrderDeleteTicketVoidTrigger()` method.

---

### Bug #14: Calendar view migration uses SQLite-specific syntax — fails on PostgreSQL
- **Severity**: Critical
- **Files**: `backend/database/migrations/2026_07_06_134100_create_calendar_availability_view.php:53`
- **Root cause**: The migration uses `DATE('now')` which is SQLite syntax. In PostgreSQL, this should be `CURRENT_DATE` or `DATE(NOW())`. The migration does not guard by driver.
- **Exact failing condition**: Running this migration on PostgreSQL throws a syntax error.
- **Impact**: Migration fails on PostgreSQL, blocking deployments.
- **Fix**: Add a driver check and use `CURRENT_DATE` for PostgreSQL, `DATE('now')` for SQLite.

---

### Bug #15: Calendar view refresh migration uses PostgreSQL-specific syntax — fails on SQLite
- **Severity**: Critical
- **Files**: `backend/database/migrations/2026_08_05_000100_refresh_calendar_views_for_current_schema.php:54`
- **Root cause**: The migration uses `CURRENT_DATE` which is PostgreSQL syntax. In SQLite, this works but may have different semantics. The migration does not guard by driver.
- **Exact failing condition**: Running this migration on SQLite may produce incorrect results or fail depending on SQLite version.
- **Impact**: Migration may fail or produce incorrect results on SQLite.
- **Fix**: Add a driver check and use `DATE('now')` for SQLite, `CURRENT_DATE` for PostgreSQL.

---

### Bug #16: FraudEvent model uses SoftDeletes but column was removed from database
- **Severity**: High
- **Files**: `backend/app/Features/Fraud/Models/FraudEvent.php:64`, `backend/database/migrations/2026_07_26_170923_remove_soft_deletes_from_fraud_events_table.php`
- **Root cause**: The `FraudEvent` model uses the `SoftDeletes` trait, but migration `2026_07_26_170923_remove_soft_deletes_from_fraud_events_table.php` removes the `deleted_at` column from the database. This means any call to `$fraudEvent->delete()` will throw an SQL error because the column does not exist.
- **Exact failing condition**: Calling `delete()` or `withTrashed()` on a `FraudEvent` model throws an SQL error.
- **Impact**: Runtime errors when attempting to soft-delete fraud events.
- **Fix**: Either restore the `deleted_at` column or remove the `SoftDeletes` trait from the `FraudEvent` model.

---

### Bug #17: Missing hasIndex() guard in pricing window composite indexes migration
- **Severity**: Medium
- **Files**: `backend/database/migrations/2026_09_16_000006_add_pricing_window_composite_indexes.php:11-14`
- **Root cause**: The migration directly creates indexes without checking if they already exist:
```php
Schema::table('pricing_windows', function (Blueprint $table) {
    $table->index(['event_id', 'ticket_category_id', 'is_active', 'deleted_at'], 'idx_pricing_windows_event_tier_active');
    $table->index(['event_id', 'priority', 'start_date_time'], 'idx_pricing_windows_prioritized');
});
```
- **Exact failing condition**: Running this migration twice throws an error because the indexes already exist.
- **Impact**: Migration fails on re-run, blocking deployments in environments where migrations are re-run.
- **Fix**: Add `Schema::hasIndex('pricing_windows', 'idx_pricing_windows_event_tier_active')` checks before creating indexes.

---

### Bug #18: triggerExists() only checks PostgreSQL
- **Severity**: Medium
- **Files**: `backend/database/migrations/2026_08_16_000022_harden_audit_logs_compliance_step77b.php:196-212`
- **Root cause**: The `triggerExists()` method only queries PostgreSQL's `pg_trigger` table. On SQLite or MySQL, it returns `false`, causing redundant trigger creation attempts.
- **Exact failing condition**: On SQLite/MySQL, the migration attempts to create triggers that already exist, potentially throwing errors.
- **Impact**: Migration may fail on SQLite/MySQL due to redundant trigger creation.
- **Fix**: Add driver-specific trigger existence checks for SQLite and MySQL.

---

### Smell #11: Inconsistent primary key types across tables
- **Severity**: Medium
- **Files**: Multiple migration files
- **Issue**: The codebase mixes `bigIncrements` and UUID primary keys:
  - `users.id` — UUID
  - `events.id` — bigIncrements
  - `organizers.id` — bigIncrements
  - `orders.id` — UUID
  - `tickets.id` — bigIncrements (original), UUID (Step 66 reconciliation)
  - `pricing_windows.id` — UUID
  - `payments.id` — bigIncrements
- **Impact**: Inconsistency makes it harder to reason about foreign keys and can lead to type mismatches (as seen in Bug #5).
- **Fix**: Standardize on UUIDs for all primary keys, or document the mixed strategy explicitly.

---

### Smell #12: Unquoted identifiers in raw SQL
- **Severity**: Medium
- **Files**: Multiple migration files
- **Issue**: Some raw SQL uses unquoted identifiers that may be reserved words or case-sensitive on PostgreSQL.
- **Impact**: Potential SQL errors on PostgreSQL when identifiers conflict with reserved words.
- **Fix**: Quote all identifiers in raw SQL using Laravel's `DB::getSchemaGrammar()->wrap()` or double-quote them manually.

---

### Quick Win #11: Fix calendar view migrations to be driver-aware
- **Files**: `2026_07_06_134100_create_calendar_availability_view.php`, `2026_08_05_000100_refresh_calendar_views_for_current_schema.php`
- **Change**: Add driver checks and use appropriate date expressions for each database.

---

### Quick Win #12: Add PostgreSQL branch to order delete trigger
- **File**: `2026_08_03_190000_harden_step66_checkout_schema.php:167-206`
- **Change**: Add a PostgreSQL branch to the `createOrderDeleteTicketVoidTrigger()` method.

---

### Quick Win #13: Add hasIndex() guards to pricing window indexes migration
- **File**: `2026_09_16_000006_add_pricing_window_composite_indexes.php`
- **Change**: Add `Schema::hasIndex()` checks before creating indexes.

---

### Quick Win #14: Fix triggerExists() to handle all drivers
- **File**: `2026_08_16_000022_harden_audit_logs_compliance_step77b.php:196-212`
- **Change**: Add SQLite and MySQL trigger existence checks.

---

### Quick Win #15: Resolve FraudEvent SoftDeletes contradiction
- **Files**: `backend/app/Features/Fraud/Models/FraudEvent.php`, `backend/database/migrations/2026_07_26_170923_remove_soft_deletes_from_fraud_events_table.php`
- **Change**: Either restore the `deleted_at` column or remove the `SoftDeletes` trait.

---

## Appendix: Full Finding Summary

| # | Bug/Smell/Gap | Severity | Category |
|---|---|---|---|
| 1 | Restore overlap blocks inactive windows | High | Pricing |
| 2 | Frontend-backend snake_case vs camelCase drift | Critical | Frontend Contract |
| 3 | Analytics endpoints missing authorization | Critical | Auth |
| 4 | `auditLog` `$isOrganizer` always true | Critical | Auth |
| 5 | `OfflineSyncController` type mismatch | High | Auth |
| 6 | `OrganizerPolicy` misses `userId` fallback | High | Auth |
| 7 | `WebhookPolicy`/`ApiKeyPolicy` miss role checks | High | Auth |
| 8 | `PricingWindowPolicy::ownsEvent` edge cases | High | Auth |
| 9 | `EventPolicy::viewAny` unnecessary query | Medium | Performance |
| 10 | `AuditLogsTable` wrong field names | High | Frontend |
| 11 | Restore test encodes buggy behavior | Medium | Tests |
| 12 | Overlap logic duplicated | Medium | Code Smell |
| 13 | SQLite-only overlap trigger | Medium | Database |
| 14 | Inclusive `whereBetween` boundaries | Low | Design |
| 15 | Four role-check patterns | Medium | Code Smell |
| 16 | Global `$with = ['organizer']` | Medium | Design |
| 17 | Inconsistent auth patterns | Medium | Code Smell |
| 18 | Duplicate `PricingWindowResource` | Medium | Code Smell |
| 19 | Unused `PricingWindowPolicy` | Low | Dead Code |
| 20 | Identical authorization helpers | Low | Code Smell |
| 21 | Soft-delete column naming | Medium | Database |
| 22 | No inactive window restore test | High | Tests |
| 23 | No race condition test | Medium | Tests |
| 24 | No auth transition integration test | Medium | Tests |
| 25 | No bearer + Gate interaction test | Medium | Tests |
| 26 | No Analytics negative tests | Medium | Tests |
| 27 | No contract tests for field shapes | Medium | Tests |
| 28 | No public event show test | Medium | Tests |
| 29 | Many admin routes untested | High | Tests |
| 30 | No Event restore test | High | Tests |
| 31 | No three-path organizer test | Medium | Tests |
| 32 | `|| true` dead code | Low | Quick Win |
| 33 | Capitalized `'Organizer'` check | Low | Quick Win |
| 34 | Wrong field names in table | Low | Quick Win |
| 35 | Duplicate type definitions | Low | Quick Win |
| 36 | Dead validation fields | Low | Quick Win |
| 37 | Broken organizer query | Low | Quick Win |
| 38 | Missing `userId` fallback | Low | Quick Win |
| 39 | Wrong auth middleware | Low | Quick Win |
| 40 | Misleading comment | Low | Quick Win |
| 41 | Test asserts wrong status | Low | Quick Win |
| 42 | Pricing window overlap trigger missing PostgreSQL | Critical | Database |
| 43 | Order delete trigger missing PostgreSQL | Critical | Database |
| 44 | Calendar view SQLite-specific syntax | Critical | Database |
| 45 | Calendar view PostgreSQL-specific syntax | Critical | Database |
| 46 | FraudEvent SoftDeletes contradiction | High | Database |
| 47 | Missing hasIndex() guard | Medium | Database |
| 48 | triggerExists() PostgreSQL-only | Medium | Database |
| 49 | Inconsistent primary key types | Medium | Database |
| 50 | Unquoted identifiers in raw SQL | Medium | Database |
| 51 | PricingWindow implicit bindings 404 for soft-deleted windows | High | Soft Delete |
| 52 | EmailTemplate implicit bindings 404 for soft-deleted templates | High | Soft Delete |
| 53 | No Event restore endpoint | High | Soft Delete |
| 54 | AdminEventController excludes soft-deleted events | Medium | Soft Delete |
| 55 | Organizer model uses custom deletedAt column | Low | Soft Delete |
| 56 | PricingWindowController parameter mismatch causes null `$eventId` | Critical | Routes |
| 57 | EventController parameter mismatch causes null `$id` | Critical | Routes |
| 58 | PermissionController parameter mismatch causes null `$roleId` | Critical | Routes |
| 59 | Bearer token auth broken on 19 route files | Critical | Routes |
| 60 | `SubstituteBindings` not explicitly registered | Medium | Routes |
| 61 | No `can:` middleware used anywhere | Low | Routes |

---

*Report generated by EventIQ Audit Process — 2026-09-18*
