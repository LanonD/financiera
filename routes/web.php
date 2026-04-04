<?php
// ══════════════════════════════════════════════
//  ROUTER CENTRALIZADO
//  Todas las rutas del sistema definidas aquí
// ══════════════════════════════════════════════

$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

// Quitar el prefijo del subdirectorio si aplica
$base   = '/financiera_mvc/public';
$uri    = str_starts_with($uri, $base) ? substr($uri, strlen($base)) : $uri;
$uri    = $uri ?: '/';

// ── Definición de rutas ─────────────────────
// [METHOD, URI, Middleware, Controller, Method]
$routes = [
    // Auth
    ['GET',  '/login',      [],       'AuthController',      'showLogin'],
    ['POST', '/login',      [],       'AuthController',      'login'],
    ['GET',  '/logout',     ['auth'], 'AuthController',      'logout'],

    // Admin — Dashboard
    ['GET',  '/dashboard',  ['auth', 'role:admin'], 'DashboardController', 'index'],

    // Préstamos
    ['GET',  '/prestamos',  ['auth', 'role:admin,promo'], 'LoanController', 'index'],
    ['GET',  '/prestamos/detalle', ['auth', 'role:admin,promo'], 'LoanController', 'detail'],

    // Empleados
    ['GET',  '/empleados',  ['auth', 'role:admin'],  'EmployeeController', 'index'],
    ['POST', '/empleados/crear', ['auth', 'role:admin'], 'EmployeeController', 'create'],

    // Clientes
    ['GET',  '/clientes',   ['auth', 'role:admin,promo'], 'ClientController', 'index'],
    ['POST', '/clientes/crear', ['auth', 'role:promo'],   'ClientController', 'create'],

    // Cobros
    ['GET',  '/cobros',     ['auth', 'role:collector'],   'PaymentController', 'index'],
    ['POST', '/cobros/registrar', ['auth', 'role:collector'], 'PaymentController', 'register'],

    // Desembolsos
    ['GET',  '/desembolsos', ['auth', 'role:desembolso'], 'DisbursementController', 'index'],
    ['POST', '/desembolsos/confirmar', ['auth', 'role:desembolso'], 'DisbursementController', 'confirm'],

    // Búsqueda
    ['GET',  '/busqueda',   ['auth', 'role:admin'],  'SearchController',    'index'],

    // Calculadora
    ['GET',  '/calculadora', ['auth'],               'LoanController',     'calculator'],
    ['POST', '/calculadora', ['auth'],               'LoanController',     'calculate'],

    // API básica
    ['GET',  '/api/loans',   ['auth', 'role:admin'], 'LoanController',     'apiIndex'],
    ['GET',  '/api/clients', ['auth', 'role:admin'], 'ClientController',   'apiIndex'],
];

// ── Resolver ruta ───────────────────────────
$matched = false;

foreach ($routes as [$routeMethod, $routeUri, $middlewares, $controller, $action]) {
    if ($method === $routeMethod && $uri === $routeUri) {
        $matched = true;

        // Ejecutar middlewares
        foreach ($middlewares as $mw) {
            if ($mw === 'auth') {
                AuthMiddleware::check();
            } elseif (str_starts_with($mw, 'role:')) {
                $roles = explode(',', substr($mw, 5));
                AuthMiddleware::requireRole($roles);
            }
        }

        // Ejecutar controlador
        require_once ROOT_PATH . '/app/controllers/' . $controller . '.php';
        $ctrl = new $controller();
        $ctrl->$action();
        break;
    }
}

// Ruta raíz — redirigir según sesión
if (!$matched && $uri === '/') {
    AuthMiddleware::redirectByRole();
}

// 404
if (!$matched && $uri !== '/') {
    http_response_code(404);
    require_once ROOT_PATH . '/app/views/layouts/404.php';
}
