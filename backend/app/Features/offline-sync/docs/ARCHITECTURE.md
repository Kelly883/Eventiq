# OfflineSync architecture (skeleton)

## Separation of concerns
### Infrastructure (generic)
- Operation inbox/outbox storage
- Idempotency key management
- Retry/backoff + status tracking
- Sync engine orchestration
- Conflict resolution primitives

### Domain workflows (specific)
- What mutation types are queueable/syncable
- How payload maps to domain-side writes
- Expected conflict policy per domain mutation

## Idempotency model
- Each queued operation includes:
  - `client_id`
  - `op_type` (e.g., `admin.user.update`)
  - `entity_id`
  - `client_mutation_id` (UUID from client)
- Effective idempotency key: `(client_id, op_type, entity_id, client_mutation_id)`

Sync request replay:
- If idempotency key already applied -> return `status=applied` and do **not** re-run side effects.

## Conflict resolution model
- Every syncable entity write uses an `expected_revision` (or similar).
- Server applies update only if revision checks pass.
- Otherwise returns `status=conflict` and may include `server_state` to rebase.

## Notification routing
- Any operation that would trigger notifications is treated as a **single domain mutation**.
- Notification enqueue/delivery is performed during the idempotent apply step so retries cannot duplicate notifications.

