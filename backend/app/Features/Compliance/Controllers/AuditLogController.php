<?php

namespace App\Features\Compliance\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function index(Request $request)
    {
        // TODO: implement filtering/pagination
        return response()->json([
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'perPage' => 10],
        ]);
    }
}

