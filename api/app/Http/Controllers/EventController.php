<?php

namespace App\Http\Controllers;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class EventController extends Controller
{
    // ── Public endpoints ──────────────────────────────────────────────────

    public function index()
    {
        return response()->json(
            Event::withCount('seats')->orderBy('date')->get()
        );
    }

    public function seats($id)
    {
        $event = Event::findOrFail($id);
        return response()->json($event->seats);
    }

    // ── Admin CRUD ────────────────────────────────────────────────────────

    public function store(Request $request)
    {
        $request->validate([
            'title'       => 'required|string|max:255',
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'venue'       => 'nullable|string|max:255',
            'capacity'    => 'nullable|integer|min:1',
            'zones'       => 'nullable|array',
        ]);

        $event = Event::create(
            $request->only(['title', 'date', 'description', 'venue', 'capacity', 'zones'])
        );

        return response()->json(['success' => true, 'event' => $event], 201);
    }

    public function update(Request $request, $id)
    {
        $event = Event::findOrFail($id);

        $request->validate([
            'title'       => 'required|string|max:255',
            'date'        => 'required|date',
            'description' => 'nullable|string',
            'venue'       => 'nullable|string|max:255',
            'capacity'    => 'nullable|integer|min:1',
            'zones'       => 'nullable|array',
        ]);

        $event->update(
            $request->only(['title', 'date', 'description', 'venue', 'capacity', 'zones'])
        );

        return response()->json(['success' => true, 'event' => $event]);
    }

    public function destroy($id)
    {
        $event = Event::findOrFail($id);
        $event->seats()->delete();
        $event->delete();

        return response()->json(['success' => true, 'message' => 'Event deleted']);
    }

    // ── Seat generation ───────────────────────────────────────────────────

    public function generateSeats($id)
    {
        $event = Event::findOrFail($id);

        if (empty($event->zones)) {
            return response()->json([
                'success' => false,
                'error'   => 'No zones defined for this event.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            // Remove existing seats for this event
            $event->seats()->delete();

            $seats = [];
            foreach ($event->zones as $zone) {
                $rows        = $zone['rows'] ?? [];
                $seatsPerRow = $zone['seatsPerRow'] ?? 10;
                $price       = $zone['price'] ?? 0;

                foreach ($rows as $row) {
                    for ($n = 1; $n <= $seatsPerRow; $n++) {
                        $seats[] = [
                            'event_id'    => $event->id,
                            'row'         => strtoupper(trim($row)),
                            'number'      => $n,
                            'status'      => 'available',
                            'price'       => $price,
                            'created_at'  => now(),
                            'updated_at'  => now(),
                        ];
                    }
                }
            }

            // Bulk insert in chunks of 100
            foreach (array_chunk($seats, 100) as $chunk) {
                Seat::insert($chunk);
            }

            DB::commit();

            return response()->json([
                'success'     => true,
                'message'     => count($seats) . ' seients generats correctament.',
                'seats_count' => count($seats),
            ]);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('generateSeats failed: ' . $e->getMessage());

            return response()->json([
                'success' => false,
                'error'   => $e->getMessage(),
            ], 500);
        }
    }

    // ── Admin stats ───────────────────────────────────────────────────────

    public function adminStats(Request $request)
    {
        $queryStats = Seat::select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'));
        if ($request->has('event_id') && $request->event_id) {
            $queryStats->where('event_id', $request->event_id);
        }
        $stats = $queryStats->groupBy('status')->get()->keyBy('status');

        $available = $stats->get('available')?->count ?? 0;
        $reserved  = $stats->get('reserved')?->count ?? 0;
        $sold      = $stats->get('sold')?->count ?? 0;
        $total     = $available + $reserved + $sold;

        $queryRes = Seat::where('status', 'reserved')
            ->select('id', 'event_id', 'row', 'number', 'session_id', 'reserved_at', 'price');
        if ($request->has('event_id') && $request->event_id) {
            $queryRes->where('event_id', $request->event_id);
        }
        $activeReservations = $queryRes->orderBy('reserved_at', 'desc')->take(20)->get();

        return response()->json([
            'stats'               => compact('available', 'reserved', 'sold', 'total'),
            'active_reservations' => $activeReservations,
        ]);
    }

    public function adminReports(Request $request)
    {
        $query = \App\Models\Seat::query();
        if ($request->has('event_id') && $request->event_id) {
            $query->where('event_id', $request->event_id);
        }

        // Revenue calculations
        $totalRevenue = (clone $query)->where('status', 'sold')->sum('price');

        $seatsStats = (clone $query)->select('status', \Illuminate\Support\Facades\DB::raw('count(*) as count'))->groupBy('status')->get()->keyBy('status');
        $soldCount = $seatsStats->get('sold')?->count ?? 0;

        $averagePerTicket = $soldCount > 0 ? $totalRevenue / $soldCount : 0;
        $occupancyPercentage = 0;

        $available = $seatsStats->get('available')?->count ?? 0;
        $reserved = $seatsStats->get('reserved')?->count ?? 0;
        $totalSeats = $available + $reserved + $soldCount;
        
        if ($totalSeats > 0) {
            $occupancyPercentage = round(($soldCount / $totalSeats) * 100, 2);
        }

        // Evolution of sales (grouped by day calculated from updated_at)
        $salesEvolution = (clone $query)
            ->where('status', 'sold')
            ->select(\Illuminate\Support\Facades\DB::raw('DATE(updated_at) as date'), \Illuminate\Support\Facades\DB::raw('count(*) as count'), \Illuminate\Support\Facades\DB::raw('sum(price) as revenue'))
            ->groupBy('date')
            ->orderBy('date', 'asc')
            ->get();

        return response()->json([
            'totalRevenue' => round($totalRevenue, 2),
            'averagePerTicket' => round($averagePerTicket, 2),
            'occupancyPercentage' => $occupancyPercentage,
            'soldCount' => $soldCount,
            'salesEvolution' => $salesEvolution
        ]);
    }
}
