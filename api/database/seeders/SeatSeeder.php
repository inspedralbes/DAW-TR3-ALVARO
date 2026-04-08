<?php

namespace Database\Seeders;

use App\Models\Seat;
use Illuminate\Database\Seeder;

class SeatSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $event = \App\Models\Event::create([
            'title' => 'Concierto Inaugural',
            'date' => now()->addDays(15),
            'description' => 'Un evento espectacular de prueba.',
        ]);

        $rows = ['A', 'B', 'C', 'D', 'E'];
        $numbersPerRow = 10;

        foreach ($rows as $row) {
            for ($i = 1; $i <= $numbersPerRow; $i++) {
                Seat::create([
                    'row' => $row,
                    'number' => $i,
                    'status' => 'available',
                    'price' => 50.00,
                    'event_id' => $event->id,
                ]);
            }
        }
    }
}
