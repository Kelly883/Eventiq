# TODO

## Auth / Token refresh + security hardening
- [x] Frontend: replace localStorage-based 401 handling in `frontend/src/lib/api.ts` with refresh+retry flow
- [ ] Frontend: centralize token handling and avoid hard logout on first 401
- [x] Backend: add refresh token endpoint (or sanctum session refresh) and corresponding auth logic
- [ ] Backend: if migrating to httpOnly cookies, add cookie-based token issuance + CORS/CSRF configuration

## Image processing + upload edge-case protection
- [ ] Identify upload endpoints and image resizing pipeline
- [ ] Add server-side validation: file type/size, decompression bomb limits, dimension bounds
- [ ] Add sharp processing safeguards: bounded dimensions, concurrency limits, async/background if needed

## React/UI
- [ ] Ensure admin pages use the updated api client and handle retried requests correctly

