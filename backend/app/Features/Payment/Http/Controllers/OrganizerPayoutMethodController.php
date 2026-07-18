<?php

namespace App\Features\Payment\Http\Controllers;

use App\Features\Payment\Http\Requests\StoreOrganizerPayoutMethodRequest;
use App\Features\Payment\Models\OrganizerPayoutMethod;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class OrganizerPayoutMethodController extends Controller
{
    /**
     * GET /api/organizer/payout-methods
     */
    public function index(Request $request)
    {
        $organizer = $request->user()->organizer;

        if (! $organizer) {
            return response()->json(['message' => 'Not an organizer account.'], 403);
        }

        return response()->json([
            'data' => $organizer->payoutMethods()->get(),
        ]);
    }

    /**
     * POST /api/organizer/payout-methods
     */
    public function store(StoreOrganizerPayoutMethodRequest $request)
    {
        $organizer = $request->user()->organizer;
        $data = $request->validated();

        return DB::transaction(function () use ($organizer, $data) {
            if (! empty($data['is_default'])) {
                $organizer->payoutMethods()->update(['is_default' => false]);
            }

            $method = $organizer->payoutMethods()->create($data);

            // First payout method for this organizer becomes the default
            // automatically, even if not explicitly requested.
            if ($organizer->payoutMethods()->count() === 1) {
                $method->update(['is_default' => true]);
            }

            return response()->json($method, 201);
        });
    }

    /**
     * DELETE /api/organizer/payout-methods/{id}
     */
    public function destroy(Request $request, int $id)
    {
        $organizer = $request->user()->organizer;

        $method = $organizer->payoutMethods()->where('id', $id)->firstOrFail();
        $method->delete();

        return response()->noContent();
    }
}
