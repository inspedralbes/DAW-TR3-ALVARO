<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

// ── Auth routes (public) ──────────────────────────────────────────────────
Route::prefix('auth')->group(function () {
    Route::post('/register', [AuthController::class, 'register']);
    Route::post('/login',    [AuthController::class, 'login']);

    Route::middleware('auth:sanctum')->group(function () {
        Route::post('/logout', [AuthController::class, 'logout']);
        Route::get('/me',      [AuthController::class, 'me']);
    });
});

// ── Public routes ─────────────────────────────────────────────────────────
Route::get('/events',              [EventController::class, 'index']);
Route::get('/events/{event}/seats',[EventController::class, 'seats']);

Route::get('/seats',               [SeatController::class, 'index']);
Route::post('/seats/reserve',      [SeatController::class, 'reserve']);
Route::post('/seats/checkout',     [SeatController::class, 'checkout']);

// My Tickets — requires Auth
Route::middleware('auth:sanctum')->get('/auth/my-tickets', [SeatController::class, 'myTicketsByToken']);

// ── Admin routes (auth:sanctum + admin role) ──────────────────────────────
Route::prefix('admin')
    ->middleware(['auth:sanctum', 'ensure.admin'])
    ->group(function () {
        Route::get('/stats',                              [EventController::class, 'adminStats']);
        Route::post('/events',                            [EventController::class, 'store']);
        Route::put('/events/{id}',                        [EventController::class, 'update']);
        Route::delete('/events/{id}',                     [EventController::class, 'destroy']);
        Route::post('/events/{id}/generate-seats',        [EventController::class, 'generateSeats']);
    });
