<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\User;

class OwnerSeeder extends Seeder
{
    public function run(): void
    {
        // Crea el super-usuario owner si no existe
        if (!User::where('usuario', 'owner')->exists()) {
            User::create([
                'usuario'  => 'owner',
                'password' => Hash::make('owner1234'),
                'puesto'   => 'owner',
                'activo'   => true,
            ]);
            $this->command->info('✅ Usuario owner creado — usuario: owner | contraseña: owner1234');
        } else {
            $this->command->warn('⚠  El usuario owner ya existe, no se creó otro.');
        }
    }
}
