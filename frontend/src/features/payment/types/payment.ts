export interface PaymentMethod {
  readonly id: string;
  readonly type: string;
  readonly brand?: string;
  readonly lastFour?: string;
  readonly expiryMonth?: number;
  readonly expiryYear?: number;
  readonly isDefault: boolean;
}

export interface StripePaymentMethodResponse {
  readonly id: string;
  readonly object: string;
  readonly type: string;
  readonly card?: {
    readonly brand: string;
    readonly last4: string;
    readonly exp_month: number;
    readonly exp_year: number;
  };
}

export interface OrganizerPaymentSettings {
  readonly stripeAccountId?: string;
  readonly stripeConnectStatus?: 'enabled' | 'pending' | 'not_connected';
  readonly connectedAt?: string;
}
