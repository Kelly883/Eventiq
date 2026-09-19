# Comprehensive Bug & Code Health Audit Prompt — EventIQ

You are auditing a Laravel + TypeScript event management platform for correctness, security, and consistency. The project follows a modular feature layout under `backend/app/Features/*` and a TypeScript frontend under `frontend/`. You must identify bugs, broken code paths, mismatched contracts, and risky patterns. Do NOT modify any code unless explicitly asked to propose a fix; this prompt is read-only diagnostic.

---

## 1. Project Context to Internalize

- **Backend**: Laravel (PHP 8.2), PostgreSQL/SQLite, Sanctum tokens, custom `BearerTokenAuth` middleware.
- **Frontend**: TypeScript, React/Next.js, feature-based modules under `frontend/src/features/*`.
- **Auth nuance**: `BearerTokenAuth` sets the user via `$request->setUserResolver(...)`. It does **not** call `Auth::login()` or set the guard user. Therefore `request()->user()` works, but `Auth::user()` / `Gate::` user resolver may return `null` unless a policy is called through `Gate::forUser($request->user())` or the controller uses request-based helpers.
- **Soft deletes**: Several models use `SoftDeletes`. Routes/actions that need trashed records must use `withTrashed()` on both the route binding/model query.
- **Role system**: Users may have roles via `role_id`, pivot `roles` table, legacy `role` string column, or attached `organizer` profile. Authorization must check all paths.
- **Testing**: PHPUnit + Laravel feature tests. Some tests use `actingAs($user, 'sanctum')` or bearer headers.

---

## 2. Priority Audit Areas

### A. Authorization / Authentication Mismatches
- Search for `$this->authorize(...)` inside controllers/middleware that run under `BearerTokenAuth`.
- Determine whether each authorization path goes through Laravel Gate (`Auth::user()`-based) or request-based checks.
- Flag any controller action where `authorize()` will fail because the Gate user resolver returns `null` under bearer auth.
- Verify all restore/delete/update/create endpoints use the same authorization mechanism as their siblings in the same controller.
- Check `Gate::forUser($request->user())` usage: is it consistent? Are there places that should use it but call `Gate::authorize()` directly?

### B. Soft-Delete / Trashed Binding Issues
- Identify routes that operate on soft-deleted models but lack `withTrashed()` on route bindings or model queries.
- Check restore endpoints: does the controller load the trashed model before authorizing? Does the route binding itself respect trashed records?
- Verify that `findOrFail($id)` vs route-model binding yields the same model state for deleted records.

### C. Pricing / Ticketing Overlap Logic
- Review `PricingWindow` overlap checks during create, update, activate, and restore.
- Confirm overlap queries exclude the current window (`where('id', '!=', $window->id)`) when appropriate.
- Check whether restore overlap logic incorrectly gates on `$window->is_active` before the window is actually restored (should check the *post-restore* state or the stored flag consistently).
- Verify `ticket_category_id` references match the actual column name across models, migrations, factories, and resources.

### D. Event Ownership & Organizer Resolution
- Trace every ownership check (`ownsEvent`, `authorizeEventOwner`, `EventPolicy`).
- Ensure organizer lookup covers: `role_id` pivot, legacy `role` column, and `organizer` relationship.
- Check for N+1 queries in loops over events/windows.
- Verify `organizer_id` on `events` matches `user_id` on `organizers` and that foreign keys exist.

### E. Route / Middleware Stack Integrity
- For each route in `routes/api.php`, list the exact middleware stack and note whether `can:ability,model` middleware is used together with controller `authorize()` calls (potential double-auth or mismatched user).
- Check that `SubstituteBindings` runs before authorization so bound models are available.
- Verify throttle middleware names exist and are spelled correctly.
- Check route parameter names match controller method signatures and policy expectations.

### F. Frontend–Backend Contract Drift
- Compare API response shapes in controllers/resources with what the frontend hooks/components consume.
- Look for renamed fields, missing fields, or changed status codes that the frontend does not handle.
- Verify pagination, filtering, and error response structures match across endpoints.

### G. Database & Migration Safety
- Check for raw SQL or triggers that behave differently on SQLite vs PostgreSQL.
- Verify index/foreign-key creation is guarded (`Schema::hasTable`, `Schema::hasColumn`, `Schema::hasIndex`) and quoted for PostgreSQL.
- Look for migrations that assume `bigIncrements` / `uuid` inconsistently.
- Verify `SoftDeletes` column names are `deleted_at` unless explicitly changed.

### H. Test Suite Health
- Run the full test suite and note any flaky or slow tests.
- Identify tests that pass for the wrong reasons (e.g., asserting on seeded data that doesn't reflect real logic).
- Check for tests that use `actingAs($user, 'sanctum')` vs bearer headers inconsistently with production auth.
- Verify factories match model fillables and database defaults.

---

## 3. Systematic Investigation Steps

For each file or feature you examine, report:

1. **File path & line numbers** of any suspicious code.
2. **What the code does** vs **what it should do**.
3. **Exact failing condition** if a bug is found (boolean expression, null dereference, missing binding, etc.).
4. **Impact**: 403/404/500, data leak, silent failure, performance issue.
5. **Call chain**: how the bug is reached from the route.
6. **Fix suggestion** (brief, do not apply unless asked).

---

## 4. Targeted Grep Searches to Run

Run these from the `backend/` directory:

```bash
# 1. All authorize() calls — compare with BearerTokenAuth context
grep -rn "\$this->authorize(" app/ | grep -v "Diagnostic"

# 2. All Gate::authorize / Gate::forUser / Gate::raw calls
grep -rn "Gate::" app/ routes/ bootstrap/ config/ | grep -v "Diagnostic"

# 3. All withTrashed usage — find missing ones
grep -rn "withTrashed" app/ routes/ | grep -v "Diagnostic"

# 4. All restore() methods
grep -rn "function restore(" app/ | grep -v "Diagnostic"

# 5. All overlap / exists() queries on pricing windows
grep -rn "overlap\|whereBetween.*start_date\|whereBetween.*end_date" app/ | grep -v "Diagnostic"

# 6. All event ownership checks
grep -rn "ownsEvent\|authorizeEventOwner\|whereHas('organizer'" app/ | grep -v "Diagnostic"

# 7. All role checks — ensure consistency
grep -rn "hasRole(" app/ | grep -v "Diagnostic"

# 8. All route model bindings and middleware
cat routes/api.php

# 9. Frontend API calls — look for mismatched paths or expected fields
grep -rn "fetch\|axios\|api/" frontend/src/ | head -80

# 10. All SoftDeletes usage
grep -rn "SoftDeletes" app/ | grep -v "Diagnostic"
```

---

## 5. Deliverables

Produce a structured report with:

### Section A: Confirmed Bugs
For each bug:
- **Title**: one-line summary
- **Severity**: Critical / High / Medium / Low
- **File(s)**: path + line numbers
- **Root cause**: exact code pattern causing the bug
- **Reproduction**: minimal steps or test case
- **Call chain**: route → middleware → controller → policy/model
- **Fix**: minimal change to resolve

### Section B: Code Smells & Risk Patterns
- Inconsistent auth patterns across sibling controller methods
- Missing `withTrashed()` on soft-delete endpoints
- Duplicate overlap logic that could drift
- Raw SQL / trigger differences across databases
- Frontend contracts that are not validated by tests

### Section C: Test Gaps
- Endpoints or features without feature tests
- Missing negative tests (403/404/422)
- Missing integration tests for auth state transitions (login → token → bearer → Gate)

### Section D: Quick Wins
- Typos, dead code, unused imports, mismatched variable names

---

## 6. Execution Rules

- **Do not modify any files** unless the user explicitly requests a fix.
- **Do not run destructive commands** (`git reset --hard`, `DROP`, etc.).
- **Do not commit or push**.
- Prefer `grep`, `rg`, `sed -n`, `php artisan test`, `npx tsc --noEmit`, and `node scripts/check-enum-sync.ts` for verification.
- If you need to inspect runtime values, add **temporary** diagnostics, capture the output, then **remove** the diagnostics before finishing.
- All findings must include exact file paths and line numbers.

---

## 7. Example Output Format

```markdown
### Bug #1: Restore endpoint returns 403 under bearer auth
- **Severity**: Critical
- **Files**: backend/app/Features/Pricing/Controllers/PricingWindowController.php:288
- **Root cause**: `$this->authorize('restore', $window)` invokes Laravel Gate, which resolves the user via `Auth::user()`. Under `BearerTokenAuth`, only `$request->setUserResolver()` is called; the guard user remains null, so Gate denies access before the policy runs.
- **Call chain**: `routes/api.php` → `BearerTokenAuth` → `PricingWindowController::restore()` → `Gate::authorize()` → `Gate::resolveUser()` → null → 403
- **Fix**: Replace `$this->authorize('restore', $window)` with `$this->authorizeEventOwner(request(), $eventId)`, matching the controller's existing request-based pattern.
```

---

Begin the audit now. Start with the backend authorization layer, then routes, then soft-delete handling, then pricing/ticketing logic, then frontend contracts. Report findings in the format above.
