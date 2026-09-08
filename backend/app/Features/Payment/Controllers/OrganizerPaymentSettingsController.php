<?php

namespace App\Features\Payment\Controllers;

use App\Features\Payment\Resources\OrganizerPaymentSettingsResource;
use Illuminate\Http\Request;

class OrganizerPaymentSettingsController
{
    /**
     * Read the organizer's payment/payout configuration.
     *
     * The gateway subaccount/recipient configuration and connect statuses are
     * managed through Eventiq onboarding and are intentionally read-only here.
     * Organizers must NOT be able to self-assert a "connected"/"enabled" status
     * or write arbitrary subaccount/recipient codes, so no writable endpoint is
     * exposed to them.
     */
    public function index(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        return response()->json(new OrganizerPaymentSettingsResource($organizer));
    }
}
