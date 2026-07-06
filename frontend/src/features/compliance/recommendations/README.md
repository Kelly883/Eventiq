# Compliance feature recommendations (before implementing real logic)

1. Reuse the existing backend `App\Models\AuditLog` model to avoid duplicating audit-log representations.
2. Implement filtering + pagination in `AuditLogController` based on request validators.
3. Add async report generation persistence (status + download link) so the UI can poll.
4. Consolidate export formats and ensure audit trail logging for every export/report action.

