<?php
// Run: php artisan tinker < database/scripts/sync_roles.php

$users = App\Models\User::all();
foreach ($users as $u) {
    $role = $u->puesto;
    if ($role && in_array($role, ['admin', 'promo', 'collector', 'desembolso'])) {
        $u->syncRoles([$role]);
        echo "Assigned [{$role}] to [{$u->usuario}]" . PHP_EOL;
    }
}
echo "Done." . PHP_EOL;
