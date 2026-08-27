<?php

namespace App\Features\admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Event;
use Illuminate\Http\Request;

class AdminEventController extends Controller
{
            public function index(Request $request)
    {
        $events = Event::query()
            ->when($request->filled('status'), fn ($q) => $q->where('status', $request->string('status')))
            ->when($request->filled('search'), fn ($q) => $q->where('title', 'like', '%'.$request->string('search').'%'))
            ->orderByDesc('created_at')
            ->paginate($request->integer('per_page', 25));

        return response()->json([
            'data' => $events->items(),
            'meta' => [
                'total' => $events->total(),
                'page' => $events->currentPage(),
                'perPage' => $events->perPage(),
            ],
        ]);
    }
}

