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
        ]);
    }

    public function dayDetail(CalendarFilterRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->calendarService->getDayDetails($request->validated()),
        ]);
    }

    public function range(CalendarFilterRequest $request)
    {
        return response()->json([
            'success' => true,
            'data' => $this->calendarService->getRangeOverview($request->validated()),
        ]);
    }
}
