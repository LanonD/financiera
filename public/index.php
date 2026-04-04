<?php
define('ROOT_PATH', dirname(__DIR__));

// Autoload de clases
spl_autoload_register(function ($class) {
    $paths = [
        ROOT_PATH . '/app/controllers/' . $class . '.php',
        ROOT_PATH . '/app/models/'      . $class . '.php',
        ROOT_PATH . '/app/services/'    . $class . '.php',
        ROOT_PATH . '/app/middleware/'  . $class . '.php',
    ];
    foreach ($paths as $path) {
        if (file_exists($path)) { require_once $path; return; }
    }
});

require_once ROOT_PATH . '/config/app.php';
require_once ROOT_PATH . '/config/database.php';

// Cargar middleware ANTES del router
require_once ROOT_PATH . '/app/middleware/AuthMiddleware.php';

session_set_cookie_params([
    'lifetime' => SESSION_LIFETIME,
    'path'     => '/',
    'secure'   => false,
    'httponly' => true,
    'samesite' => 'Strict',
]);
session_start();

require_once ROOT_PATH . '/routes/web.php';
