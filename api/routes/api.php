<?php

use App\Http\Controllers\SeatController;
use Illuminate\Support\Facades\Route;

Route::get('/seats', [SeatController::class, 'index']);
Route::post('/seats/reserve', [SeatController::class, 'reserve']);
Route::post('/seats/checkout', [SeatController::class, 'checkout']);
