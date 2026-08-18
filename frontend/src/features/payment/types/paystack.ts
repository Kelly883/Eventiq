export interface PaystackInitializeResponse {
  readonly status: boolean;
  readonly message: string;
  readonly data: {
    readonly authorizationUrl: string;
    readonly accessCode: string;
    readonly reference: string;
  };
}

export interface PaystackCustomer {
  readonly id: number;
  readonly customerCode: string;
  readonly email: string;
  readonly firstName: string;
  readonly lastName: string;
  readonly phone: string;
}

export interface PaystackAuthorization {
  readonly authorizationCode: string;
  readonly bin: string;
  readonly last4: string;
  readonly expMonth: string;
  readonly expYear: string;
  readonly channel: string;
  readonly cardType: string;
  readonly bank: string;
  readonly countryCode: string;
  readonly brand: string;
  readonly reusable: boolean;
  readonly signature: string;
}

export interface PaystackVerifyResponse {
  readonly status: boolean;
  readonly message: string;
  readonly data: {
    readonly id: number;
    readonly domain: string;
    readonly status: 'success' | 'failed' | 'pending';
    readonly reference: string;
    readonly amount: number;
    readonly currency: string;
    readonly customer: PaystackCustomer;
    readonly authorization?: PaystackAuthorization;
    readonly gatewayResponse: string;
    readonly paidAt: string;
    readonly channel: string;
    readonly fees: number;
    readonly amountSettled: number;
  };
}

export interface PaystackWebhookPayload {
  readonly event: string;
  readonly data: {
    readonly id: number;
    readonly reference: string;
    readonly amount: number;
    readonly currency: string;
    readonly status: 'success' | 'failed';
    readonly paidAt: string;
    readonly customer: PaystackCustomer;
    readonly authorization?: PaystackAuthorization;
  };
}

export interface PaystackRefundResponse {
  readonly status: boolean;
  readonly message: string;
  readonly data: {
    readonly id: number;
    readonly transaction: string;
    readonly amount: number;
    readonly currency: string;
    readonly status: 'pending' | 'processed' | 'failed';
  };
}
