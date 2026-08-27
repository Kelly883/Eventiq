# auditLogs

## Description

PURPOSE: Immutable audit trail of all system transactions, user actions, and data modifications for regulatory compliance (GDPR, PCI-DSS, SOX)

FIELDS:
id: string
├── Purpose: UUID primary key, auto-generated
│   ├── Constraints: required
│   └── Mutable: no

userId: string
├── Purpose: Foreign key reference to users.id, indexed for user-level audit tracking
│   ├── Constraints: required
│   └── Mutable: no

action: string
├── Purpose: Action type: user_login, user_logout, user_suspended, event_created, event_approved, event_flagged, event_cancelled, payment_processed, payment_refunded, refund_requested, refund_approved, refund_rejected, payout_approved, payout_rejected, ticket_checked_in, ticket_voided, fraud_flagged, fraud_approved, admin_setting_changed, user_permission_changed, data_export_requested
│   ├── Constraints: required
│   └── Mutable: yes

targetType: string
├── Purpose: Type of entity affected: user, event, order, payment, refund, payout, ticket, fraud_event, admin_setting, permission
│   ├── Constraints: required
│   └── Mutable: yes

targetId: string
├── Purpose: ID of affected entity, indexed for fast lookups by target
│   └── Mutable: no

status: string
├── Purpose: Action status: success, failed, pending
│   ├── Constraints: required
│   └── Mutable: yes

ipAddress: string
├── Purpose: Client IP address (hashed for privacy), indexed for geolocation tracking
│   └── Mutable: yes

userAgent: string
├── Purpose: Client user agent string for device tracking
│   └── Mutable: yes

geolocation: object
├── Purpose: Geolocation data: {country, city, latitude, longitude} derived from IP address
│   └── Mutable: yes

requestData: object
├── Purpose: Request payload (sanitized to remove sensitive fields like passwords, card numbers)
│   └── Mutable: yes

responseData: object
├── Purpose: Response payload (sanitized, only for successful actions)
│   └── Mutable: yes

changedFields: object
├── Purpose: For update actions, object showing before/after values: {fieldName: {before: value, after: value}}
│   └── Mutable: yes

errorMessage: string
├── Purpose: Error message if status=failed
│   └── Mutable: yes

errorCode: string
├── Purpose: Machine-readable error code if status=failed
│   └── Mutable: yes

complianceClassification: string
├── Purpose: Data classification: public, internal, sensitive, pci_dss, gdpr_personal_data, sox_financial
│   ├── Constraints: required
│   └── Mutable: yes

retentionDate: timestamp
├── Purpose: Calculated date when this log should be deleted based on retention policy
│   ├── Constraints: required
│   └── Mutable: yes

metadata: object
├── Purpose: Additional context: {sessionId, requestId, correlationId, duration_ms, dataSize_bytes}
│   └── Mutable: yes

createdAt: timestamp
└── Purpose: Audit event creation timestamp, indexed for time-range queries
    ├── Constraints: required
    └── Mutable: no

LARAVEL POLICIES:
- Create: [AUTH_USER_ID] != null
- Read: [user_id] == [AUTH_USER_ID]
- Update: [user_id] == [AUTH_USER_ID]
- Delete: Soft delete (mark deleted, retain data)
- Immutable: createdAt, userId

LIFECYCLE:
- Created: set [SERVER_TIMESTAMP] for createdAt

## Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID primary key, auto-generated |
| userId | string | Yes | Foreign key reference to users.id, indexed for user-level audit tracking |
| action | string | Yes | Action type: user_login, user_logout, user_suspended, event_created, event_approved, event_flagged, event_cancelled, payment_processed, payment_refunded, refund_requested, refund_approved, refund_rejected, payout_approved, payout_rejected, ticket_checked_in, ticket_voided, fraud_flagged, fraud_approved, admin_setting_changed, user_permission_changed, data_export_requested |
| targetType | string | Yes | Type of entity affected: user, event, order, payment, refund, payout, ticket, fraud_event, admin_setting, permission |
| targetId | string | No | ID of affected entity, indexed for fast lookups by target |
| status | string | Yes | Action status: success, failed, pending |
| ipAddress | string | No | Client IP address (hashed for privacy), indexed for geolocation tracking |
| userAgent | string | No | Client user agent string for device tracking |
| geolocation | object | No | Geolocation data: {country, city, latitude, longitude} derived from IP address |
| requestData | object | No | Request payload (sanitized to remove sensitive fields like passwords, card numbers) |
| responseData | object | No | Response payload (sanitized, only for successful actions) |
| changedFields | object | No | For update actions, object showing before/after values: {fieldName: {before: value, after: value}} |
| errorMessage | string | No | Error message if status=failed |
| errorCode | string | No | Machine-readable error code if status=failed |
| complianceClassification | string | Yes | Data classification: public, internal, sensitive, pci_dss, gdpr_personal_data, sox_financial |
| retentionDate | timestamp | Yes | Calculated date when this log should be deleted based on retention policy |
| metadata | object | No | Additional context: {sessionId, requestId, correlationId, duration_ms, dataSize_bytes} |
| createdAt | timestamp | Yes | Audit event creation timestamp, indexed for time-range queries |

---
Generated by VisualPRD