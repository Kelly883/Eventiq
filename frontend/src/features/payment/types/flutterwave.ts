export interface FlutterwaveInitializeResponse {
  readonly status: string;
  readonly message: string;
  readonly data: {
    readonly link: string;
    readonly txRef: string;
  };
}

export interface FlutterwaveCustomer {
  readonly id: number;
  readonly name: string;
  readonly email: string;
  readonly phoneNumber: string;
}

export interface FlutterwaveCard {
  readonly first6digits: string;
  readonly last4digits: string;
  readonly brand: string;
  readonly expiry: string;
}

export interface FlutterwaveVerifyResponse {
  readonly status: string;
  readonly message: string;
  readonly data: {
    readonly id: number;
    readonly txRef: string;
    readonly flwRef: string;
    readonly amount: number;
    readonly currency: string;
    readonly chargedAmount: number;
    readonly status: 'successful' | 'failed' | 'pending';
    readonly paymentType: string;
    readonly createdAt: string;
    readonly customer: FlutterwaveCustomer;
    readonly card?: FlutterwaveCard;
    readonly processorResponse?: string;
  };
}

export interface FlutterwaveWebhookPayload {
  readonly event: string;
  readonly data: {
    readonly id: number;
    readonly txRef: string;
    readonly flwRef: string;
    readonly amount: number;
    readonly currency: string;
    readonly status: 'successful' | 'failed';
    readonly createdAt: string;
    readonly customer: FlutterwaveCustomer;
    readonly processorResponse?: string;
  };
}

export interface FlutterwaveRefundResponse {
  readonly status: string;
  readonly message: string;
  readonly data: {
    readonly id: number;
    readonly txRef: string;
    readonly amount: number;
    readonly status: 'pending' | 'processed' | 'failed';
  };
}
