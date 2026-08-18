export type PaymentGatewayName = 'flutterwave' | 'paystack';

export type PaymentVerificationStatus = 'pending' | 'verified' | 'failed';

export type PaymentTransactionState =
  | 'initiated'
  | 'processing'
  | 'success'
  | 'failed'
  | 'refunded';

export interface Transaction {
  readonly id: string;
  readonly userId: string;
  readonly organizerId: string;
  readonly orderId: string;
  readonly eventId: string;
  readonly ticketId?: string;
  readonly gateway: PaymentGatewayName;
  readonly reference: string;
  readonly gatewayTransactionId?: string;
  readonly gatewayReference?: string;
  readonly authorizationCode?: string;
  readonly authorizationType?: string;
  readonly amount: number;
  readonly currency: string;
  readonly fees: number;
  readonly netAmount: number;
  readonly status: PaymentVerificationStatus;
  readonly state?: PaymentTransactionState;
  readonly paymentChannel?: string;
  readonly customerEmail?: string;
  readonly customerCode?: string;
  readonly gatewayResponse?: Record<string, unknown>;
  readonly lastError?: string;
  readonly paidAt?: string;
  readonly refundedAmount: number;
  readonly refundReference?: string;
  readonly isFullyRefunded: boolean;
  readonly webhookEventId?: string;
  readonly webhookIdempotencyKey?: string;
  readonly attempts: number;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface Payout {
  readonly id: string;
  readonly organizerId: string;
  readonly gateway: PaymentGatewayName;
  readonly reference: string;
  readonly status: PaymentVerificationStatus;
  readonly amount: number;
  readonly fees: number;
  readonly netAmount: number;
  readonly currency: string;
  readonly metadata?: Record<string, unknown>;
  readonly paidAt?: string;
  readonly failureReason?: string;
  readonly initiatedBy?: string;
  readonly approvedBy?: string;
  readonly approvedAt?: string;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface PaymentGateway {
  readonly key: PaymentGatewayName;
  readonly label: string;
}

export interface VerifyPaymentDTO {
  readonly gateway: PaymentGatewayName;
  readonly reference: string;
}

export interface PaymentVerifiedDTO {
  readonly gateway: PaymentGatewayName;
  readonly reference: string;
  readonly status: PaymentVerificationStatus;
  readonly transactionState: PaymentTransactionState;
  readonly metadata?: Record<string, any>;
}

export interface PayoutReadyDTO {
  readonly gateway: PaymentGatewayName;
  readonly transactionReference: string;
  readonly organizerId: number;
  readonly payoutReference: string;
  readonly amount: number;
  readonly currency: string;
}

export interface PaymentVerifiedEventPayload {
  readonly type: 'payment.verified';
  readonly payload: PaymentVerifiedDTO;
}

export interface PayoutReadyEventPayload {
  readonly type: 'payout.ready';
  readonly payload: PayoutReadyDTO;
}

export interface PaymentVerificationResponse {
  readonly success: boolean;
  readonly transaction?: Transaction;
  readonly message?: string;
}

export * from './payment';
export * from './paystack';
export * from './flutterwave';
