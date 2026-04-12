<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $adminEmail = env('ADMIN_EMAIL', 'admin@ticketmonster.com');
        $adminPass = env('ADMIN_PASSWORD', 'admin1234');

        User::updateOrCreate(
            ['email' => $adminEmail],
            [
                'name'     => 'Administrador Principal',
                'password' => Hash::make($adminPass),
                'role'     => 'admin',
            ]
        );

        $this->command->info("Admin creat o actualitzat: {$adminEmail} / [Contrasenya Oculta]");
    }
}
