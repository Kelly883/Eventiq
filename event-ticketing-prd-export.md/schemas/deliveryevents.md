# delivery_events

## Description

PURPOSE: Immutable audit trail of all ticket delivery attempts, tracking delivery method, status, timestamps, and error details for every ticket sent to customers

FIELDS:
id: string
├── Purpose: UUID primary key, auto-generated
│   ├── Constraints: required
│   └── Mutable: no

ticketId: string
├── Purpose: Foreign key reference to tickets.id, indexed for fast lookups
│   ├── Constraints: required
│   └── Mutable: no

orderId: string
├── Purpose: Foreign key reference to orders.id, indexed for order-level tracking
│   ├── Constraints: required
│   └── Mutable: no

userId: string
├── Purpose: Foreign key reference to users.id, indexed for user-level delivery history
│   ├── Constraints: required
│   └── Mutable: no

eventId: string
├── Purpose: Foreign key reference to events.id, indexed for event-level analytics
│   ├── Constraints: required
│   └── Mutable: no

deliveryMethod: string
├── Purpose: Delivery method: 'email', 'sms', 'dashboard' — determines which channel was used
│   ├── Constraints: required
│   └── Mutable: yes

recipientEmail: string
├── Purpose: Email address ticket was sent to (if method=email), hashed for privacy
│   └── Mutable: yes

recipientPhone: string
├── Purpose: Phone number ticket was sent to (if method=sms), hashed for privacy
│   └── Mutable: yes

status: string
├── Purpose: Delivery status: 'pending', 'sent', 'failed', 'bounced', 'viewed' — tracks delivery lifecycle
│   ├── Constraints: required
│   └── Mutable: yes

deliveryTimestamp: timestamp
├── Purpose: Exact timestamp when ticket was successfully delivered, null if not yet sent
│   └── Mutable: yes

viewedTimestamp: timestamp
├── Purpose: Timestamp when ticket was first viewed by recipient (if tracked), null if not viewed
│   └── Mutable: yes

attemptCount: integer
├── Purpose: Number of delivery attempts made, incremented on each retry
│   ├── Constraints: required
│   └── Mutable: yes

maxAttempts: integer
├── Purpose: Maximum delivery attempts allowed (default 3), after which delivery is abandoned
│   ├── Constraints: required
│   └── Mutable: yes

lastAttemptAt: timestamp
├── Purpose: Timestamp of most recent delivery attempt, used for retry scheduling
│   └── Mutable: yes

nextRetryAt: timestamp
├── Purpose: Scheduled time for next retry attempt (exponential backoff), null if no retry scheduled
│   └── Mutable: yes

errorMessage: string
├── Purpose: Error message from last failed delivery attempt (e.g., 'Email bounced', 'Invalid phone number')
│   └── Mutable: yes

errorCode: string
├── Purpose: Machine-readable error code (e.g., 'INVALID_EMAIL', 'SMS_RATE_LIMITED', 'PROVIDER_ERROR')
│   └── Mutable: yes

providerResponse: object
├── Purpose: Raw response from delivery provider (email SDK, SMS SDK, etc.) for debugging
│   └── Mutable: yes

fraudEventId: string
├── Purpose: Foreign key reference to fraud_events.id if delivery was blocked due to fraud flag, null if no fraud concern
│   └── Mutable: no

deliveryBlocked: boolean
├── Purpose: Whether delivery was blocked due to fraud flag or other security concern, defaults to false
│   ├── Constraints: required
│   └── Mutable: yes

blockReason: string
├── Purpose: Reason delivery was blocked (e.g., 'fraud_flagged', 'order_refunded', 'manual_hold')
│   └── Mutable: yes

qrCodeData: string
├── Purpose: QR code data string embedded in ticket, used for venue check-in
│   ├── Constraints: required
│   └── Mutable: yes

ticketPdfUrl: string
├── Purpose: URL to generated PDF ticket file stored in S3/object storage, null until PDF generated
│   └── Mutable: yes

createdAt: timestamp
├── Purpose: Delivery event creation timestamp (when order was completed)
│   ├── Constraints: required
│   └── Mutable: no

updatedAt: timestamp
└── Purpose: Last delivery event update timestamp (when status changed)
    ├── Constraints: required
    └── Mutable: yes

LARAVEL POLICIES:
- Create: [AUTH_USER_ID] != null
- Read: [user_id] == [AUTH_USER_ID]
- Update: [user_id] == [AUTH_USER_ID]
- Delete: Soft delete (mark deleted, retain data)
- Immutable: createdAt, userId

LIFECYCLE:
- Created: set [SERVER_TIMESTAMP] for createdAt
- Updated: update [UPDATED_AT]

## Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID primary key, auto-generated |
| ticketId | string | Yes | Foreign key reference to tickets.id, indexed for fast lookups |
| orderId | string | Yes | Foreign key reference to orders.id, indexed for order-level tracking |
| userId | string | Yes | Foreign key reference to users.id, indexed for user-level delivery history |
| eventId | string | Yes | Foreign key reference to events.id, indexed for event-level analytics |
| deliveryMethod | string | Yes | Delivery method: 'email', 'sms', 'dashboard' — determines which channel was used |
| recipientEmail | string | No | Email address ticket was sent to (if method=email), hashed for privacy |
| recipientPhone | string | No | Phone number ticket was sent to (if method=sms), hashed for privacy |
| status | string | Yes | Delivery status: 'pending', 'sent', 'failed', 'bounced', 'viewed' — tracks delivery lifecycle |
| deliveryTimestamp | timestamp | No | Exact timestamp when ticket was successfully delivered, null if not yet sent |
| viewedTimestamp | timestamp | No | Timestamp when ticket was first viewed by recipient (if tracked), null if not viewed |
| attemptCount | integer | Yes | Number of delivery attempts made, incremented on each retry |
| maxAttempts | integer | Yes | Maximum delivery attempts allowed (default 3), after which delivery is abandoned |
| lastAttemptAt | timestamp | No | Timestamp of most recent delivery attempt, used for retry scheduling |
| nextRetryAt | timestamp | No | Scheduled time for next retry attempt (exponential backoff), null if no retry scheduled |
| errorMessage | string | No | Error message from last failed delivery attempt (e.g., 'Email bounced', 'Invalid phone number') |
| errorCode | string | No | Machine-readable error code (e.g., 'INVALID_EMAIL', 'SMS_RATE_LIMITED', 'PROVIDER_ERROR') |
| providerResponse | object | No | Raw response from delivery provider (email SDK, SMS SDK, etc.) for debugging |
| fraudEventId | string | No | Foreign key reference to fraud_events.id if delivery was blocked due to fraud flag, null if no fraud concern |
| deliveryBlocked | boolean | Yes | Whether delivery was blocked due to fraud flag or other security concern, defaults to false |
| blockReason | string | No | Reason delivery was blocked (e.g., 'fraud_flagged', 'order_refunded', 'manual_hold') |
| qrCodeData | string | Yes | QR code data string embedded in ticket, used for venue check-in |
| ticketPdfUrl | string | No | URL to generated PDF ticket file stored in S3/object storage, null until PDF generated |
| createdAt | timestamp | Yes | Delivery event creation timestamp (when order was completed) |
| updatedAt | timestamp | Yes | Last delivery event update timestamp (when status changed) |

## Relationships


## Indexes


---
Generated by VisualPRD