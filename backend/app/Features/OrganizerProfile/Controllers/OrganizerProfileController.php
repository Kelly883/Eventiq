<?php

namespace App\Features\OrganizerProfile\Controllers;

use App\Features\OrganizerProfile\Models\OrganizerProfile;
use App\Features\OrganizerProfile\Requests\UpdateOrganizerProfileRequest;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class OrganizerProfileController extends Controller
{
    public function show($id)
    {
        $organizer = OrganizerProfile::with('user', 'events')->findOrFail($id);
        return response()->json(['data' => $organizer]);
    }

    public function edit()
    {
        $organizer = OrganizerProfile::where('user_id', auth()->id())->firstOrFail();
        return response()->json(['data' => $organizer]);
    }

    public function update(UpdateOrganizerProfileRequest $request)
    {
        $organizer = OrganizerProfile::where('user_id', auth()->id())->firstOrFail();
        $organizer->update($request->validated());
        return response()->json(['data' => $organizer, 'message' => 'Profile updated successfully.']);
    }

    public function events($id)
    {
        $organizer = OrganizerProfile::findOrFail($id);
        return response()->json(['data' => $organizer->events()->paginate(12)]);
    }

    public function auditLog()
    {
        $organizer = OrganizerProfile::where('user_id', auth()->id())->firstOrFail();
        // Placeholder: return audit log entries for this organizer
        return response()->json(['data' => []]);
    }
}