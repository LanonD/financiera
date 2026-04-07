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
        $id       = (int)($_POST['id'] ?? 0);
        $nombre   = htmlspecialchars(trim($_POST['nombre']   ?? ''));
        $celular  = htmlspecialchars(trim($_POST['celular']  ?? ''));
        $email    = filter_var(trim($_POST['email'] ?? ''), FILTER_VALIDATE_EMAIL) ?: '';
        $puesto   = $_POST['puesto']   ?? 'promo';
        $rango    = $_POST['rango']    ?? 'Bronce';
        $capacidad = (float)($_POST['capacidad'] ?? 0);

        if (!$id || !$nombre) {
            header('Location: ' . APP_URL . '/empleados?error=datos'); exit();
        }
        $this->empModel->update($id, compact('nombre','celular','email','puesto','rango','capacidad'));
        header('Location: ' . APP_URL . '/empleados?ok=actualizado'); exit();
    }
}
