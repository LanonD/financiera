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

        // Filtros de servidor via GET
        $filtros = [
            'frecuencia' => trim($_GET['frecuencia'] ?? ''),
            'monto_min'  => (float)($_GET['monto_min'] ?? 0),
            'monto_max'  => (float)($_GET['monto_max'] ?? 0),
            'desde'      => trim($_GET['desde'] ?? ''),
            'hasta'      => trim($_GET['hasta'] ?? ''),
        ];

        if ($puesto === 'promo') {
            $empModel    = new Employee();
            $empleado    = $empModel->findByUserId($_SESSION['id']);
            $promotor_id = $empleado ? $empleado['id'] : 0;
            $prestamos   = $this->loanModel->getFiltered(
                $promotor_id,
                $filtros['frecuencia'], $filtros['monto_min'], $filtros['monto_max'],
                $filtros['desde'], $filtros['hasta']
            );
            $clientModel = new Client();
            $clientes    = $empleado ? $clientModel->getByPromotor($empleado['id']) : [];
            $pageTitle   = 'Mis préstamos';
            $breadcrumb  = 'Promotor · Cartera personal';
        } else {
            $prestamos   = $this->loanModel->getFiltered(
                0,
                $filtros['frecuencia'], $filtros['monto_min'], $filtros['monto_max'],
                $filtros['desde'], $filtros['hasta']
            );
            $clientModel = new Client();
            $clientes    = $clientModel->getAll();
            $pageTitle   = 'Préstamos';
            $breadcrumb  = 'Administración · Todos los préstamos';
        }
        $this->render('admin/prestamos', compact('prestamos','clientes','pageTitle','breadcrumb','filtros'));
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


    // GET /prestamos/nuevo
    public function newLoan(): void {
        $clientes   = $this->loadClientes();
        $pageTitle  = 'Nuevo préstamo';
        $breadcrumb = 'Préstamos · Crear nuevo';
        $this->render('admin/prestamo_nuevo', compact('clientes', 'pageTitle', 'breadcrumb'));
    }

    // Carga clientes según el rol: promo ve solo los suyos, admin ve todos
    private function loadClientes(): array {
        $puesto      = $_SESSION['puesto'] ?? '';
        $clientModel = new Client();
        if ($puesto === 'promo') {
            $empModel = new Employee();
            $emp      = $empModel->findByUserId($_SESSION['id']);
            return $emp ? $clientModel->getByPromotor($emp['id']) : [];
        }
        return $clientModel->getAll();
    }

    // GET /calculadora2
    public function calculator2(): void {
        $result = null; $errores = []; $input = [];
        $clientes  = $this->loadClientes();
        $pageTitle = 'Calculadora 2'; $breadcrumb = 'Herramientas · Pago fijo acordado';
        $this->render('admin/calculadora2', compact('result','errores','input','clientes','pageTitle','breadcrumb'));
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
        $clientes  = $this->loadClientes();
        $pageTitle = 'Calculadora 2'; $breadcrumb = 'Herramientas · Pago fijo acordado';
        $this->render('admin/calculadora2', compact('result','errores','input','clientes','pageTitle','breadcrumb'));
    }

    // POST /prestamos/crear2 — crea préstamo desde Calculadora 2 (pago fijo, sin interés diario)
    public function createLoan2(): void {
        $puesto   = $_SESSION['puesto'] ?? '';
        $empModel = new Employee();
        $emp      = $empModel->findByUserId($_SESSION['id']);
        if (!$emp && $puesto !== 'admin') { $this->redirect('/calculadora2'); }

        $cliente_id      = (int)  ($_POST['cliente_id']      ?? 0);
        $monto_entregado = (float)($_POST['monto_entregado'] ?? 0);
        $monto_retornar  = (float)($_POST['monto_retornar']  ?? 0);
        $num_pagos       = (int)  ($_POST['num_pagos']       ?? 0);
        $frecuencia      = $_POST['frecuencia']   ?? 'Mensual';
        $fecha_inicio    = $_POST['fecha_inicio'] ?? date('Y-m-d');

        if (!$cliente_id || $monto_entregado <= 0 || $monto_retornar < $monto_entregado || $num_pagos <= 0) {
            $this->redirect('/calculadora2?error=datos');
        }

        $clientModel = new Client();
        $cliente     = $clientModel->findById($cliente_id);
        if (!$cliente) { $this->redirect('/calculadora2'); }

        $promotor_id = $emp ? $emp['id'] : ($cliente['promotor_id'] ?? 0);

        $result    = $this->loanService->calcularPagoFijo($monto_entregado, $monto_retornar, $num_pagos, $frecuencia, $fecha_inicio);
        $fecha_fin = $result['tabla'][count($result['tabla']) - 1]['fecha'] ?? date('Y-m-d');

        $prestamo_id = $this->loanModel->create([
            'cliente_id'        => $cliente_id,
            'promotor_id'       => $promotor_id,
            'cobrador_id'       => null,
            'monto'             => $monto_entregado,
            'tasa_diaria'       => 0,
            'num_pagos'         => $num_pagos,
            'frecuencia'        => $frecuencia,
            'cuota'             => $result['cuota_base'],
            'saldo_actual'      => $monto_entregado,                        // principal entregado
            'interes_acumulado' => $monto_retornar - $monto_entregado,      // ganancia pre-cargada
            'fecha_inicio'      => $fecha_inicio,
            'fecha_fin'         => $fecha_fin,
            'interes_activo'    => 0,                                        // sin acumulación diaria
        ]);

        $payModel = new Payment();
        $payModel->createSchedule($prestamo_id, $result['tabla']);

        $this->redirect('/prestamos/detalle?id=' . $prestamo_id . '&ok=creado2');
    }

    // POST /prestamos/toggle-interes — pausa/reanuda interés regular
    public function toggleInterest(): void {
        $id = (int)($_POST['prestamo_id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }
        $this->loanModel->toggleInterest($id);
        $this->redirect('/prestamos/detalle?id=' . $id);
    }

    // POST /prestamos/toggle-mora — activa/desactiva interés por mora
    public function toggleMoraInterest(): void {
        $id = (int)($_POST['prestamo_id'] ?? 0);
        if (!$id) { $this->redirect('/prestamos'); }
        $this->loanModel->toggleMoraInterest($id);
        $this->redirect('/prestamos/detalle?id=' . $id);
    }

    // POST /prestamos/crear
    public function createLoan(): void {
        $puesto = $_SESSION['puesto'] ?? '';
        $empModel = new Employee();
        $emp = $empModel->findByUserId($_SESSION['id']);
        if (!$emp && $puesto !== 'admin') { $this->redirect('/prestamos'); }

        $principal    = (float)($_POST['monto']        ?? 0);
        $tasa_diaria  = (float)($_POST['tasa_diaria']  ?? 0);
        $num_pagos    = (int)  ($_POST['num_pagos']    ?? 0);
        $frecuencia   = $_POST['frecuencia']   ?? 'Mensual';
        $fecha_inicio = $_POST['fecha_inicio'] ?? date('Y-m-d');
        $cliente_id   = (int)($_POST['cliente_id'] ?? 0);

        if (!$cliente_id || $principal <= 0 || $num_pagos <= 0) {
            $this->redirect('/prestamos?error=datos');
        }

        $clientModel = new Client();
        $cliente = $clientModel->findById($cliente_id);
        if (!$cliente) { $this->redirect('/prestamos'); }
        
        $promotor_id = $emp ? $emp['id'] : $cliente['promotor_id'];

        $result = $this->loanService->calcularAmortizacion($principal, $tasa_diaria, $num_pagos, $frecuencia, $fecha_inicio);
        $fecha_fin = $result['tabla'][count($result['tabla'])-1]['fecha'] ?? date('Y-m-d');

        $prestamo_id = $this->loanModel->create([
            'cliente_id'  => $cliente_id,
            'promotor_id' => $promotor_id,
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
            'cobrador_id'       => (int)  ($_POST['cobrador_id']       ?? 0),
            'promotor_id'       => (int)  ($_POST['promotor_id']       ?? 0),
            'estatus'           =>         $_POST['estatus']            ?? 'Activo',
            'saldo_actual'      => (float)($_POST['saldo_actual']       ?? 0),
            'interes_acumulado' => (float)($_POST['interes_acumulado']  ?? 0),
            'interes_diario'    => (float)($_POST['interes_diario']     ?? 0),
        ];
        
        // Automatización: si saldo e interés caen a 0 (y no estaba cancelado/retirado), se finaliza.
        if ($data['saldo_actual'] <= 0 && $data['interes_acumulado'] <= 0 && in_array($data['estatus'], ['Activo', 'Atrasado', 'Pendiente'])) {
            $data['estatus'] = 'Finalizado';
        }

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
