<?php

namespace Database\Seeders;

use App\Models\Event;
use App\Models\Seat;
use Illuminate\Database\Seeder;

class DefaultEventSeeder extends Seeder
{
    public function run(): void
    {
        // Only create if no events exist
        if (Event::count() > 0) {
            $this->command->info('Ja existeixen esdeveniments, saltant seeder.');
            return;
        }

        $zones = [
            [
                'name'        => 'VIP',
                'color'       => '#95aaff',
                'price'       => 120.00,
                'rows'        => ['A', 'B'],
                'seatsPerRow' => 10,
            ],
            [
                'name'        => 'Preferent',
                'color'       => '#83fba5',
                'price'       => 75.00,
                'rows'        => ['C', 'D', 'E'],
                'seatsPerRow' => 15,
            ],
            [
                'name'        => 'General',
                'color'       => '#ffd16f',
                'price'       => 40.00,
                'rows'        => ['F', 'G', 'H', 'I', 'J'],
                'seatsPerRow' => 20,
            ],
        ];

        $event = Event::create([
            'title'       => 'NEON PULSE FESTIVAL 2026',
            'date'        => '2026-07-15 21:00:00',
            'venue'       => 'Palau Sant Jordi, Barcelona',
            'description' => 'El festival de música electrònica més gran de Catalunya.',
            'zones'       => $zones,
        ]);

        // Generate seats from zones
        $seats = [];
        foreach ($zones as $zone) {
            foreach ($zone['rows'] as $row) {
                for ($n = 1; $n <= $zone['seatsPerRow']; $n++) {
                    $seats[] = [
                        'event_id'   => $event->id,
                        'row'        => $row,
                        'number'     => $n,
                        'status'     => 'available',
                        'price'      => $zone['price'],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($seats, 100) as $chunk) {
            Seat::insert($chunk);
        }

        $total = count($seats);
        $this->command->info("Esdeveniment creat: '{$event->title}' amb {$total} seients.");
    }
}
