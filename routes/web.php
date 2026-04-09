<?php
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$method = $_SERVER['REQUEST_METHOD'];

$base = '/financiera_mvc/public';
$uri  = str_starts_with($uri, $base) ? substr($uri, strlen($base)) : $uri;
$uri  = $uri ?: '/';

$routes = [
    ['GET',  '/login',                 [],                          'AuthController',        'showLogin'],
    ['POST', '/login',                 [],                          'AuthController',        'login'],
    ['GET',  '/logout',                ['auth'],                    'AuthController',        'logout'],
    ['GET',  '/dashboard',             ['auth','role:admin'],       'DashboardController',   'index'],
    ['GET',  '/prestamos',             ['auth','role:admin,promo'], 'LoanController',        'index'],
    ['GET',  '/prestamos/detalle',     ['auth','role:admin,promo'], 'LoanController',        'detail'],
    ['POST', '/prestamos/crear',       ['auth','role:promo'],       'LoanController',        'createLoan'],
    ['POST', '/prestamos/editar',      ['auth','role:admin,promo'], 'LoanController',        'edit'],
    ['POST', '/prestamos/meta',        ['auth','role:admin'],       'LoanController',        'updateMeta'],
    ['GET',  '/clientes/editar',       ['auth','role:admin'],       'ClientController',      'editForm'],
    ['POST', '/clientes/editar',       ['auth','role:admin'],       'ClientController',      'update'],
    ['POST', '/empleados/editar',      ['auth','role:admin'],       'EmployeeController',    'update'],
    ['GET',  '/empleados',             ['auth','role:admin'],       'EmployeeController',    'index'],
    ['POST', '/empleados/crear',       ['auth','role:admin'],       'EmployeeController',    'create'],
    ['POST', '/empleados/eliminar',    ['auth','role:admin'],       'EmployeeController',    'delete'],
    ['GET',  '/clientes',              ['auth','role:admin,promo'], 'ClientController',      'index'],
    ['GET',  '/clientes/detalle',      ['auth','role:admin,promo'], 'ClientController',      'detail'],
    ['POST', '/clientes/crear',        ['auth','role:promo'],       'ClientController',      'create'],
    ['GET',  '/cobros',                ['auth','role:collector,admin'],            'PaymentController',     'index'],
    ['POST', '/cobros/registrar',      ['auth','role:collector,admin'],            'PaymentController',     'register'],
    ['GET',  '/desembolsos',           ['auth','role:desembolso,admin,promo'],     'DisbursementController','index'],
    ['POST', '/desembolsos/confirmar', ['auth','role:desembolso,admin,promo'],     'DisbursementController','confirm'],
    ['GET',  '/reportes',              ['auth','role:admin'],                      'ReporteController',     'index'],
    ['GET',  '/busqueda',              ['auth','role:admin'],       'SearchController',      'index'],
    ['GET',  '/calculadora',           ['auth'],                    'LoanController',        'calculator'],
    ['POST', '/calculadora',           ['auth'],                    'LoanController',        'calculate'],
    ['GET',  '/api/loans',             ['auth','role:admin'],       'LoanController',        'apiIndex'],
    ['GET',  '/api/clients',           ['auth','role:admin'],       'ClientController',      'apiIndex'],
];

$matched = false;

foreach ($routes as [$routeMethod, $routeUri, $middlewares, $controller, $action]) {
    if ($method === $routeMethod && $uri === $routeUri) {
        $matched = true;
        foreach ($middlewares as $mw) {
            if ($mw === 'auth') {
                AuthMiddleware::check();
            } elseif (str_starts_with($mw, 'role:')) {
                AuthMiddleware::requireRole(explode(',', substr($mw, 5)));
            }
        }
        require_once ROOT_PATH . '/app/controllers/' . $controller . '.php';
        $ctrl = new $controller();
        $ctrl->$action();
        break;
    }
}

if (!$matched && $uri === '/') { AuthMiddleware::redirectByRole(); }

if (!$matched && $uri !== '/') {
    http_response_code(404);
    require_once ROOT_PATH . '/app/views/layouts/404.php';
}
