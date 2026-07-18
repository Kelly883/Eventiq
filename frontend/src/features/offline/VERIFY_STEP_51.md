# Step 51 verification notes

Use these checks before building the offline ticket sync flow.

## Frontend SDK checks

- `idb` is available via `import { openDB } from 'idb'` in `services/offlineTicketStore.ts`.
- The offline ticket database is versioned and currently uses schema version 2. Version 2 adds lookup indexes for ticket code, event ID, and update timestamp before lookup-heavy sync logic is built on top.
- `axios` is centralized in `src/lib/api.ts` and reads `import.meta.env.VITE_API_BASE_URL`.
- `getDeviceToken()` persists the generated token in `localStorage` under `eventiqDeviceToken` and also exposes `window.EventiqDevice.getDeviceToken()` for manual browser-console verification.
- The shared axios request interceptor adds `X-Device-Token` to every request made through `api`.
- Backend offline-sync routes remain protected by `auth:sanctum`; `X-Device-Token` is validated as a 64-character pseudonymous identifier for sync attribution/idempotency only, not as an authentication credential.

Manual browser check:

```js
const first = window.EventiqDevice.getDeviceToken();
window.location.reload();
// after reload
const second = window.EventiqDevice.getDeviceToken();
console.assert(first === second, 'device token should persist across reloads');
```

Manual Network tab check:

```js
import('/src/lib/api.ts').then(({ api }) => api.get('/api/auth/me'));
```

Inspect the request headers and confirm `X-Device-Token` is present.

## Backend Sanctum checks

- `laravel/sanctum` is declared in `backend/composer.json`.
- `config/sanctum.php` is present.
- Offline-sync routes are inside `auth:sanctum`, and the controller uses `X-Device-Token` only as the idempotency `client_id`; mismatched body `client_id` values are rejected.
- `App\\Models\\User` uses `Laravel\\Sanctum\\HasApiTokens`.
- The `personal_access_tokens` migration exists.

Example tinker verification once backend dependencies and a local database are available:

```php
$user = App\\Models\\User::firstOrCreate(
    ['email' => 'sanctum-test@example.com'],
    ['name' => 'Sanctum Test', 'password' => bcrypt('password')]
);
$plainTextToken = $user->createToken('step-51-test')->plainTextToken;
```

Then call a protected route:

```bash
curl -H "Authorization: Bearer $plainTextToken" http://localhost:8000/api/auth/me
```

## Honest implementation notes

- Device-token uniqueness is good enough for sync ownership tracking because it starts from browser cryptographic randomness when available. It is not suitable as an authentication factor.
- `localStorage` is readable by any JavaScript running on the origin, so XSS could copy the token. The backend now explicitly keeps authorization in Sanctum bearer/session auth and treats the device token as a pseudonymous sync identifier only.
- IndexedDB is appropriate for large ticket datasets compared with `localStorage`; the ticket cache now includes indexes for ticket code, event ID, and update timestamp, which should be used instead of scanning all cached tickets.
- Before building sync on top, add integration tests against the real offline endpoints and validate the tinker/curl Sanctum flow in an environment with backend dependencies installed.
