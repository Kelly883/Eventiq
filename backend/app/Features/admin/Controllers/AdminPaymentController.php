<?php

namespace App\Features\admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminPaymentController extends Controller
{
    public function index(Request $request)
    {
        // TODO: implement payment reconciliation filters + report/chart
        return response()->json([
            'payments' => [],
            'report' => [],
            'chart' => [],
        ]);
    }
}

