<?php

namespace App\Features\Analytics\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function getSummary(Request $request, $eventId) {}
    public function getSalesVelocity(Request $request, $eventId) {}
    public function getDetailed(Request $request, $eventId) {}
    public function getComparison(Request $request) {}
}
