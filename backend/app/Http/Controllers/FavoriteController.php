<?php

namespace App\Http\Controllers;

use App\Models\Favorite;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function index(Request $request)
    {
        $favorites = Favorite::where('user_id', $request->user()->id)
            ->with('event.organizer', 'event.ticketTiers')
            ->latest()
            ->paginate(20);

        return response()->json([
            'data' => $favorites->items(),
            'meta' => [
                'current_page' => $favorites->currentPage(),
                'last_page' => $favorites->lastPage(),
                'per_page' => $favorites->perPage(),
                'total' => $favorites->total(),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'event_id' => 'required|exists:events,id',
        ]);

        $favorite = Favorite::firstOrCreate([
            'user_id' => $request->user()->id,
            'event_id' => $validated['event_id'],
        ]);

        return response()->json([
            'data' => $favorite,
            'message' => 'Event added to favorites',
        ], 201);
    }

    public function destroy(Request $request, $eventId)
    {
        $deleted = Favorite::where('user_id', $request->user()->id)
            ->where('event_id', $eventId)
            ->delete();

        if (! $deleted) {
            return response()->json([
                'message' => 'Favorite not found',
            ], 404);
        }

        return response()->json([
            'message' => 'Event removed from favorites',
        ]);
    }

    public function check(Request $request, $eventId)
    {
        $isFavorite = Favorite::where('user_id', $request->user()->id)
            ->where('event_id', $eventId)
            ->exists();

        return response()->json([
            'data' => ['is_favorite' => $isFavorite],
        ]);
    }
}
