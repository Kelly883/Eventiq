export interface PaymentMethod {
  readonly id: string;
  readonly gateway: 'paystack' | 'flutterwave';
  readonly gatewayPaymentMethodId?: string;
  readonly type: string;
  readonly brand?: string;
  readonly lastFour?: string;
  readonly expiryMonth?: number;
  readonly expiryYear?: number;
  readonly details?: Record<string, unknown>;
  readonly isDefault: boolean;
  readonly paystackCustomerCode?: string;
  readonly flutterwaveCustomerId?: string;
  readonly createdAt?: string;
  readonly updatedAt?: string;
}

export interface Payment {
  readonly id: string;
  readonly userId: string;
  readonly reference: string;
  readonly gateway: 'paystack' | 'flutterwave';
  readonly gatewayTransactionId?: string;
  readonly amount: number;
  readonly currency: string;
  readonly status: 'pending' | 'successful' | 'failed' | 'cancelled' | 'refunded';
  readonly paymentType?: string;
  readonly metadata?: Record<string, unknown>;
  readonly paidAt?: string;
  readonly createdAt?: string;
  readonly updatedAt?: string;
}

export interface Transaction extends Payment {
  readonly organizerId?: string;
  readonly orderId?: string;
  readonly eventId?: string;
  readonly ticketId?: string;
  readonly gatewayReference?: string;
  readonly authorizationCode?: string;
  readonly authorizationType?: string;
  readonly fees?: number;
  readonly netAmount?: number;
  readonly paymentChannel?: string;
  readonly customerEmail?: string;
  readonly customerCode?: string;
  readonly lastError?: string;
  readonly refundedAmount?: number;
  readonly refundReference?: string;
  readonly isFullyRefunded?: boolean;
  readonly attempts?: number;
}

export interface PaystackPaymentResponse {
  readonly status: boolean;
  readonly message: string;
  readonly data?: {
    readonly reference: string;
    readonly status: string;
    readonly amount: number;
    readonly currency: string;
    readonly transaction_date?: string;
    readonly channel?: string;
    readonly gateway_response?: string;
    readonly paid_at?: string;
    readonly customer?: {
      readonly id: number;
      readonly email: string;
      readonly customer_code?: string;
    };
    readonly authorization?: {
      readonly authorization_code?: string;
      readonly bin?: string;
      readonly last4?: string;
      readonly exp_month?: string;
      readonly exp_year?: string;
      readonly channel?: string;
      readonly card_type?: string;
      readonly bank?: string;
      readonly country_code?: string;
    };
  };
}

export interface FlutterwavePaymentResponse {
  readonly status: string;
  readonly message: string;
  readonly data?: {
    readonly id?: number;
    readonly tx_ref?: string;
    readonly flw_ref?: string;
    readonly amount?: number;
    readonly currency?: string;
    readonly status?: string;
    readonly charged_amount?: number;
    readonly payment_type?: string;
    readonly created_at?: string;
    readonly customer?: {
      readonly id?: number;
      readonly email?: string;
      readonly name?: string;
      readonly phone_number?: string;
    };
    readonly card?: {
      readonly first_6digits?: string;
      readonly last_4digits?: string;
      readonly issuer?: string;
      readonly country?: string;
      readonly type?: string;
      readonly expiry?: string;
    };
  };
}

export interface OrganizerPaymentSettings {
  readonly paystackSubaccountCode?: string;
  readonly paystackConnectStatus?: 'enabled' | 'pending' | 'not_connected' | 'disabled';
  readonly flutterwaveSubaccountId?: string;
  readonly flutterwaveConnectStatus?: 'enabled' | 'pending' | 'not_connected' | 'disabled';
  readonly defaultGateway?: 'paystack' | 'flutterwave';
  readonly connectedAt?: string;
}
