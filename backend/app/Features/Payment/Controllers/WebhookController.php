<?php

namespace App\Features\Payment\Controllers;

use Illuminate\Http\Request;

class WebhookController
{
    public function handle(Request $request)
    {
        // TODO: generic webhook endpoint dispatcher.
        return response()->json(['received' => true]);
    }
}

