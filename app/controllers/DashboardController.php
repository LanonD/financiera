<?php
require_once ROOT_PATH . '/app/models/Loan.php';

class DashboardController {

    private Loan $loanModel;

    public function __construct() {
        $this->loanModel = new Loan();
    }

    public function index(): void {
        $kpis      = $this->loanModel->getKPIs();
        $prestamos = $this->loanModel->getAll();
        $pageTitle  = 'Vista general';
        $breadcrumb = 'Préstamos · Todos los registros';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/dashboard.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }
}
