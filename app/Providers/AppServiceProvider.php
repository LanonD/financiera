<?php

namespace App\Providers;

use App\Models\User;
use Illuminate\Database\Eloquent\Builder as EloquentBuilder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        // Filtro multi-tenant por administrador. Equivale a
        // where($columna, $adminId), salvo cuando el owner está en "vista
        // global" (adminId() === User::ADMIN_GLOBAL): ahí no filtra y la
        // consulta abarca los datos de TODOS los administradores.
        $deAdmin = function ($adminId, string $columna = 'admin_id') {
            if ($adminId === User::ADMIN_GLOBAL) {
                return $this;
            }
            return $this->where($columna, $adminId);
        };

        QueryBuilder::macro('deAdmin', $deAdmin);
        EloquentBuilder::macro('deAdmin', $deAdmin);
    }
}
