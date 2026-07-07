# TODO

## i18n accessibility-localization folder alignment (frontend)
- [x] Confirm existing i18n setup (none found in current repo)
- [x] Create global i18n root under `frontend/src/i18n/`
- [x] Implement minimal translation utility (language-first, namespaces per feature)
- [x] Add example namespaces for existing features (admin, compliance)
- [ ] Ensure no `frontend/src/features/accessibility-localization` folder is created


## Backend routing alignment for “Users” (backend)
- [ ] Confirm where “Users” endpoints should live using:
  - [ ] `backend/routes/api.php`
  - [ ] `backend/routes/admin.php`
  - [ ] `backend/app/Features/*/Routes/api.php`
- [ ] Prefer `backend/app/Features/<Feature>/Controllers/...` + feature route files
- [ ] Avoid forcing `backend/app/Http/Controllers/Api/Users/...` unless routing convention matches

## Smoke test
- [ ] Run frontend build/lint
- [ ] Run backend composer install and any php artisan checks
- [ ] Ensure commands work in this Windows environment (avoid failing cmd chaining)

