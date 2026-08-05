<?php

namespace App\Http\Controllers;

/**
 * Vista global del owner: el "admin global". El owner navega toda la interfaz
 * de administrador (dashboard, préstamos, clientes, cobros, reportes…) pero
 * viendo los datos de TODOS los administradores juntos, en sólo lectura.
 * La marca vive en sesión y sólo aplica a usuarios con puesto owner
 * (ver User::esVistaGlobal / User::ADMIN_GLOBAL).
 */
class VistaGlobalController extends Controller
{
    public function entrar()
    {
        // Incompatible con una impersonación a medias: se parte de la sesión owner
        session()->forget(['impersonador_id', 'cartera_activa']);
        session(['vista_global' => true]);

        return redirect()->route('dashboard')
            ->with('success', 'Vista global activada: estás viendo los datos de todos los administradores (sólo lectura).');
    }

    public function salir()
    {
        session()->forget('vista_global');

        return redirect()->route('owner.dashboard')
            ->with('success', 'Saliste de la vista global.');
    }
}
