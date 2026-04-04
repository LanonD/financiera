<?php
define('APP_NAME',    'PrestaCRM');
define('APP_VERSION', '2.0.0');
define('APP_URL',     'http://localhost/financiera_mvc/public');
define('APP_ENV',     'development');  // cambiar a 'production' en hosting

// Roles válidos del sistema
define('ROLES', ['admin', 'promo', 'collector', 'desembolso']);

// Rutas por rol al hacer login
define('ROLE_REDIRECTS', [
    'admin'      => '/dashboard',
    'promo'      => '/prestamos',
    'collector'  => '/cobros',
    'desembolso' => '/desembolsos',
]);

// Configuración de sesión
define('SESSION_LIFETIME', 3600);   // 1 hora
define('MAX_LOGIN_ATTEMPTS', 5);
define('LOGIN_LOCKOUT_TIME', 300);  // 5 minutos

// Mostrar errores solo en desarrollo
if (APP_ENV === 'development') {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
} else {
    ini_set('display_errors', 0);
    error_reporting(0);
}
