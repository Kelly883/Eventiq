<?php

namespace App\Features\Payment\Controllers;

use Illuminate\Http\Request;

class PaystackController
{
    public function webhook(Request $request)
    {
        // TODO: verify signature + dispatch queued webhook processing job.
        return response()->json(['received' => true]);
    }
}

