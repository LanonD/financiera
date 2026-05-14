<?php

namespace Database\Seeders;

use App\Models\Empleado;
use App\Models\User;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;

class RolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        // Crear los 4 roles
        $roles = ['admin', 'promo', 'collector', 'desembolso'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Crear usuario admin por defecto
        $admin = User::firstOrCreate(
            ['usuario' => 'admin'],
            [
                'password' => bcrypt('admin123'),
                'puesto'   => 'admin',
                'activo'   => true,
            ]
        );
        $admin->assignRole('admin');

        // Crear perfil de empleado para el admin
        Empleado::firstOrCreate(
            ['usuario_id' => $admin->id],
            [
                'nombre'            => 'Administrador',
                'puesto'            => 'admin',
                'rango'             => 'Diamante',
                'capacidad_maxima'  => 9999,
                'monto_ocupado'     => 0,
                'activo'            => true,
            ]
        );

        $this->command->info('Roles creados: ' . implode(', ', $roles));
        $this->command->info('Usuario admin creado — usuario: admin / contraseña: admin123');
    }
}
