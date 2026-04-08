<?php

namespace App\Http\Controllers;

use App\Events\SeatUpdated;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class SeatController extends Controller
{
    public function index()
    {
        return response()->json(Seat::all());
    }

    public function reserve(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
            'session_id' => 'required|string',
        ]);

        $updated = Seat::where('id', $request->seat_id)
            ->where('status', 'available')
            ->update([
                'status' => 'reserved',
                'session_id' => $request->session_id,
                'reserved_at' => now(),
            ]);

        if (!$updated) {
            \Log::warning("Conflict or invalid state for seat {$request->seat_id} by session {$request->session_id}");
            return response()->json([
                'success' => false,
                'error' => 'Seat already reserved or sold',
            ], 409);
        }

        $seat = Seat::find($request->seat_id);
        
        // Dispatch broadcast event
        event(new SeatUpdated($seat));
        \Log::info("Seat updated: {$seat->id} status: {$seat->status}");

        return response()->json([
            'success' => true,
            'message' => 'Seat reserved successfully',
            'data' => [
                'seat_id' => $seat->id,
                'status' => $seat->status,
                'expires_at' => $seat->reserved_at->addMinutes(5),
            ],
        ]);
    }

    public function checkout(Request $request)
    {
        $request->validate([
            'seat_id' => 'required|exists:seats,id',
            'session_id' => 'required|string',
        ]);

        $updated = Seat::where('id', $request->seat_id)
            ->where('status', 'reserved')
            ->where('session_id', $request->session_id)
            ->update([
                'status' => 'sold',
            ]);

        if (!$updated) {
            \Log::warning("Conflict or invalid state for seat {$request->seat_id} by session {$request->session_id}");
            return response()->json([
                'success' => false,
                'error' => 'Seat not reserved by you or expired',
            ], 409);
        }

        $seat = Seat::find($request->seat_id);
        
        event(new SeatUpdated($seat));
        \Log::info("Seat updated: {$seat->id} status: {$seat->status}");

        return response()->json([
            'success' => true,
            'message' => 'Seat sold successfully',
        ]);
    }
}
