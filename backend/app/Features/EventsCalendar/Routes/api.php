<?php

use Illuminate\Support\Facades\Route;
use App\Features\EventsCalendar\Controllers\EventCalendarController;

Route::prefix('events/public/calendar')->middleware('throttle:discovery')->group(function () {
    Route::get('/', [EventCalendarController::class, 'index']);
    Route::get('/day/{date}', [EventCalendarController::class, 'dayDetail']);
    Route::get('/range', [EventCalendarController::class, 'range']);
});
