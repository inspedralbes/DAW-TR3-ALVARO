<?php

use App\Models\Seat;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Artisan;

uses(RefreshDatabase::class);

test('expired seats are released', function () {
    // Seat reserved 6 minutes ago
    $expiredSeat = Seat::create([
        'row' => 'A',
        'number' => 1,
        'status' => 'reserved',
        'session_id' => 'expired-session',
        'reserved_at' => now()->subMinutes(6),
        'price' => 50.00
    ]);

    // Seat reserved 2 minutes ago (not expired)
    $validSeat = Seat::create([
        'row' => 'A',
        'number' => 2,
        'status' => 'reserved',
        'session_id' => 'valid-session',
        'reserved_at' => now()->subMinutes(2),
        'price' => 50.00
    ]);

    Artisan::call('seats:release-expired');

    $expiredSeat->refresh();
    $validSeat->refresh();

    expect($expiredSeat->status)->toBe('available');
    expect($expiredSeat->session_id)->toBeNull();
    expect($expiredSeat->reserved_at)->toBeNull();

    expect($validSeat->status)->toBe('reserved');
    expect($validSeat->session_id)->toBe('valid-session');
});
