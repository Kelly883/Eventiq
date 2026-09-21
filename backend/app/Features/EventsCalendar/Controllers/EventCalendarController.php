<?php

namespace App\Features\EventsCalendar\Controllers;

use App\Features\EventsCalendar\Http\Requests\CalendarFilterRequest;
use App\Features\EventsCalendar\Services\EventCalendarService;
use App\Http\Controllers\Controller;

class EventCalendarController extends Controller
{
    public function __construct(private readonly EventCalendarService $calendarService)
    {
    }

    public function index(CalendarFilterRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->calendarService->getMonthOverview($request->validated()),
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=60',
            'Vary' => 'Accept-Encoding, Timezone',
        ]);
    }

    public function dayDetail(string $date, CalendarFilterRequest $request)
    {
        // Validate the date format before passing to the service
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return response()->json([
                'success' => false,
                'message' => 'Invalid date format. Expected Y-m-d.',
            ], 400);
        }

        $data = $request->validated();
        $data['date'] = $date;

        return response()->json([
            'success' => true,
            'data' => $this->calendarService->getDayDetails($data),
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=60',
            'Vary' => 'Accept-Encoding, Timezone',
        ]);
    }

    public function range(CalendarFilterRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->calendarService->getRangeOverview($request->validated()),
        ])->withHeaders([
            'Cache-Control' => 'public, max-age=60',
            'Vary' => 'Accept-Encoding, Timezone',
        ]);
    }
}
