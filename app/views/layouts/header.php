<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@300;400;500;600&family=DM+Mono:wght@400;500&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?= APP_URL ?>/assets/css/main.css">
    <title><?= APP_NAME ?> — <?= $pageTitle ?? 'Panel' ?></title>
</head>
<body>

<?php
$puesto  = $_SESSION['puesto']  ?? '';
$usuario = $_SESSION['usuario'] ?? '';

// Menús por rol
$menus = [
    'admin' => [
        ['href' => '/dashboard',   'label' => 'Vista general', 'icon' => 'grid'],
        ['href' => '/reportes',    'label' => 'Reportes',      'icon' => 'report'],
        ['href' => '/empleados',   'label' => 'Empleados',     'icon' => 'user'],
        ['href' => '/clientes',    'label' => 'Clientes',      'icon' => 'users'],
        ['href' => '/prestamos',   'label' => 'Préstamos',     'icon' => 'file'],
        ['href' => '/desembolsos', 'label' => 'Desembolsos',   'icon' => 'cash'],
        ['href' => '/cobros/asignar','label' => 'Asignar cobros','icon' => 'assign'],
        ['href' => '/cobros',      'label' => 'Cobros',        'icon' => 'check'],
        ['href' => '/busqueda',    'label' => 'Búsqueda',      'icon' => 'search'],
        ['href' => '/calculadora',  'label' => 'Calculadora 1',  'icon' => 'calc'],
        ['href' => '/calculadora2', 'label' => 'Calculadora 2',  'icon' => 'calc2'],
    ],
    'promo' => [
        ['href' => '/prestamos',     'label' => 'Mis préstamos',  'icon' => 'file'],
        ['href' => '/clientes',      'label' => 'Mis clientes',   'icon' => 'users'],
        ['href' => '/cobros/asignar','label' => 'Asignar cobros', 'icon' => 'assign'],
        ['href' => '/desembolsos',   'label' => 'Entregar',       'icon' => 'cash'],
        ['href' => '/calculadora',   'label' => 'Calculadora 1',  'icon' => 'calc'],
        ['href' => '/calculadora2',  'label' => 'Calculadora 2',  'icon' => 'calc2'],
    ],
    'collector' => [
        ['href' => '/cobros',     'label' => 'Mis cobros',    'icon' => 'check'],
    ],
    'desembolso' => [
        ['href' => '/desembolsos','label' => 'Desembolsos',   'icon' => 'cash'],
    ],
];

$navItems   = $menus[$puesto] ?? [];
$currentUri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$base       = '/financiera_mvc/public';

$icons = [
    'grid'   => '<svg viewBox="0 0 16 16" fill="currentColor"><rect x="1" y="1" width="6" height="6" rx="1.5"/><rect x="9" y="1" width="6" height="6" rx="1.5"/><rect x="1" y="9" width="6" height="6" rx="1.5"/><rect x="9" y="9" width="6" height="6" rx="1.5"/></svg>',
    'user'   => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="8" cy="5" r="3"/><path d="M2 14c0-3.314 2.686-6 6-6s6 2.686 6 6"/></svg>',
    'users'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><circle cx="11" cy="5" r="2.5"/><path d="M15 14c0-2.761-2.239-5-5-5"/></svg>',
    'file'   => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><rect x="2" y="3" width="12" height="10" rx="1.5"/><path d="M5 7h6M5 10h4"/></svg>',
    'search' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round"><circle cx="6.5" cy="6.5" r="4.5"/><path d="M11.5 11.5L15 15"/></svg>',
    'calc'   => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 8h6M8 5v6"/></svg>',
    'check'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8l4 4 8-8"/></svg>',
    'cash'   => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><path d="M2 8h12M9 4l4 4-4 4"/></svg>',
    'report' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="1.5"/><path d="M5 10V8M8 10V6M11 10V4"/></svg>',
    'assign' => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><circle cx="6" cy="5" r="2.5"/><path d="M1 14c0-2.761 2.239-5 5-5"/><path d="M10 8l2 2 3-3"/></svg>',
    'calc2'  => '<svg viewBox="0 0 16 16" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round"><rect x="2" y="2" width="12" height="12" rx="2"/><path d="M5 6h6M5 10h4M11 10h.01"/></svg>',
];
?>

<aside class="sidebar">
    <div class="sidebar-logo">
        <div class="logo-mark">
            <svg viewBox="0 0 14 14" fill="white"><path d="M7 1L2 4v6l5 3 5-3V4L7 1z"/></svg>
        </div>
        <span class="logo-text"><?= APP_NAME ?></span>
    </div>
    <nav class="sidebar-nav">
        <span class="nav-section-label">Panel</span>
        <?php foreach ($navItems as $item):
            $isActive = str_contains($currentUri, $base . $item['href']);
        ?>
        <a class="nav-item <?= $isActive ? 'active' : '' ?>" href="<?= APP_URL . $item['href'] ?>">
            <?= $icons[$item['icon']] ?? '' ?>
            <?= $item['label'] ?>
        </a>
        <?php endforeach; ?>
    </nav>
    <div class="sidebar-footer">
        <div class="user-avatar"><?= strtoupper(substr($usuario, 0, 2)) ?></div>
        <div class="user-info">
            <div class="user-name"><?= htmlspecialchars($usuario) ?></div>
            <div class="user-role"><?= ucfirst($puesto) ?></div>
        </div>
    </div>
</aside>

<div class="main-wrapper">
    <header class="topbar">
        <div class="topbar-left">
            <h1><?= $pageTitle ?? '' ?></h1>
            <div class="breadcrumb"><?= $breadcrumb ?? '' ?></div>
        </div>
        <div class="topbar-right">
            <a href="<?= APP_URL ?>/logout" class="btn-secondary" style="text-decoration:none">Cerrar sesión</a>
        </div>
    </header>
    <main class="content">
