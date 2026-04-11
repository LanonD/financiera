<?php
require_once ROOT_PATH . '/app/models/Payment.php';
require_once ROOT_PATH . '/app/models/Employee.php';
require_once ROOT_PATH . '/app/models/Loan.php';

class PaymentController {

    private Payment  $paymentModel;
    private Employee $employeeModel;

    public function __construct() {
        $this->paymentModel  = new Payment();
        $this->employeeModel = new Employee();
    }

    public function index(): void {
        $puesto = $_SESSION['puesto'] ?? '';

        if ($puesto === 'admin') {
            // Admin ve todos los préstamos activos, sin filtro de fecha
            $cobrador   = ['nombre' => 'Admin', 'rango' => '—', 'capacidad_maxima' => 999999999];
            $prestamos  = $this->paymentModel->getPendingByCollector(0, false);
            $pageTitle  = 'Cobros del día';
            $breadcrumb = 'Administrador · Todos los préstamos activos · ' . date('d/m/Y');
        } else {
            $cobrador = $this->employeeModel->findByUserId($_SESSION['id']);
            if (!$cobrador) {
                header('Location: ' . APP_URL . '/login?error=empleado'); exit();
            }
            // Cobrador: ve TODOS sus préstamos asignados (hoy, atrasados y futuros).
            // La vista los separa en dos secciones. Al registrar un pago y recargar,
            // el préstamo pasa de "hoy" a "próximos" porque su proximo_pago avanza.
            $prestamos  = $this->paymentModel->getPendingByCollector($cobrador['id'], false);
            $pageTitle  = 'Mis cobros';
            $breadcrumb = 'Panel de cobrador · ' . date('d/m/Y');
        }

        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/collector/cobros.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function asignar(): void {
        $puesto    = $_SESSION['puesto'] ?? '';
        $loanModel = new Loan();
        $cobradores = $this->employeeModel->getByType('collector');

        // ── Filtros desde GET ──────────────────────────────────────────────
        $filtroDesde       = $_GET['desde']        ?? '';
        $filtroHasta       = $_GET['hasta']        ?? '';
        $filtroSinCobrador = !empty($_GET['sin_cobrador']);
        $filtroBusqueda    = trim($_GET['busqueda'] ?? '');

        if ($puesto === 'promo') {
            $emp       = $this->employeeModel->findByUserId($_SESSION['id']);
            $prestamos = $emp
                ? $loanModel->getActiveForAssignment($emp['id'], $filtroDesde, $filtroHasta, $filtroSinCobrador, $filtroBusqueda)
                : [];
            $breadcrumb = 'Promotor · Asignar cobradores';
        } else {
            $prestamos  = $loanModel->getActiveForAssignment(0, $filtroDesde, $filtroHasta, $filtroSinCobrador, $filtroBusqueda);
            $breadcrumb = 'Administrador · Asignar cobradores';
        }

        $pageTitle = 'Asignar cobros';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/cobros_asignar.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function guardarAsignacion(): void {
        $asignaciones = $_POST['asignacion'] ?? [];
        $loanModel    = new Loan();
        $guardados    = 0;

        foreach ($asignaciones as $prestamo_id => $cobrador_id) {
            $loanModel->assignCollector((int)$prestamo_id, (int)$cobrador_id);
            $guardados++;
        }

        $back = $_SERVER['HTTP_REFERER'] ?? (APP_URL . '/cobros/asignar');
        header('Location: ' . APP_URL . '/cobros/asignar?ok=' . $guardados);
        exit();
    }

    public function register(): void {
        header('Content-Type: application/json');
        $puesto     = $_SESSION['puesto'] ?? '';
        $usuario_id = $_SESSION['id'];
        $cobrador   = $this->employeeModel->findByUserId($usuario_id);
        // Admin no tiene empleado — usamos id=0, registerBatch lo maneja
        if (!$cobrador && $puesto !== 'admin') { echo json_encode(['error' => 'Cobrador no encontrado']); exit(); }

        $cobros = json_decode(file_get_contents('php://input'), true);
        if (!$cobros) { echo json_encode(['error' => 'Datos inválidos']); exit(); }

        try {
            $r = $this->paymentModel->registerBatch($cobros, $cobrador['id'] ?? 0);
            echo json_encode(['ok' => true, 'registrados' => $r]);
        } catch (Exception $e) {
            error_log('[PaymentController] ' . $e->getMessage());
            echo json_encode(['error' => 'Error al registrar cobros']);
        }
    }
}
