# TODO - Step 55 (Auth DB tables)

- [ ] Decide final DB column naming strategy (snake_case vs camelCase)
- [ ] Create/adjust users migration to required fields/UUID PK
- [ ] Create/adjust sessions migration (UUID PK, userId FK cascade, token index, expiresAt, revokedAt)
- [ ] Create/adjust password_reset_tokens migration (UUID PK, userId FK cascade, token index, expiresAt, usedAt)
- [ ] Update User model to match new columns and hide passwordHash
- [ ] Update Session model to match new sessions schema
- [ ] Update PasswordResetToken model to match new schema
- [ ] Run `php artisan migrate`
- [ ] Verify tables/columns/indexes via DB inspection and/or artisan

