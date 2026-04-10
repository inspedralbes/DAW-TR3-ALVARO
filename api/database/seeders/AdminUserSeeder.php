<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@ticketmonster.com'],
            [
                'name'     => 'Admin',
                'password' => Hash::make('admin1234'),
                'role'     => 'admin',
            ]
        );

        $this->command->info('Admin creat: admin@ticketmonster.com / admin1234');
    }
}
