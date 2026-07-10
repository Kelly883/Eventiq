export type PaymentGatewayName = 'flutterwave' | 'paystack';

export type PaymentVerificationStatus = 'pending' | 'verified' | 'failed';

// Canonical gateway-agnostic transaction/payout lifecycle.
export type PaymentTransactionState =
  | 'initiated'
  | 'processing'
  | 'success'
  | 'failed'
  | 'refunded';

export interface PaymentMethod {
  id: string;
  type: string;
  gateway: PaymentGatewayName | string;
  label?: string;
}

export interface Transaction {
  id: string;
  reference: string;
  gateway: PaymentGatewayName | string;
  status: PaymentVerificationStatus | string;
  state?: PaymentTransactionState | string;
  amount: number;
  currency: string;
  createdAt?: string;
}

export interface Payout {
  id: string;
  organizerId: string;
  status: PaymentVerificationStatus | string;
  amount: number;
}

export interface OrganizerPaymentSettings {
  organizerId: string;
  gateway: PaymentGatewayName | string;
  payoutAccount?: string;
}

export interface PaymentGateway {
  key: PaymentGatewayName | string;
  label: string;
}

// DTOs / data contracts
export interface VerifyPaymentDTO {
  gateway: PaymentGatewayName;
  reference: string;
}

export interface PaymentVerifiedDTO {
  gateway: PaymentGatewayName;
  reference: string;
  status: PaymentVerificationStatus;
  transactionState: PaymentTransactionState;
  metadata?: Record<string, any>;
}

export interface PayoutReadyDTO {
  gateway: PaymentGatewayName;
  transactionReference: string;
  organizerId: number;
  payoutReference: string;
  // amounts in major units in UI; backend may use minor.
  amount: number;
  currency: string;
}

// Optional event payloads for future event/listener wiring.
export interface PaymentVerifiedEventPayload {
  type: 'payment.verified';
  payload: PaymentVerifiedDTO;
}

export interface PayoutReadyEventPayload {
  type: 'payout.ready';
  payload: PayoutReadyDTO;
}

export interface PaymentVerificationResponse {
  success: boolean;
  transaction?: Transaction;
  message?: string;
}


