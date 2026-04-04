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
        $usuario_id = $_SESSION['id'];
        $cobrador   = $this->employeeModel->findByUserId($usuario_id);

        if (!$cobrador) {
            header('Location: ' . APP_URL . '/login?error=empleado'); exit();
        }

        $prestamos  = $this->paymentModel->getPendingByCollector($cobrador['id']);
        $pageTitle  = 'Mis cobros';
        $breadcrumb = 'Panel de cobrador · ' . date('d/m/Y');

        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/collector/cobros.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function register(): void {
        header('Content-Type: application/json');
        $usuario_id = $_SESSION['id'];
        $cobrador   = $this->employeeModel->findByUserId($usuario_id);
        if (!$cobrador) { echo json_encode(['error' => 'Cobrador no encontrado']); exit(); }

        $cobros = json_decode(file_get_contents('php://input'), true);
        if (!$cobros) { echo json_encode(['error' => 'Datos inválidos']); exit(); }

        try {
            $r = $this->paymentModel->registerBatch($cobros, $cobrador['id']);
            echo json_encode(['ok' => true, 'registrados' => $r]);
        } catch (Exception $e) {
            error_log('[PaymentController] ' . $e->getMessage());
            echo json_encode(['error' => 'Error al registrar cobros']);
        }
    }
}
