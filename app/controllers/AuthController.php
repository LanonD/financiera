<?php
require_once ROOT_PATH . '/app/models/User.php';

class AuthController {

    private User $userModel;

    public function __construct() {
        $this->userModel = new User();
    }

    // GET /login
    public function showLogin(): void {
        // Si ya está logueado, redirigir
        if (isset($_SESSION['id'])) {
            AuthMiddleware::redirectByRole();
        }
        $error    = $_GET['error']    ?? '';
        $intentos = (int)($_GET['intentos'] ?? 0);
        $min      = (int)($_GET['min']      ?? 5);
        require_once ROOT_PATH . '/app/views/auth/login.php';
    }

    // POST /login
    public function login(): void {
        // Headers de seguridad
        header("X-Frame-Options: DENY");
        header("X-Content-Type-Options: nosniff");

        $user = $this->sanitize($_POST['user'] ?? '');
        $pwd  = $_POST['pwd'] ?? '';

        // Validar rate limiting
        $ip  = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $key = 'login_' . md5($ip);

        $_SESSION[$key . '_intentos'] = $_SESSION[$key . '_intentos'] ?? 0;
        $_SESSION[$key . '_bloqueo']  = $_SESSION[$key . '_bloqueo']  ?? 0;

        // Verificar bloqueo
        if ($_SESSION[$key . '_bloqueo'] > 0) {
            $restantes = ($_SESSION[$key . '_bloqueo'] + LOGIN_LOCKOUT_TIME) - time();
            if ($restantes > 0) {
                $min = ceil($restantes / 60);
                $this->redirect("/login?error=bloqueado&min=$min");
            }
            $_SESSION[$key . '_intentos'] = 0;
            $_SESSION[$key . '_bloqueo']  = 0;
        }

        // Validar formato del usuario
        if (empty($user) || empty($pwd)) {
            $this->redirect('/login?error=empty');
        }

        if (strlen($user) > 60 || !preg_match('/^[a-zA-Z0-9._\-áéíóúÁÉÍÓÚñÑ]+$/', $user)) {
            $this->redirect('/login?error=format');
        }

        if (strlen($pwd) < 4 || strlen($pwd) > 128) {
            $this->redirect('/login?error=password');
        }

        // Buscar usuario
        $fila = $this->userModel->findByUsername($user);

        if ($fila && password_verify($pwd, $fila['password'])) {
            // Login exitoso
            session_regenerate_id(true);
            $_SESSION[$key . '_intentos'] = 0;
            $_SESSION[$key . '_bloqueo']  = 0;

            $_SESSION['id']            = (int)$fila['id'];
            $_SESSION['usuario']       = $fila['usuario'];
            $_SESSION['puesto']        = $fila['puesto'];
            $_SESSION['last_activity'] = time();

            $destinos = ROLE_REDIRECTS;
            $this->redirect($destinos[$fila['puesto']] ?? '/login');
        }

        // Login fallido
        $_SESSION[$key . '_intentos']++;
        if ($_SESSION[$key . '_intentos'] >= MAX_LOGIN_ATTEMPTS) {
            $_SESSION[$key . '_bloqueo'] = time();
            $this->redirect('/login?error=bloqueado&min=' . ceil(LOGIN_LOCKOUT_TIME / 60));
        }

        $restantes = MAX_LOGIN_ATTEMPTS - $_SESSION[$key . '_intentos'];
        $this->redirect("/login?error=password&intentos=$restantes");
    }

    // GET /logout
    public function logout(): void {
        session_destroy();
        header('Location: ' . APP_URL . '/login');
        exit();
    }

    private function sanitize(string $input): string {
        return htmlspecialchars(stripslashes(trim($input)), ENT_QUOTES, 'UTF-8');
    }

    private function redirect(string $path): void {
        header('Location: ' . APP_URL . $path);
        exit();
    }
}
