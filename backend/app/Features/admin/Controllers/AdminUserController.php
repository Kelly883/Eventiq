<?php

namespace App\Features\admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AdminUserController extends Controller
{
    public function index(Request $request)
    {
        // TODO: implement user search/filtering + pagination
        return response()->json([
            'data' => [],
            'meta' => ['total' => 0, 'page' => 1, 'perPage' => 10],
        ]);
    }
}

