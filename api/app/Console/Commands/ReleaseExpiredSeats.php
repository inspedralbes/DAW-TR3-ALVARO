<?php

namespace App\Console\Commands;

use App\Events\SeatUpdated;
use App\Models\Seat;
use Illuminate\Console\Command;

class ReleaseExpiredSeats extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'seats:release-expired';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Release seats that have been reserved for more than 5 minutes';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $expiredSeats = Seat::where('status', 'reserved')
            ->where('reserved_at', '<', now()->subMinutes(5))
            ->get();

        foreach ($expiredSeats as $seat) {
            $seat->update([
                'status' => 'available',
                'session_id' => null,
                'reserved_at' => null,
            ]);

            // Dispatch broadcast event for each released seat
            event(new SeatUpdated($seat));
            
            $this->info("Released seat {$seat->row}{$seat->number}");
        }

        $this->info('Cleanup completed.');
    }
}
