# OfflineSync backend feature

This feature is being bootstrapped as an **infrastructure-first** offline sync engine.

Goals:
- **Idempotency**: offline replays are safe and do not duplicate side effects.
- **Conflict resolution**: deterministic application based on per-entity revision/version.
- **Maintainability**: separate offline infrastructure (queue/retry/storage/sync engine)
  from offline domain workflows (what operations get queued/synced).

See `backend/app/Features/OfflineSync/docs/` (to be added).

