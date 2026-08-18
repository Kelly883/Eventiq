export interface PaymentMethod {
  readonly id: string;
  readonly gateway: 'paystack' | 'flutterwave';
  readonly type: 'card' | 'bank_transfer' | 'ussd' | 'qr' | 'mobile_money';
  readonly gatewayPaymentMethodId: string;
  readonly paystackCustomerCode?: string;
  readonly flutterwaveCustomerId?: string;
  readonly brand?: string;
  readonly lastFour?: string;
  readonly expiryMonth?: number;
  readonly expiryYear?: number;
  readonly bankName?: string;
  readonly accountName?: string;
  readonly accountNumberLast4?: string;
  readonly details?: Record<string, unknown>;
  readonly isDefault: boolean;
  readonly createdAt: string;
  readonly updatedAt: string;
}

export interface OrganizerPaymentSettings {
  readonly organizerId: string;

  readonly paystack: {
    readonly enabled: boolean;
    readonly subaccountCode?: string;
    readonly businessName?: string;
    readonly recipientCode?: string;
    readonly connectStatus: 'enabled' | 'pending' | 'not_connected';
    readonly connectedAt?: string;
  };

  readonly flutterwave: {
    readonly enabled: boolean;
    readonly subaccountId?: string;
    readonly businessReference?: string;
    readonly connectStatus: 'enabled' | 'pending' | 'not_connected';
    readonly connectedAt?: string;
  };

  readonly defaultProvider?: 'paystack' | 'flutterwave';
  readonly supportedCurrencies: string[];
  readonly platformCommissionPercentage: number;
  readonly payoutMethods: string[];
  readonly settlementConfig?: Record<string, unknown>;
}
