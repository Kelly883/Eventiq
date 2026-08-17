<?php

namespace App\Features\OrganizerProfile\Controllers;

use App\Http\Resources\OrganizerPublicResource;
use App\Http\Resources\OrganizerPrivateResource;
use App\Models\Organizer;
use App\Features\OrganizerProfile\Requests\UpdateOrganizerProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class OrganizerProfileController extends Controller
{
    public function show($id)
    {
        $organizer = Organizer::with('user', 'events')->findOrFail($id);

        if (! $organizer->isPublic) {
            return response()->json([
                'data' => new OrganizerPublicResource($organizer),
            ], Response::HTTP_OK);
        }

        return response()->json([
            'data' => new OrganizerPublicResource($organizer),
        ]);
    }

    public function edit()
    {
        $organizer = Organizer::where('user_id', auth()->id())->firstOrFail();

        return response()->json([
            'data' => new OrganizerPrivateResource($organizer),
        ]);
    }

    public function update(UpdateOrganizerProfileRequest $request)
    {
        $organizer = Organizer::where('user_id', auth()->id())->firstOrFail();
        $organizer->update($request->validated());
        $organizer->refresh();

        return response()->json([
            'data' => new OrganizerPrivateResource($organizer),
            'message' => 'Profile updated successfully.',
        ]);
    }

    public function events($id)
    {
        $organizer = Organizer::findOrFail($id);

        return response()->json([
            'data' => $organizer->events()->paginate(12),
        ]);
    }

    public function auditLog()
    {
        $organizer = Organizer::where('user_id', auth()->id())->firstOrFail();

        return response()->json(['data' => []]);
    }
}
