<?php

use App\Http\Controllers\EventController;
use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

Route::get('/events', [EventController::class, 'index']);
Route::get('/events/{event}/seats', [EventController::class, 'seats']);

Route::get('/seats', [SeatController::class, 'index']);
Route::post('/seats/reserve', [SeatController::class, 'reserve']);
Route::post('/seats/checkout', [SeatController::class, 'checkout']);

Route::get('/tickets', [SeatController::class, 'myTickets']);
