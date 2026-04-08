<?php

namespace App\Http\Controllers;

use App\Events\SeatUpdated;
use App\Models\Seat;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

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

        // Check limit of 5 per session locally to prevent hoarding
        $currentReservationsCount = Seat::where('session_id', $request->session_id)
            ->whereIn('status', ['reserved', 'sold'])
            ->count();
            
        if ($currentReservationsCount >= 5) {
            return response()->json([
                'success' => false,
                'error' => 'Purchase limit of 5 seats reached.',
            ], 403);
        }

        $updated = Seat::where('id', $request->seat_id)
            ->where('status', 'available')
            ->update([
                'status' => 'reserved',
                'session_id' => $request->session_id,
                'reserved_at' => now(),
            ]);

        if (!$updated) {
            Log::warning("Conflict or invalid state for seat {$request->seat_id} by session {$request->session_id}");
            return response()->json([
                'success' => false,
                'error' => 'Seat already reserved or sold',
            ], 409);
        }

        $seat = Seat::find($request->seat_id);
        
        // Dispatch broadcast event
        event(new SeatUpdated($seat));
        Log::info("Seat reserved: {$seat->id} status: {$seat->status}");

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
            'seat_ids' => 'required|array|min:1|max:5',
            'seat_ids.*' => 'exists:seats,id',
            'session_id' => 'required|string',
            'name' => 'required|string|max:255',
            'email' => 'required|email|max:255',
        ]);

        try {
            DB::beginTransaction();

            // Find or create the user
            $user = User::firstOrCreate(
                ['email' => $request->email],
                ['name' => $request->name, 'password' => bcrypt(str()->random(16))]
            );

            // Fetch seats ensuring they belong to this session and are reserved
            $seatsToProcess = Seat::whereIn('id', $request->seat_ids)
                ->where('status', 'reserved')
                ->where('session_id', $request->session_id)
                ->lockForUpdate()
                ->get();

            if ($seatsToProcess->count() !== collect($request->seat_ids)->count()) {
                throw new \Exception('One or more seats are not reserved by you or have expired.');
            }

            // Check if the user globally has reached the limit of 5 purchases
            $userOwnedSeats = Seat::where('user_id', $user->id)->where('status', 'sold')->count();
            if ($userOwnedSeats + $seatsToProcess->count() > 5) {
                throw new \Exception('Limit of 5 purchased seats per person exceeded.');
            }

            // Update all seats
            foreach ($seatsToProcess as $seat) {
                $seat->status = 'sold';
                $seat->user_id = $user->id;
                $seat->save();
            }

            DB::commit();

            // Broadcast the updates for each seat
            foreach ($seatsToProcess as $seat) {
                event(new SeatUpdated($seat));
                Log::info("Seat sold: {$seat->id} to user: {$user->id}");
            }

            return response()->json([
                'success' => true,
                'message' => 'Seats sold successfully',
                'data' => [
                    'user_id' => $user->id,
                    'seats' => $seatsToProcess->pluck('id'),
                ]
            ]);

        } catch (\Exception $e) {
            DB::rollBack();
            Log::warning("Checkout failed for session {$request->session_id}: " . $e->getMessage());
            
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
            ], 409);
        }
    }
}
