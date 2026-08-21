<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Resources\OrganizerPaymentSettingsResource;
use App\Features\Payment\Requests\UpdateOrganizerPaymentSettingsRequest;
use App\Models\Organizer;
use Illuminate\Http\Request;

class OrganizerPaymentSettingsController
{
    public function index(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        return response()->json(new OrganizerPaymentSettingsResource($organizer));
    }

    public function update(UpdateOrganizerPaymentSettingsRequest $request)
    {
        $organizer = $request->user()->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        $data = $request->validated();

        $organizer->update([
            'paystack_subaccount_code' => $data['paystack_subaccount_code'] ?? $organizer->paystack_subaccount_code,
            'paystack_business_name' => $data['paystack_business_name'] ?? $organizer->paystack_business_name,
            'paystack_recipient_code' => $data['paystack_recipient_code'] ?? $organizer->paystack_recipient_code,
            'paystack_connect_status' => $data['paystack_connect_status'] ?? $organizer->paystack_connect_status,
            'paystack_connected_at' => $data['paystack_connected_at'] ?? $organizer->paystack_connected_at,
            'flutterwave_subaccount_id' => $data['flutterwave_subaccount_id'] ?? $organizer->flutterwave_subaccount_id,
            'flutterwave_business_reference' => $data['flutterwave_business_reference'] ?? $organizer->flutterwave_business_reference,
            'flutterwave_connect_status' => $data['flutterwave_connect_status'] ?? $organizer->flutterwave_connect_status,
            'flutterwave_connected_at' => $data['flutterwave_connected_at'] ?? $organizer->flutterwave_connected_at,
        ]);

        return response()->json(new OrganizerPaymentSettingsResource($organizer));
    }
}
