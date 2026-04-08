<?php

use App\Models\Seat;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('a user can reserve an available seat', function () {
    $seat = Seat::create([
        'row' => 'A',
        'number' => 1,
        'status' => 'available',
        'price' => 50.00
    ]);

    $response = $this->postJson('/api/seats/reserve', [
        'seat_id' => $seat->id,
        'session_id' => 'test-session-123'
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('success', true)
             ->assertJsonPath('data.status', 'reserved');

    $seat->refresh();
    expect($seat->status)->toBe('reserved');
    expect($seat->session_id)->toBe('test-session-123');
    expect($seat->reserved_at)->not->toBeNull();
});

test('a user cannot reserve a seat that is already reserved', function () {
    $seat = Seat::create([
        'row' => 'A',
        'number' => 1,
        'status' => 'reserved',
        'session_id' => 'other-session',
        'reserved_at' => now(),
        'price' => 50.00
    ]);

    $response = $this->postJson('/api/seats/reserve', [
        'seat_id' => $seat->id,
        'session_id' => 'my-session'
    ]);

    $response->assertStatus(409);
});

test('a user can checkout a reserved seat', function () {
    $seat = Seat::create([
        'row' => 'A',
        'number' => 1,
        'status' => 'reserved',
        'session_id' => 'my-session',
        'reserved_at' => now(),
        'price' => 50.00
    ]);

    $response = $this->postJson('/api/seats/checkout', [
        'seat_id' => $seat->id,
        'session_id' => 'my-session'
    ]);

    $response->assertStatus(200)
             ->assertJsonPath('success', true);

    $seat->refresh();
    expect($seat->status)->toBe('sold');
});

test('a user cannot checkout a seat reserved by someone else', function () {
    $seat = Seat::create([
        'row' => 'A',
        'number' => 1,
        'status' => 'reserved',
        'session_id' => 'other-session',
        'reserved_at' => now(),
        'price' => 50.00
    ]);

    $response = $this->postJson('/api/seats/checkout', [
        'seat_id' => $seat->id,
        'session_id' => 'my-session'
    ]);

    $response->assertStatus(409);
});
