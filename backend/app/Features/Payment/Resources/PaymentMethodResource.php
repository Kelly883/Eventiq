<?php

namespace App\Features\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PaymentMethodResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'gateway' => $this->gateway,
            'gatewayPaymentMethodId' => $this->gateway_payment_method_id,
            'type' => $this->type,
            'brand' => $this->brand,
            'lastFour' => $this->last_four,
            'expiryMonth' => $this->exp_month,
            'expiryYear' => $this->exp_year,
            'isDefault' => (bool) $this->is_default,
            'isExpired' => $this->isExpired(),
            'paystackCustomerCode' => $this->paystack_customer_code,
            'flutterwaveCustomerId' => $this->flutterwave_customer_id,
            'createdAt' => $this->created_at,
            'updatedAt' => $this->updated_at,
        ];
    }
}
