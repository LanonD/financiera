<?php
class AuthMiddleware {

    // Verificar que el usuario esté autenticado
    public static function check(): void {
        if (!isset($_SESSION['id']) || !isset($_SESSION['puesto'])) {
            header('Location: ' . APP_URL . '/login');
            exit();
        }

        // Verificar que la sesión no haya expirado
        if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity']) > SESSION_LIFETIME) {
            session_destroy();
            header('Location: ' . APP_URL . '/login?error=session');
            exit();
        }

        $_SESSION['last_activity'] = time();
    }

    // Verificar que el usuario tenga el rol requerido
    public static function requireRole(array $roles): void {
        self::check();
        if (!in_array($_SESSION['puesto'], $roles)) {
            http_response_code(403);
            require_once ROOT_PATH . '/app/views/layouts/403.php';
            exit();
        }
    }

    // Redirigir a la vista correcta según rol
    public static function redirectByRole(): void {
        if (!isset($_SESSION['puesto'])) {
            header('Location: ' . APP_URL . '/login');
            exit();
        }

        $destinos = ROLE_REDIRECTS;
        $destino  = $destinos[$_SESSION['puesto']] ?? '/login';
        header('Location: ' . APP_URL . $destino);
        exit();
    }
}
