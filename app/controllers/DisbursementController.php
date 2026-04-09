<?php
require_once ROOT_PATH . '/app/models/Loan.php';
require_once ROOT_PATH . '/app/models/Employee.php';

class DisbursementController {

    private Loan     $loanModel;
    private Employee $empModel;

    public function __construct() {
        $this->loanModel = new Loan();
        $this->empModel  = new Employee();
    }

    public function index(): void {
        $puesto   = $_SESSION['puesto'] ?? '';
        $empleado = $this->empModel->findByUserId($_SESSION['id']);

        if ($puesto === 'promo' && $empleado) {
            $prestamos_pendientes = $this->loanModel->getPendingDisbursementByPromotor($empleado['id']);
            $breadcrumb = 'Promotor · Mis préstamos por entregar';
        } else {
            $prestamos_pendientes = $this->loanModel->getPendingDisbursement();
            $breadcrumb = 'Desembolso · ' . count($prestamos_pendientes) . ' pendientes';
        }

        $pageTitle = 'Desembolsos pendientes';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/desembolso/desembolsos.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function confirm(): void {
        header('Content-Type: application/json');
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data || !isset($data['prestamo_id'])) {
            echo json_encode(['error' => 'Datos inválidos']); exit();
        }
        $db   = Database::connect();
        $id   = (int)$data['prestamo_id'];
        $monto = (float)($data['monto'] ?? 0);
        $forma = $data['forma'] ?? 'efectivo';
        $nota  = substr($data['nota'] ?? '', 0, 500);
        $stmt = $db->prepare("UPDATE prestamos SET monto_entregado=?, forma_entrega=?, nota_entrega=?, fecha_entrega=NOW(), estatus='Activo' WHERE id=? AND estatus='Pendiente'");
        $stmt->bind_param("dssi", $monto, $forma, $nota, $id);
        $stmt->execute();
        $ok = $stmt->affected_rows > 0;
        $stmt->close();
        echo json_encode(['ok' => $ok, 'error' => $ok ? null : 'Préstamo no encontrado o ya entregado']);
    }
}
