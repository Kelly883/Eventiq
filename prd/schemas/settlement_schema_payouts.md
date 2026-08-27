# payouts

## Description

PURPOSE: Store all payout records for organizers, tracking revenue settlement calculations, commission deductions, and payment status

FIELDS:
id: string
├── Purpose: UUID primary key, auto-generated
│   ├── Constraints: required
│   └── Mutable: no

organizerId: string
├── Purpose: Foreign key reference to organizers.id, indexed for fast lookups
│   ├── Constraints: required
│   └── Mutable: no

settlementPeriodStartDate: timestamp
├── Purpose: Start date of settlement period (e.g., first day of month), indexed for range queries
│   ├── Constraints: required
│   └── Mutable: yes

settlementPeriodEndDate: timestamp
├── Purpose: End date of settlement period (e.g., last day of month), indexed for range queries
│   ├── Constraints: required
│   └── Mutable: yes

grossRevenue: decimal(12, 2)
├── Purpose: Total ticket sales revenue before deductions (sum of orders.totalAmount for period)
│   ├── Constraints: required
│   └── Mutable: yes

refundsDeducted: decimal(12, 2)
├── Purpose: Total refund amounts deducted from gross revenue (sum of completed refund_requests.refundAmount for period)
│   ├── Constraints: required
│   └── Mutable: yes

netRevenue: decimal(12, 2)
├── Purpose: Computed: grossRevenue - refundsDeducted, represents revenue after refunds
│   ├── Constraints: required
│   └── Mutable: yes

platformCommissionPercentage: decimal(5, 2)
├── Purpose: Commission percentage applied (e.g., 10 for 10%), sourced from settlement_policies
│   ├── Constraints: required
│   └── Mutable: yes

platformCommissionAmount: decimal(12, 2)
├── Purpose: Computed: netRevenue * (platformCommissionPercentage / 100), platform's cut
│   ├── Constraints: required
│   └── Mutable: yes

processingFeePercentage: decimal(5, 2)
├── Purpose: Payment processor fee percentage (e.g., 2.9 for Stripe), sourced from settlement_policies
│   ├── Constraints: required
│   └── Mutable: yes

processingFeeAmount: decimal(12, 2)
├── Purpose: Computed: netRevenue * (processingFeePercentage / 100), payment processor's cut
│   ├── Constraints: required
│   └── Mutable: yes

taxWithholdingPercentage: decimal(5, 2)
├── Purpose: Optional tax withholding percentage (e.g., 30 for 1099 contractors), sourced from organizer tax settings
│   └── Mutable: yes

taxWithholdingAmount: decimal(12, 2)
├── Purpose: Computed: netRevenue * (taxWithholdingPercentage / 100) if applicable, withheld for tax purposes
│   └── Mutable: yes

payoutAmount: decimal(12, 2)
├── Purpose: Final payout: netRevenue - platformCommissionAmount - processingFeeAmount - taxWithholdingAmount
│   ├── Constraints: required
│   └── Mutable: yes

payoutMethod: string
├── Purpose: Payout method: 'ach', 'wire_transfer', 'check', 'store_credit', sourced from organizer payment settings
│   ├── Constraints: required
│   └── Mutable: yes

paymentGatewayPayoutId: string
├── Purpose: Payout ID from payment gateway (Stripe payout ID, PayPal payout ID, etc.), null until payout initiated
│   └── Mutable: no

paymentGatewayResponse: object
├── Purpose: Raw response from payment gateway for debugging and audit trail
│   └── Mutable: yes

status: string
├── Purpose: Payout status: 'pending' (awaiting calculation), 'calculated' (ready for approval), 'approved' (admin approved), 'processing' (sent to gateway), 'completed' (successfully paid), 'failed' (payment failed)
│   ├── Constraints: required
│   └── Mutable: yes

calculatedAt: timestamp
├── Purpose: Timestamp when payout was calculated by system
│   └── Mutable: yes

approvedBy: string
├── Purpose: Foreign key to users.id — admin who approved the payout, null if not yet approved
│   └── Mutable: yes

approvedAt: timestamp
├── Purpose: Timestamp when payout was approved by admin
│   └── Mutable: yes

processingStartedAt: timestamp
├── Purpose: Timestamp when payout processing began with payment gateway
│   └── Mutable: yes

completedAt: timestamp
├── Purpose: Timestamp when payout was successfully completed
│   └── Mutable: yes

failureReason: string
├── Purpose: Reason for payout failure if status='failed' (e.g., 'Invalid bank account', 'Insufficient funds'), null if successful
│   └── Mutable: yes

retryCount: integer
├── Purpose: Number of retry attempts for failed payouts, defaults to 0
│   ├── Constraints: required
│   └── Mutable: yes

nextRetryAt: timestamp
├── Purpose: Scheduled time for next retry attempt (exponential backoff), null if no retry scheduled
│   └── Mutable: yes

createdAt: timestamp
├── Purpose: Payout record creation timestamp
│   ├── Constraints: required
│   └── Mutable: no

updatedAt: timestamp
└── Purpose: Last payout update timestamp
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
| organizerId | string | Yes | Foreign key reference to organizers.id, indexed for fast lookups |
| settlementPeriodStartDate | timestamp | Yes | Start date of settlement period (e.g., first day of month), indexed for range queries |
| settlementPeriodEndDate | timestamp | Yes | End date of settlement period (e.g., last day of month), indexed for range queries |
| grossRevenue | decimal(12, 2) | Yes | Total ticket sales revenue before deductions (sum of orders.totalAmount for period) |
| refundsDeducted | decimal(12, 2) | Yes | Total refund amounts deducted from gross revenue (sum of completed refund_requests.refundAmount for period) |
| netRevenue | decimal(12, 2) | Yes | Computed: grossRevenue - refundsDeducted, represents revenue after refunds |
| platformCommissionPercentage | decimal(5, 2) | Yes | Commission percentage applied (e.g., 10 for 10%), sourced from settlement_policies |
| platformCommissionAmount | decimal(12, 2) | Yes | Computed: netRevenue * (platformCommissionPercentage / 100), platform's cut |
| processingFeePercentage | decimal(5, 2) | Yes | Payment processor fee percentage (e.g., 2.9 for Stripe), sourced from settlement_policies |
| processingFeeAmount | decimal(12, 2) | Yes | Computed: netRevenue * (processingFeePercentage / 100), payment processor's cut |
| taxWithholdingPercentage | decimal(5, 2) | No | Optional tax withholding percentage (e.g., 30 for 1099 contractors), sourced from organizer tax settings |
| taxWithholdingAmount | decimal(12, 2) | No | Computed: netRevenue * (taxWithholdingPercentage / 100) if applicable, withheld for tax purposes |
| payoutAmount | decimal(12, 2) | Yes | Final payout: netRevenue - platformCommissionAmount - processingFeeAmount - taxWithholdingAmount |
| payoutMethod | string | Yes | Payout method: 'ach', 'wire_transfer', 'check', 'store_credit', sourced from organizer payment settings |
| paymentGatewayPayoutId | string | No | Payout ID from payment gateway (Stripe payout ID, PayPal payout ID, etc.), null until payout initiated |
| paymentGatewayResponse | object | No | Raw response from payment gateway for debugging and audit trail |
| status | string | Yes | Payout status: 'pending' (awaiting calculation), 'calculated' (ready for approval), 'approved' (admin approved), 'processing' (sent to gateway), 'completed' (successfully paid), 'failed' (payment failed) |
| calculatedAt | timestamp | No | Timestamp when payout was calculated by system |
| approvedBy | string | No | Foreign key to users.id — admin who approved the payout, null if not yet approved |
| approvedAt | timestamp | No | Timestamp when payout was approved by admin |
| processingStartedAt | timestamp | No | Timestamp when payout processing began with payment gateway |
| completedAt | timestamp | No | Timestamp when payout was successfully completed |
| failureReason | string | No | Reason for payout failure if status='failed' (e.g., 'Invalid bank account', 'Insufficient funds'), null if successful |
| retryCount | integer | Yes | Number of retry attempts for failed payouts, defaults to 0 |
| nextRetryAt | timestamp | No | Scheduled time for next retry attempt (exponential backoff), null if no retry scheduled |
| createdAt | timestamp | Yes | Payout record creation timestamp |
| updatedAt | timestamp | Yes | Last payout update timestamp |

---
Generated by VisualPRD