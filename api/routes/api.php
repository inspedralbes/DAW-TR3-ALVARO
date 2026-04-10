<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

// ── Public routes ─────────────────────────────────────────────────────────
Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}/seats', [EventController::class, 'seats']);

Route::get('/seats', [SeatController::class, 'index']);
Route::post('/seats/reserve', [SeatController::class, 'reserve']);
Route::post('/seats/checkout', [SeatController::class, 'checkout']);

Route::get('/tickets', [SeatController::class, 'myTickets']);

// ── Admin routes (no auth middleware for now) ─────────────────────────────
Route::prefix('admin')->group(function () {
    Route::get('/stats', [EventController::class, 'adminStats']);
    Route::post('/events', [EventController::class, 'store']);
    Route::put('/events/{id}', [EventController::class, 'update']);
    Route::delete('/events/{id}', [EventController::class, 'destroy']);
    Route::post('/events/{id}/generate-seats', [EventController::class, 'generateSeats']);
});
