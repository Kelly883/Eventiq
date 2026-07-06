<?php

namespace App\Features\admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
    public function index(Request $request)
    {
        // TODO: implement event moderation listing/filtering
        return response()->json([
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'perPage' => 10],
        ]);
    }
}

