<?php

namespace App\Features\admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index(Request $request)
    {
        // TODO: implement metrics/alerts/activity aggregation
        return response()->json([
            'metrics' => [],
            'quickStats' => [],
            'activity' => [],
            'alerts' => [],
        ]);
    }
}

