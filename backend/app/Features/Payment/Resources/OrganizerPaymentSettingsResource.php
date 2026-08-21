<?php

namespace App\Features\Payment\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class OrganizerPaymentSettingsResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        $resource = $this->resource;

        return [
            'paystackSubaccountCode' => $resource->paystack_subaccount_code,
            'paystackBusinessName' => $resource->paystack_business_name,
            'paystackRecipientCode' => $resource->paystack_recipient_code,
            'paystackConnectStatus' => $resource->paystack_connect_status,
            'paystackConnectedAt' => $resource->paystack_connected_at,
            'flutterwaveSubaccountId' => $resource->flutterwave_subaccount_id,
            'flutterwaveBusinessReference' => $resource->flutterwave_business_reference,
            'flutterwaveConnectStatus' => $resource->flutterwave_connect_status,
            'flutterwaveConnectedAt' => $resource->flutterwave_connected_at,
            'defaultGateway' => $resource->paymentDefault ?? null,
            'isPaystackConnected' => $resource->isPaystackConnected(),
            'isFlutterwaveConnected' => $resource->isFlutterwaveConnected(),
        ];
    }
}
