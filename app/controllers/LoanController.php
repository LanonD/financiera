<?php
require_once ROOT_PATH . '/app/models/Loan.php';
require_once ROOT_PATH . '/app/services/LoanService.php';

class LoanController {

    private Loan        $loanModel;
    private LoanService $loanService;

    public function __construct() {
        $this->loanModel   = new Loan();
        $this->loanService = new LoanService();
    }

    // GET /prestamos
    public function index(): void {
        $prestamos = $this->loanModel->getAll();
        $this->render('admin/prestamos', compact('prestamos'));
    }

    // GET /prestamos/detalle?id=X
    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }

        $prestamo = $this->loanModel->findById($id);
        if (!$prestamo) { $this->redirect('/prestamos'); }

        $pagos    = $this->loanModel->getPayments($id);
        $this->render('admin/prestamo_detalle', compact('prestamo', 'pagos'));
    }

    // GET /calculadora
    public function calculator(): void {
        $result = null;
        $this->render('admin/calculadora', compact('result'));
    }

    // POST /calculadora
    public function calculate(): void {
        $principal   = (float)($_POST['principal']   ?? 0);
        $tasa_diaria = (float)($_POST['tasa_diaria'] ?? 0);
        $num_pagos   = (int)  ($_POST['num_pagos']   ?? 0);
        $frecuencia  = $_POST['frecuencia'] ?? 'Mensual';
        $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');

        $errores = [];
        if ($principal   <= 0) $errores[] = 'El monto debe ser mayor a 0';
        if ($tasa_diaria <= 0) $errores[] = 'La tasa diaria debe ser mayor a 0';
        if ($num_pagos   <= 0) $errores[] = 'El número de pagos debe ser mayor a 0';

        $result = empty($errores)
            ? $this->loanService->calcularAmortizacion($principal, $tasa_diaria, $num_pagos, $frecuencia, $fecha_inicio)
            : null;

        $this->render('admin/calculadora', compact('result', 'errores'));
    }

    // GET /api/loans
    public function apiIndex(): void {
        header('Content-Type: application/json');
        echo json_encode($this->loanModel->getAll());
    }

    private function render(string $view, array $data = []): void {
        extract($data);
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/' . $view . '.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    private function redirect(string $path): void {
        header('Location: ' . APP_URL . $path);
        exit();
    }
}
