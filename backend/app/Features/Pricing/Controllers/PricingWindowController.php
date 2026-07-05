<?php

namespace App\Features\Pricing\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class PricingWindowController extends Controller
{
    public function index($eventId)
    {
        // List pricing windows
    }

    public function store(Request $request, $eventId)
    {
        // Create pricing window
    }

    public function show($eventId, $id)
    {
        // Show single pricing window
    }

    public function update(Request $request, $eventId, $id)
    {
        // Update pricing window
    }

    public function destroy($eventId, $id)
    {
        // Delete pricing window
    }
}
