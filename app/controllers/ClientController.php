<?php
require_once ROOT_PATH . '/app/models/Client.php';
require_once ROOT_PATH . '/app/models/Employee.php';

class ClientController {

    private Client   $clientModel;
    private Employee $empModel;

    public function __construct() {
        $this->clientModel = new Client();
        $this->empModel    = new Employee();
    }

    public function index(): void {
        $puesto = $_SESSION['puesto'];
        if ($puesto === 'promo') {
            $emp     = $this->empModel->findByUserId($_SESSION['id']);
            $clientes = $emp ? $this->clientModel->getByPromotor($emp['id']) : [];
        } else {
            $clientes = $this->clientModel->getAll();
        }
        $pageTitle  = $puesto === 'promo' ? 'Mis clientes' : 'Clientes';
        $breadcrumb = 'Gestión de clientes';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/clientes.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function create(): void {
        $emp = $this->empModel->findByUserId($_SESSION['id']);
        if (!$emp) { header('Location: ' . APP_URL . '/clientes'); exit(); }
        $data = [
            'nombre'    => htmlspecialchars(trim($_POST['nombre']    ?? '')),
            'celular'   => htmlspecialchars(trim($_POST['celular']   ?? '')),
            'email'     => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'fijo'      => htmlspecialchars(trim($_POST['fijo']      ?? '')),
            'direccion' => htmlspecialchars(trim($_POST['direccion'] ?? '')),
            'curp'      => strtoupper(trim($_POST['curp']            ?? '')),
            'ocupacion' => $_POST['ocupacion'] ?? 'Empleado',
            'ine'=>'','pagare'=>'','contrato'=>'','comprobante'=>'','foto_vivienda'=>'',
            'latitud'=>null,'longitud'=>null,
            'contacto_nombre'=>'','contacto_telefono'=>'','contacto_direccion'=>'',
            'contacto_nombre2'=>'','contacto_telefono2'=>'','contacto_direccion2'=>'',
        ];
        $this->clientModel->create($data, $emp['id']);
        header('Location: ' . APP_URL . '/clientes');
        exit();
    }

    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: ' . APP_URL . '/clientes'); exit(); }

        $cliente = $this->clientModel->findById($id);
        if (!$cliente) { header('Location: ' . APP_URL . '/clientes'); exit(); }

        // Promo can only see their own clients
        if ($_SESSION['puesto'] === 'promo') {
            $emp = $this->empModel->findByUserId($_SESSION['id']);
            if (!$emp || $cliente['promotor_id'] != $emp['id']) {
                header('Location: ' . APP_URL . '/clientes'); exit();
            }
        }

        $prestamos  = $this->clientModel->getLoansWithPayments($id);
        $pageTitle  = $cliente['nombre'];
        $breadcrumb = 'Clientes · Historial';

        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/cliente_detalle.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function editForm(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { header('Location: ' . APP_URL . '/clientes'); exit(); }
        $cliente   = $this->clientModel->findById($id);
        if (!$cliente) { header('Location: ' . APP_URL . '/clientes'); exit(); }
        $promotores = $this->empModel->getByType('promo');
        $pageTitle  = 'Editar cliente';
        $breadcrumb = 'Clientes · Editar';
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once ROOT_PATH . '/app/views/admin/cliente_editar.php';
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    public function update(): void {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { header('Location: ' . APP_URL . '/clientes'); exit(); }
        $data = [
            'nombre'      => htmlspecialchars(trim($_POST['nombre']    ?? '')),
            'celular'     => htmlspecialchars(trim($_POST['celular']   ?? '')),
            'email'       => filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '',
            'fijo'        => htmlspecialchars(trim($_POST['fijo']      ?? '')),
            'direccion'   => htmlspecialchars(trim($_POST['direccion'] ?? '')),
            'curp'        => strtoupper(trim($_POST['curp']            ?? '')),
            'ocupacion'   => $_POST['ocupacion']   ?? 'Empleado',
            'promotor_id' => (int)($_POST['promotor_id'] ?? 0),
        ];
        $this->clientModel->update($id, $data);
        header('Location: ' . APP_URL . '/clientes/detalle?id=' . $id . '&ok=actualizado'); exit();
    }

    public function apiIndex(): void {
        header('Content-Type: application/json');
        echo json_encode($this->clientModel->getAll());
    }
}
