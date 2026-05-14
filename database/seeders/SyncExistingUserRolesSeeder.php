<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;

class SyncExistingUserRolesSeeder extends Seeder
{
    public function run(): void
    {
        // Ensure all roles exist first
        $roles = ['admin', 'promo', 'collector', 'desembolso'];
        foreach ($roles as $role) {
            Role::firstOrCreate(['name' => $role, 'guard_name' => 'web']);
        }

        // Assign the Spatie role matching the existing puesto column
        $users = User::all();
        foreach ($users as $user) {
            $puesto = $user->puesto;
            if ($puesto && in_array($puesto, $roles)) {
                $user->syncRoles([$puesto]);
                $this->command->info("Assigned [{$puesto}] to [{$user->usuario}]");
            }
        }

        $this->command->info('All existing users now have Spatie roles.');
    }
}
