# refund_policies

## Description

PURPOSE: Store refund policies per event, defining eligibility windows, refund percentages, and processing rules

FIELDS:
id: string
├── Purpose: UUID primary key, auto-generated
│   ├── Constraints: required
│   └── Mutable: no

eventId: string
├── Purpose: Foreign key reference to events.id, indexed for fast lookups, one-to-one relationship
│   ├── Constraints: required
│   └── Mutable: no

organizerId: string
├── Purpose: Foreign key reference to organizers.id for access control
│   ├── Constraints: required
│   └── Mutable: no

refundWindowDays: integer
├── Purpose: Number of days after purchase during which refunds are allowed, defaults to 14
│   ├── Constraints: required
│   └── Mutable: yes

refundPercentageBeforeEvent: decimal(5, 2)
├── Purpose: Refund percentage allowed before event date (e.g., 100 for full refund, 80 for 80%)
│   ├── Constraints: required
│   └── Mutable: yes

refundPercentageAfterEventStart: decimal(5, 2)
├── Purpose: Refund percentage allowed after event has started, typically 0 or low percentage
│   ├── Constraints: required
│   └── Mutable: yes

allowRefundsAfterEventStart: boolean
├── Purpose: Whether to allow any refunds after event has started, defaults to false
│   ├── Constraints: required
│   └── Mutable: yes

processingTimeBusinessDays: integer
├── Purpose: Expected number of business days to process refund, defaults to 3-5
│   ├── Constraints: required
│   └── Mutable: yes

allowedRefundMethods: array<string>
├── Purpose: Array of allowed refund methods: ['original_payment_method', 'store_credit', 'alternative_payment_method']
│   ├── Constraints: required
│   └── Mutable: yes

requiresApproval: boolean
├── Purpose: Whether refund requests require manual admin approval before processing, defaults to false
│   ├── Constraints: required
│   └── Mutable: yes

autoApproveThreshold: decimal(10, 2)
├── Purpose: Optional: Auto-approve refunds below this amount without admin review
│   └── Mutable: yes

maxRefundsPerUser: integer
├── Purpose: Optional: Maximum number of refunds allowed per user for this event, null means unlimited
│   └── Mutable: yes

refundReasons: array<string>
├── Purpose: Array of allowed refund reasons: ['event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other']
│   ├── Constraints: required
│   └── Mutable: yes

cancellationPolicy: text
├── Purpose: Human-readable cancellation policy text displayed to users
│   └── Mutable: yes

isActive: boolean
├── Purpose: Whether this policy is currently active, defaults to true
│   ├── Constraints: required
│   └── Mutable: yes

createdAt: timestamp
├── Purpose: Policy creation timestamp
│   ├── Constraints: required
│   └── Mutable: no

updatedAt: timestamp
└── Purpose: Last policy update timestamp
    ├── Constraints: required
    └── Mutable: yes

LARAVEL POLICIES:
- Create: [AUTH_USER_ID] != null
- Read: [AUTH_USER_ID] != null
- Update: [user_id] == [AUTH_USER_ID]
- Delete: [user_id] == [AUTH_USER_ID]
- Immutable: createdAt

LIFECYCLE:
- Created: set [SERVER_TIMESTAMP] for createdAt
- Updated: update [UPDATED_AT]

## Fields

| Field | Type | Required | Description |
|-------|------|----------|-------------|
| id | string | Yes | UUID primary key, auto-generated |
| eventId | string | Yes | Foreign key reference to events.id, indexed for fast lookups, one-to-one relationship |
| organizerId | string | Yes | Foreign key reference to organizers.id for access control |
| refundWindowDays | integer | Yes | Number of days after purchase during which refunds are allowed, defaults to 14 |
| refundPercentageBeforeEvent | decimal(5, 2) | Yes | Refund percentage allowed before event date (e.g., 100 for full refund, 80 for 80%) |
| refundPercentageAfterEventStart | decimal(5, 2) | Yes | Refund percentage allowed after event has started, typically 0 or low percentage |
| allowRefundsAfterEventStart | boolean | Yes | Whether to allow any refunds after event has started, defaults to false |
| processingTimeBusinessDays | integer | Yes | Expected number of business days to process refund, defaults to 3-5 |
| allowedRefundMethods | array<string> | Yes | Array of allowed refund methods: ['original_payment_method', 'store_credit', 'alternative_payment_method'] |
| requiresApproval | boolean | Yes | Whether refund requests require manual admin approval before processing, defaults to false |
| autoApproveThreshold | decimal(10, 2) | No | Optional: Auto-approve refunds below this amount without admin review |
| maxRefundsPerUser | integer | No | Optional: Maximum number of refunds allowed per user for this event, null means unlimited |
| refundReasons | array<string> | Yes | Array of allowed refund reasons: ['event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other'] |
| cancellationPolicy | text | No | Human-readable cancellation policy text displayed to users |
| isActive | boolean | Yes | Whether this policy is currently active, defaults to true |
| createdAt | timestamp | Yes | Policy creation timestamp |
| updatedAt | timestamp | Yes | Last policy update timestamp |

## Relationships


## Indexes


---
Generated by VisualPRD