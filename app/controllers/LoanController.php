<?php
require_once ROOT_PATH . '/app/models/Loan.php';
require_once ROOT_PATH . '/app/models/Client.php';
require_once ROOT_PATH . '/app/models/Payment.php';
require_once ROOT_PATH . '/app/models/Employee.php';
require_once ROOT_PATH . '/app/services/LoanService.php';

class LoanController {

    private Loan        $loanModel;
    private LoanService $loanService;

    public function __construct() {
        $this->loanModel   = new Loan();
        $this->loanService = new LoanService();
    }

    // GET /prestamos — admin ve todos, promo solo los suyos
    public function index(): void {
        $puesto = $_SESSION['puesto'];
        if ($puesto === 'promo') {
            $empModel  = new Employee();
            $empleado  = $empModel->findByUserId($_SESSION['id']);
            $prestamos = $empleado ? $this->loanModel->getByPromotor($empleado['id']) : [];
            $clientModel = new Client();
            $clientes    = $empleado ? $clientModel->getByPromotor($empleado['id']) : [];
            $pageTitle  = 'Mis préstamos';
            $breadcrumb = 'Promotor · Cartera personal';
        } else {
            $prestamos  = $this->loanModel->getAll();
            $clientes   = [];
            $pageTitle  = 'Préstamos';
            $breadcrumb = 'Administración · Todos los préstamos';
        }
        $this->render('admin/prestamos', compact('prestamos','clientes','pageTitle','breadcrumb'));
    }

    // GET /prestamos/detalle?id=X
    public function detail(): void {
        $id = (int)($_GET['id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }
        $prestamo = $this->loanModel->findById($id);
        if (!$prestamo) { $this->redirect('/prestamos'); }
        $pagos       = $this->loanModel->getPayments($id);
        $interesInfo = $this->loanModel->getInterestInfo($id);
        $empModel    = new Employee();
        $cobradores  = $empModel->getByType('collector');
        $promotores  = $empModel->getByType('promo');
        $pageTitle   = 'Detalle #' . $id;
        $breadcrumb  = 'Préstamos · Detalle';
        $this->render('admin/prestamo_detalle', compact('prestamo','pagos','interesInfo','cobradores','promotores','pageTitle','breadcrumb'));
    }

    // GET /calculadora
    public function calculator(): void {
        $result = null; $errores = []; $input = [];
        $pageTitle = 'Calculadora'; $breadcrumb = 'Herramientas · Amortización';
        $this->render('admin/calculadora', compact('result','errores','input','pageTitle','breadcrumb'));
    }

    // POST /calculadora
    public function calculate(): void {
        $input = [
            'principal'    => (float)($_POST['principal']    ?? 0),
            'tasa_diaria'  => (float)($_POST['tasa_diaria']  ?? 0),
            'num_pagos'    => (int)  ($_POST['num_pagos']    ?? 0),
            'frecuencia'   => $_POST['frecuencia']   ?? 'Mensual',
            'fecha_inicio' => $_POST['fecha_inicio'] ?? date('Y-m-d'),
        ];
        $errores = [];
        if ($input['principal']   <= 0) $errores[] = 'El monto debe ser mayor a 0.';
        if ($input['tasa_diaria'] <= 0) $errores[] = 'La tasa diaria debe ser mayor a 0.';
        if ($input['num_pagos']   <= 0) $errores[] = 'El número de pagos debe ser mayor a 0.';
        $result = empty($errores)
            ? $this->loanService->calcularAmortizacion($input['principal'],$input['tasa_diaria'],$input['num_pagos'],$input['frecuencia'],$input['fecha_inicio'])
            : null;
        $pageTitle = 'Calculadora'; $breadcrumb = 'Herramientas · Amortización';
        $this->render('admin/calculadora', compact('result','errores','input','pageTitle','breadcrumb'));
    }


    // GET /calculadora2
    public function calculator2(): void {
        $result = null; $errores = []; $input = [];
        $pageTitle = 'Calculadora 2'; $breadcrumb = 'Herramientas · Pago fijo acordado';
        $this->render('admin/calculadora2', compact('result','errores','input','pageTitle','breadcrumb'));
    }

    // POST /calculadora2
    public function calculate2(): void {
        $input = [
            'monto_entregado' => (float)($_POST['monto_entregado'] ?? 0),
            'monto_retornar'  => (float)($_POST['monto_retornar']  ?? 0),
            'num_pagos'       => (int)  ($_POST['num_pagos']       ?? 0),
            'frecuencia'      => $_POST['frecuencia']   ?? 'Mensual',
            'fecha_inicio'    => $_POST['fecha_inicio'] ?? date('Y-m-d'),
        ];
        $errores = [];
        if ($input['monto_entregado'] <= 0) $errores[] = 'El dinero entregado debe ser mayor a 0.';
        if ($input['monto_retornar']  <= 0) $errores[] = 'El total a retornar debe ser mayor a 0.';
        if ($input['monto_retornar'] < $input['monto_entregado']) $errores[] = 'El total a retornar debe ser mayor o igual al dinero entregado.';
        if ($input['num_pagos']      <= 0) $errores[] = 'El número de pagos debe ser mayor a 0.';
        $result = empty($errores)
            ? $this->loanService->calcularPagoFijo(
                $input['monto_entregado'], $input['monto_retornar'],
                $input['num_pagos'], $input['frecuencia'], $input['fecha_inicio']
              )
            : null;
        $pageTitle = 'Calculadora 2'; $breadcrumb = 'Herramientas · Pago fijo acordado';
        $this->render('admin/calculadora2', compact('result','errores','input','pageTitle','breadcrumb'));
    }

    // POST /prestamos/toggle-interes
    public function toggleInterest(): void {
        $id = (int)($_POST['prestamo_id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }
        $this->loanModel->toggleInterest($id);
        $this->redirect('/prestamos/detalle?id=' . $id);
    }

    // POST /prestamos/crear
    public function createLoan(): void {
        $empModel = new Employee();
        $emp = $empModel->findByUserId($_SESSION['id']);
        if (!$emp) { $this->redirect('/prestamos'); }

        $principal    = (float)($_POST['monto']        ?? 0);
        $tasa_diaria  = (float)($_POST['tasa_diaria']  ?? 0);
        $num_pagos    = (int)  ($_POST['num_pagos']    ?? 0);
        $frecuencia   = $_POST['frecuencia']   ?? 'Mensual';
        $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $cliente_id   = (int)($_POST['cliente_id'] ?? 0);

        if (!$cliente_id || $principal <= 0 || $num_pagos <= 0) {
            $this->redirect('/prestamos?error=datos');
        }

        $result = $this->loanService->calcularAmortizacion($principal, $tasa_diaria, $num_pagos, $frecuencia, $fecha_inicio);
        $fecha_fin = $result['tabla'][count($result['tabla'])-1]['fecha'] ?? date('Y-m-d');

        $prestamo_id = $this->loanModel->create([
            'cliente_id'  => $cliente_id,
            'promotor_id' => $emp['id'],
            'cobrador_id' => null,
            'monto'       => $principal,
            'tasa_diaria' => $tasa_diaria,
            'num_pagos'   => $num_pagos,
            'frecuencia'  => $frecuencia,
            'cuota'       => $result['cuota'],
            'saldo_actual'=> $principal,
            'fecha_inicio'=> $fecha_inicio,
            'fecha_fin'   => $fecha_fin,
        ]);

        // Crear tabla de pagos
        $payModel = new Payment();
        $payModel->createSchedule($prestamo_id, $result['tabla']);

        $this->redirect('/prestamos');
    }

    // POST /prestamos/editar — recalculate pending payments with new rate / start date
    public function edit(): void {
        $id                = (int)($_POST['prestamo_id']    ?? 0);
        $tasa_diaria       = (float)($_POST['tasa_diaria']  ?? 0);
        $fecha_primer_pago = trim($_POST['fecha_primer_pago'] ?? '');

        if (!$id || $tasa_diaria <= 0 || !$fecha_primer_pago) {
            $this->redirect('/prestamos/detalle?id=' . $id . '&error=datos');
        }

        $prestamo = $this->loanModel->findById($id);
        if (!$prestamo) { $this->redirect('/prestamos'); }

        // Keep paid/partial payments, only rebuild pending ones
        $todosLosPagos = $this->loanModel->getPayments($id);
        $pagados       = array_filter($todosLosPagos, fn($p) => in_array($p['estatus'], ['Pagado', 'Parcial']));
        $restantes     = (int)$prestamo['num_pagos'] - count($pagados);

        if ($restantes <= 0) {
            $this->redirect('/prestamos/detalle?id=' . $id . '&error=finalizado');
        }

        // True base date for daily interest:
        //   - If payments have been made: last paid payment date (saldo was set on that day)
        //   - If no payments made yet: TODAY (saldo_actual is the balance as of today)
        $fechaBase = date('Y-m-d');
        foreach (array_reverse($todosLosPagos) as $pg) {
            if (in_array($pg['estatus'], ['Pagado', 'Parcial'])) {
                $fechaBase = $pg['fecha_programada'];
                break;
            }
        }

        $result = $this->loanService->calcularAmortizacion(
            (float)$prestamo['saldo_actual'],
            $tasa_diaria,
            $restantes,
            $prestamo['frecuencia'],
            $fechaBase,         // base: last paid date → real days to first payment
            $fecha_primer_pago  // actual first payment date (may be irregular)
        );

        // Renumber payments to continue after last paid
        $nextNum = count($pagados) + 1;
        foreach ($result['tabla'] as &$fila) {
            $fila['pago'] = $nextNum++;
        }
        unset($fila);

        $fecha_fin = $result['tabla'][count($result['tabla']) - 1]['fecha'];

        $this->loanModel->updateTerms($id, $tasa_diaria, $result['cuota'], $fecha_fin);

        $payModel = new Payment();
        $payModel->deletePending($id);
        $payModel->createSchedule($id, $result['tabla']);

        $this->redirect('/prestamos/detalle?id=' . $id . '&ok=actualizado');
    }

    public function updateMeta(): void {
        $id = (int)($_POST['prestamo_id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }
        $data = [
            'cobrador_id'       => (int)($_POST['cobrador_id']       ?? 0),
            'promotor_id'       => (int)($_POST['promotor_id']       ?? 0),
            'estatus'           => $_POST['estatus'] ?? 'Activo',
            'saldo_actual'      => (float)($_POST['saldo_actual']      ?? 0),
            'interes_acumulado' => (float)($_POST['interes_acumulado'] ?? 0),
        ];
        $this->loanModel->updateMeta($id, $data);
        $this->redirect('/prestamos/detalle?id=' . $id . '&ok=meta');
    }

    // GET /api/loans
    public function apiIndex(): void {
        header('Content-Type: application/json');
        echo json_encode($this->loanModel->getAll());
    }

    private function render(string $view, array $data = []): void {
        $file = ROOT_PATH . '/app/views/' . $view . '.php';
        if (!file_exists($file)) {
            error_log("[LoanController] Vista no encontrada: $file");
            http_response_code(500);
            echo "<div style='font-family:monospace;padding:20px;background:#fee2e2;border-radius:8px;margin:20px'><strong>Vista no encontrada:</strong> app/views/{$view}.php</div>";
            return;
        }
        extract($data);
        require_once ROOT_PATH . '/app/views/layouts/header.php';
        require_once $file;
        require_once ROOT_PATH . '/app/views/layouts/footer.php';
    }

    private function redirect(string $path): void {
        header('Location: ' . APP_URL . $path); exit();
    }
}
