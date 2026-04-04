# PrestaCRM v2.0

Sistema de gestión de préstamos desarrollado en PHP puro con arquitectura MVC.

---

## Características

- Login seguro con rate limiting (bloqueo tras 5 intentos)
- Roles: `admin`, `promotor`, `cobrador`, `desembolso`
- Gestión de préstamos con tabla de amortización automática
- Vista de cobrador responsive (desktop + móvil)
- Registro de pagos completos y parciales
- Vista de desembolso con recopilación de documentos
- Calculadora de cuota fija (fórmula annuity)
- Búsqueda avanzada de clientes y empleados
- Arquitectura MVC limpia sin frameworks

---

## Requisitos

- PHP 8.0+
- MySQL 5.7+ / MariaDB
- Apache con mod_rewrite habilitado
- XAMPP (desarrollo local)

---

## Instalación

**1. Clonar o descomprimir el proyecto**
```
C:\xampp\htdocs\financiera_mvc\
```

**2. Crear la base de datos**
- Abrir phpMyAdmin en `http://localhost/phpmyadmin`
- Ir a **Importar** y seleccionar `financiera.sql`
- Click en **Continuar**

**3. Configurar la conexión**

Editar `config/database.php`:
```php
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');       // tu contraseña de MySQL
define('DB_NAME',     'financiera');
```

**4. Habilitar mod_rewrite en XAMPP**
- Abrir `C:\xampp\apache\conf\httpd.conf`
- Buscar `#LoadModule rewrite_module` y quitar el `#`
- Reiniciar Apache

**5. Abrir el sistema**
```
http://localhost/financiera_mvc/public/
```

---

## Usuarios de prueba

| Usuario | Contraseña | Rol |
|---|---|---|
| admin | password | Administrador |
| promotor1 | password | Promotor |
| cobrador1 | password | Cobrador |
| desembolso1 | password | Desembolso |

---

## Estructura del proyecto

```
financiera_mvc/
├── financiera.sql              ← Base de datos
├── config/
│   ├── app.php                 ← Configuración global
│   └── database.php            ← Conexión MySQL
├── public/
│   ├── index.php               ← Front controller (único punto de entrada)
│   ├── .htaccess               ← Rewrite rules
│   └── assets/
│       ├── css/main.css
│       └── js/
├── routes/
│   └── web.php                 ← Todas las rutas definidas aquí
├── app/
│   ├── controllers/            ← Manejan requests
│   │   ├── AuthController.php
│   │   ├── DashboardController.php
│   │   ├── LoanController.php
│   │   └── PaymentController.php
│   ├── models/                 ← Queries a la BD
│   │   ├── User.php
│   │   ├── Loan.php
│   │   ├── Payment.php
│   │   ├── Employee.php
│   │   └── Client.php
│   ├── services/               ← Lógica de negocio
│   │   └── LoanService.php     ← Cálculos de amortización
│   ├── middleware/
│   │   └── AuthMiddleware.php  ← Protección de rutas
│   └── views/
│       ├── auth/login.php
│       ├── admin/dashboard.php
│       ├── collector/cobros.php
│       └── layouts/
│           ├── header.php      ← Sidebar dinámico por rol
│           └── footer.php
└── storage/
    └── logs/                   ← Logs del sistema
```

---

## API básica

| Endpoint | Método | Descripción |
|---|---|---|
| `/api/loans` | GET | Lista todos los préstamos (JSON) |
| `/api/clients` | GET | Lista todos los clientes (JSON) |

---

## Seguridad implementada

- Prepared Statements en todas las queries (anti SQL Injection)
- `password_hash()` / `password_verify()` para contraseñas
- Sanitización de inputs con `htmlspecialchars`
- Rate limiting: bloqueo tras 5 intentos fallidos (5 min)
- `session_regenerate_id()` al hacer login
- Headers de seguridad: X-Frame-Options, X-XSS-Protection
- Acceso directo a archivos internos bloqueado via .htaccess
- Middleware de autenticación y roles en cada ruta

---

## Screenshots

> _(agregar capturas del sistema aquí)_

---

## Equipo

| Área | Responsable |
|---|---|
| Frontend / Diseño | — |
| Backend / BD | — |
