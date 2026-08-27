# fraud_events

## Description

PURPOSE: Immutable audit trail of all fraud detection events, storing risk scores, detection methods, and fraud factors for every suspicious transaction

FIELDS:
id: string
├── Purpose: UUID primary key, auto-generated
│   ├── Constraints: required
│   └── Mutable: no

orderId: string
├── Purpose: Foreign key reference to orders.id, indexed for fast lookups
│   ├── Constraints: required
│   └── Mutable: no

userId: string
├── Purpose: Foreign key reference to users.id, indexed for user-level fraud tracking
│   ├── Constraints: required
│   └── Mutable: no

eventType: string
├── Purpose: Type of fraud event: 'duplicate_ticket_attempt', 'velocity_check_failed', 'payment_pattern_suspicious', 'device_fingerprint_mismatch', 'geolocation_anomaly', 'card_testing', 'high_risk_payment_method'
│   ├── Constraints: required
│   └── Mutable: yes

riskScore: decimal(5,2)
├── Purpose: Fraud risk score 0-100, computed by fraud detection SDK
│   ├── Constraints: required
│   └── Mutable: yes

riskLevel: string
├── Purpose: Risk level classification: 'low' (0-30), 'medium' (31-70), 'high' (71-100)
│   ├── Constraints: required
│   └── Mutable: yes

detectionMethod: string
├── Purpose: Method that flagged this event: 'sift_science', 'stripe_radar', 'duplicate_detection', 'velocity_check', 'rule_based'
│   ├── Constraints: required
│   └── Mutable: yes

fraudFactors: object
├── Purpose: Object containing fraud risk factors: {duplicateTicketDetected: boolean, velocityCheckFailed: boolean, paymentPatternSuspicious: boolean, deviceFingerprintMismatch: boolean, geolocationAnomaly: boolean, cardTestingPattern: boolean, highRiskPaymentMethod: boolean}
│   ├── Constraints: required
│   └── Mutable: yes

paymentDetails: object
├── Purpose: Payment method fingerprint data: {cardLast4: string, issuer: string, country: string, cardFingerprint: string}
│   └── Mutable: yes

velocityMetrics: object
├── Purpose: User velocity data: {ordersIn24h: integer, totalSpendIn24h: decimal, averageOrderValue: decimal, ordersInLastHour: integer}
│   └── Mutable: yes

deviceInfo: object
├── Purpose: Device fingerprinting data: {ipAddress: string, userAgent: string, deviceFingerprint: string, country: string, city: string}
│   └── Mutable: yes

duplicateTicketInfo: object
├── Purpose: Duplicate ticket detection results: {matchingTicketIds: array<string>, matchingQRCodes: array<string>, matchingEventIds: array<string>}
│   └── Mutable: yes

status: string
├── Purpose: Event status: 'flagged', 'reviewed', 'approved', 'rejected', 'auto_blocked'
│   ├── Constraints: required
│   └── Mutable: yes

reviewedBy: string
├── Purpose: Foreign key to users.id — admin who reviewed this event, null if not yet reviewed
│   └── Mutable: yes

reviewNotes: string
├── Purpose: Optional notes from admin reviewer documenting the decision
│   └── Mutable: yes

reviewedAt: timestamp
├── Purpose: Timestamp when event was reviewed by admin
│   └── Mutable: yes

createdAt: timestamp
├── Purpose: Fraud event detection timestamp
│   ├── Constraints: required
│   └── Mutable: no

updatedAt: timestamp
└── Purpose: Last update timestamp
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
| orderId | string | Yes | Foreign key reference to orders.id, indexed for fast lookups |
| userId | string | Yes | Foreign key reference to users.id, indexed for user-level fraud tracking |
| eventType | string | Yes | Type of fraud event: 'duplicate_ticket_attempt', 'velocity_check_failed', 'payment_pattern_suspicious', 'device_fingerprint_mismatch', 'geolocation_anomaly', 'card_testing', 'high_risk_payment_method' |
| riskScore | decimal(5,2) | Yes | Fraud risk score 0-100, computed by fraud detection SDK |
| riskLevel | string | Yes | Risk level classification: 'low' (0-30), 'medium' (31-70), 'high' (71-100) |
| detectionMethod | string | Yes | Method that flagged this event: 'sift_science', 'stripe_radar', 'duplicate_detection', 'velocity_check', 'rule_based' |
| fraudFactors | object | Yes | Object containing fraud risk factors: {duplicateTicketDetected: boolean, velocityCheckFailed: boolean, paymentPatternSuspicious: boolean, deviceFingerprintMismatch: boolean, geolocationAnomaly: boolean, cardTestingPattern: boolean, highRiskPaymentMethod: boolean} |
| paymentDetails | object | No | Payment method fingerprint data: {cardLast4: string, issuer: string, country: string, cardFingerprint: string} |
| velocityMetrics | object | No | User velocity data: {ordersIn24h: integer, totalSpendIn24h: decimal, averageOrderValue: decimal, ordersInLastHour: integer} |
| deviceInfo | object | No | Device fingerprinting data: {ipAddress: string, userAgent: string, deviceFingerprint: string, country: string, city: string} |
| duplicateTicketInfo | object | No | Duplicate ticket detection results: {matchingTicketIds: array<string>, matchingQRCodes: array<string>, matchingEventIds: array<string>} |
| status | string | Yes | Event status: 'flagged', 'reviewed', 'approved', 'rejected', 'auto_blocked' |
| reviewedBy | string | No | Foreign key to users.id — admin who reviewed this event, null if not yet reviewed |
| reviewNotes | string | No | Optional notes from admin reviewer documenting the decision |
| reviewedAt | timestamp | No | Timestamp when event was reviewed by admin |
| createdAt | timestamp | Yes | Fraud event detection timestamp |
| updatedAt | timestamp | Yes | Last update timestamp |

## Relationships


## Indexes


---
Generated by VisualPRD