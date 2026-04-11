<?php
require_once ROOT_PATH . '/app/models/Employee.php';
require_once ROOT_PATH . '/app/models/User.php';

class EmployeeController {

    private Employee $empModel;
    private User     $userModel;

    public function __construct() {
        $this->empModel  = new Employee();
        $this->userModel = new User();
    }

    public function index(): void {
        $promotores  = $this->empModel->getByType('promo');
        $cobradores  = $this->empModel->getByType('collector');
        $desembolso  = $this->empModel->getByType('desembolso');
        $pageTitle   = 'Empleados';
        $breadcrumb  = 'Administración · Gestión de personal';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/empleados.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) {
            header('Location: ' . APP_URL . '/empleados');
            exit();
        }
        $empleado = $this->empModel->findById($id);
        if (!$empleado) {
            header('Location: ' . APP_URL . '/empleados?error=noencontrado');
            exit();
        }

        require_once ROOT_PATH . '/app/models/Loan.php';
        require_once ROOT_PATH . '/app/models/Payment.php';
        $loanModel = new Loan();
        $paymentModel = new Payment();

        $prestamosActivos = [];
        $historial = [];
        $pendientes = [];

        if ($empleado['puesto'] === 'promo') {
            $prestamosActivos = $loanModel->getByPromotor($id);
            $pendientes = $loanModel->getPendingDisbursementByPromotor($id);
        } elseif ($empleado['puesto'] === 'collector') {
            $prestamosActivos = $loanModel->getByCollector($id, false);
            // Pagos cobrados por este cobrador (historial simple)
            $db = Database::connect();
            $stmt = $db->prepare("
                SELECT pg.*, p.id AS prestamo_id, c.nombre AS cliente_nombre 
                FROM pagos pg 
                JOIN prestamos p ON pg.prestamo_id = p.id 
                JOIN clientes_f c ON p.cliente_id = c.id 
                WHERE pg.cobrador_id = ? AND pg.estatus IN ('Pagado', 'Parcial') 
                ORDER BY pg.fecha_pago DESC LIMIT 50
            ");
            $stmt->bind_param("i", $id);
            $stmt->execute();
            $historial = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
            $stmt->close();
        }

        $pageTitle  = 'Detalle Empleado: ' . htmlspecialchars($empleado['nombre']);
        $breadcrumb = 'Administración · Empleados · Detalle';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/empleado_detalle.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function create(): void {
        $usuario  = htmlspecialchars(trim($_POST['usuario']   ?? ''));
        $password = $_POST['password'] ?? '';
        $puesto   = $_POST['puesto']   ?? 'promo';
        $nombre   = htmlspecialchars(trim($_POST['nombre']    ?? ''));

        if (!$usuario || !$password || !$nombre) {
            header('Location: ' . APP_URL . '/empleados?error=campos');
            exit();
        }

        $uid = $this->userModel->create($usuario, $password, $puesto);
        $this->empModel->create([
            'nombre'   => $nombre,
            'celular'  => $_POST['celular'] ?? '',
            'email'    => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'fijo'     => '', 'direccion' => $_POST['direccion'] ?? '',
            'puesto'   => $puesto,
            'rango'    => $_POST['rango'] ?? 'Bronce',
            'capacidad' => (float)($_POST['capacidad'] ?? 0),
            'latitud'  => null, 'longitud' => null,
            'contacto_nombre'=>'','contacto_telefono'=>'','contacto_direccion'=>'',
            'contacto_nombre2'=>'','contacto_telefono2'=>'','contacto_direccion2'=>'',
        ], $uid);

        header('Location: ' . APP_URL . '/empleados');
        exit();
    }

    public function delete(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) {
            header('Location: ' . APP_URL . '/empleados?error=id');
            exit();
        }
        $this->empModel->softDelete($id);
        header('Location: ' . APP_URL . '/empleados?ok=eliminado');
        exit();
    }

    public function update(): void {
        $id        = (int)($_POST['id'] ?? 0);
        $nombre    = htmlspecialchars(trim($_POST['nombre']   ?? ''));
        $celular   = htmlspecialchars(trim($_POST['celular']  ?? ''));
        $email     = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        $puesto    = $_POST['puesto']    ?? 'promo';
        $rango     = $_POST['rango']     ?? 'Bronce';
        $capacidad = (float)($_POST['capacidad'] ?? 0);
        $usuario   = trim($_POST['usuario'] ?? '');

        if (!$id || !$nombre) {
            header('Location: ' . APP_URL . '/empleados?error=datos'); exit();
        }

        $this->empModel->update($id, compact('nombre','celular','email','puesto','rango','capacidad'));

        // Actualizar nombre de usuario si se proporcionó
        if ($usuario !== '') {
            $emp = $this->empModel->findById($id);
            if ($emp && !empty($emp['usuario_id'])) {
                $ok = $this->userModel->updateUsername((int)$emp['usuario_id'], $usuario);
                if (!$ok) {
                    header('Location: ' . APP_URL . '/empleados?error=usuario_duplicado'); exit();
                }
            }
        }

        header('Location: ' . APP_URL . '/empleados?ok=actualizado'); exit();
    }
}
