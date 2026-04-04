<?php
class ClientController {
    public function index(): void {
        $pageTitle  = 'ClientController';
        $breadcrumb = 'En construcción';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        echo '<div class="content-header"><div><h2>ClientController</h2><p>Vista en construcción.</p></div></div>';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }
    public function create(): void { header('Location: ' . APP_URL . '/'); exit(); }
    public function confirm(): void { header('Content-Type: application/json'); echo json_encode(['ok'=>true]); }
    public function apiIndex(): void { header('Content-Type: application/json'); echo json_encode([]); }
}
