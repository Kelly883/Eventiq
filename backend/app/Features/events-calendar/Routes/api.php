<?php

use Illuminate\Support\Facades\Route;
use App\Features\EventsCalendar\Controllers\EventCalendarController;

Route::prefix('calendar')->group(function () {
    Route::get('/', [EventCalendarController::class, 'index']);
    Route::get('/day', [EventCalendarController::class, 'dayDetail']);
    Route::get('/range', [EventCalendarController::class, 'range']);
});
