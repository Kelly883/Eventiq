export type PaymentGateway = 'paystack' | 'flutterwave';

export interface PaymentGatewayConfig {
  readonly gateway: PaymentGateway;
  readonly enabled: boolean;
  readonly publicKey?: string;
  readonly currency: string;
}

export interface PaymentGatewayError {
  readonly code?: string;
  readonly message: string;
  readonly gateway: PaymentGateway;
}
