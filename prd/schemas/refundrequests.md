# refund_requests

## Description

PURPOSE: Store all refund requests from users with complete lifecycle tracking

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
├── Purpose: Foreign key reference to orders.id for transaction context
│   ├── Constraints: required
│   └── Mutable: no

userId: string
├── Purpose: Foreign key reference to users.id, indexed for user-level refund tracking
│   ├── Constraints: required
│   └── Mutable: no

eventId: string
├── Purpose: Foreign key reference to events.id, indexed for event-level analytics
│   ├── Constraints: required
│   └── Mutable: no

originalAmount: decimal(10, 2)
├── Purpose: Original ticket purchase price
│   ├── Constraints: required
│   └── Mutable: yes

refundAmount: decimal(10, 2)
├── Purpose: Calculated refund amount based on policy (originalAmount * refundPercentage)
│   ├── Constraints: required
│   └── Mutable: yes

refundPercentage: decimal(5, 2)
├── Purpose: Refund percentage applied based on event policy and timing (e.g., 100, 80, 50)
│   ├── Constraints: required
│   └── Mutable: yes

reason: string
├── Purpose: Refund reason: 'event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other'
│   ├── Constraints: required
│   └── Mutable: yes

explanation: text
├── Purpose: Optional detailed explanation provided by user
│   └── Mutable: yes

refundMethod: string
├── Purpose: Refund method: 'original_payment_method', 'store_credit', 'alternative_payment_method'
│   ├── Constraints: required
│   └── Mutable: yes

status: string
├── Purpose: Request status: 'pending', 'approved', 'rejected', 'processing', 'completed', 'failed'
│   ├── Constraints: required
│   └── Mutable: yes

rejectionReason: text
├── Purpose: Reason for rejection if status='rejected', null if approved
│   └── Mutable: yes

approvedBy: string
├── Purpose: Foreign key to users.id — admin who approved/rejected the request, null if not yet reviewed
│   └── Mutable: yes

approvedAt: timestamp
├── Purpose: Timestamp when request was approved or rejected
│   └── Mutable: yes

processingStartedAt: timestamp
├── Purpose: Timestamp when refund processing began with payment gateway
│   └── Mutable: yes

completedAt: timestamp
├── Purpose: Timestamp when refund was successfully completed
│   └── Mutable: yes

paymentGatewayRefundId: string
├── Purpose: Refund ID from payment gateway (Stripe refund ID, PayPal refund ID, etc.)
│   └── Mutable: no

paymentGatewayResponse: json
├── Purpose: Raw response from payment gateway for debugging
│   └── Mutable: yes

appealCount: integer
├── Purpose: Number of appeals submitted for this refund request, defaults to 0
│   ├── Constraints: required
│   └── Mutable: yes

lastAppealAt: timestamp
├── Purpose: Timestamp of most recent appeal submission
│   └── Mutable: yes

createdAt: timestamp
├── Purpose: Refund request creation timestamp
│   ├── Constraints: required
│   └── Mutable: no

updatedAt: timestamp
└── Purpose: Last refund request update timestamp
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
| orderId | string | Yes | Foreign key reference to orders.id for transaction context |
| userId | string | Yes | Foreign key reference to users.id, indexed for user-level refund tracking |
| eventId | string | Yes | Foreign key reference to events.id, indexed for event-level analytics |
| originalAmount | decimal(10, 2) | Yes | Original ticket purchase price |
| refundAmount | decimal(10, 2) | Yes | Calculated refund amount based on policy (originalAmount * refundPercentage) |
| refundPercentage | decimal(5, 2) | Yes | Refund percentage applied based on event policy and timing (e.g., 100, 80, 50) |
| reason | string | Yes | Refund reason: 'event_cancelled', 'personal_circumstances', 'duplicate_purchase', 'other' |
| explanation | text | No | Optional detailed explanation provided by user |
| refundMethod | string | Yes | Refund method: 'original_payment_method', 'store_credit', 'alternative_payment_method' |
| status | string | Yes | Request status: 'pending', 'approved', 'rejected', 'processing', 'completed', 'failed' |
| rejectionReason | text | No | Reason for rejection if status='rejected', null if approved |
| approvedBy | string | No | Foreign key to users.id — admin who approved/rejected the request, null if not yet reviewed |
| approvedAt | timestamp | No | Timestamp when request was approved or rejected |
| processingStartedAt | timestamp | No | Timestamp when refund processing began with payment gateway |
| completedAt | timestamp | No | Timestamp when refund was successfully completed |
| paymentGatewayRefundId | string | No | Refund ID from payment gateway (Stripe refund ID, PayPal refund ID, etc.) |
| paymentGatewayResponse | json | No | Raw response from payment gateway for debugging |
| appealCount | integer | Yes | Number of appeals submitted for this refund request, defaults to 0 |
| lastAppealAt | timestamp | No | Timestamp of most recent appeal submission |
| createdAt | timestamp | Yes | Refund request creation timestamp |
| updatedAt | timestamp | Yes | Last refund request update timestamp |

## Relationships


## Indexes


---
Generated by VisualPRD