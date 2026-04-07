<?php
require_once ROOT_PATH . '/app/models/Client.php';
require_once ROOT_PATH . '/app/models/Loan.php';

class SearchController {

    public function index(): void {
        $clientes = [];
        $prestamos = [];
        $q = htmlspecialchars(trim($_GET['q'] ?? ''));

        if ($q) {
            $clientModel = new Client();
            $clientes    = $clientModel->search(['nombre' => $q, 'celular' => $q]);
            $loanModel   = new Loan();
            $prestamos   = $loanModel->getAll();
            $prestamos   = array_filter($prestamos, fn($p) => stripos($p['cliente_nombre'],$q) !== false);
        }

        $pageTitle  = 'Búsqueda avanzada';
        $breadcrumb = 'Buscar clientes y préstamos';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/busqueda.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }
}
