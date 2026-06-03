<?php

namespace App\Http\Controllers\Mobile;

use App\Http\Controllers\Controller;
use App\Models\Cliente;
use App\Models\Empleado;
use App\Models\MobileSyncOperation;
use App\Models\Pago;
use App\Models\Prestamo;
use App\Models\PrestamoActividad;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class MobileSyncController extends Controller
{
    public function bootstrap(Request $request)
    {
        $user = Auth::user();
        $user->loadMissing('empleado');

        $prestamos = $this->visiblePrestamosQuery()
            ->with(['cliente', 'promotor', 'cobrador', 'desembolso'])
            ->orderByDesc('updated_at')
            ->get();

        $clienteIds = $prestamos->pluck('cliente_id')->filter()->unique();
        $clientes = Cliente::where('admin_id', $user->adminId())
            ->where(function ($query) use ($user, $clienteIds) {
                $query->whereIn('id', $clienteIds);

                if (in_array('admin', $user->getAllRoles())) {
                    $query->orWhere('activo', true);
                } elseif (in_array('promo', $user->getAllRoles()) && $user->empleado) {
                    $query->orWhere('promotor_id', $user->empleado->id);
                }
            })
            ->orderBy('nombre')
            ->get();

        $pagos = Pago::whereIn('prestamo_id', $prestamos->pluck('id'))
            ->orderBy('prestamo_id')
            ->orderBy('numero_pago')
            ->get();

        $empleados = Empleado::where('admin_id', $user->adminId())
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'user' => [
                'id' => $user->id,
                'usuario' => $user->usuario,
                'nombre' => $user->nombre,
                'alias' => $user->alias,
                'puesto' => $user->puesto,
                'roles' => $user->getAllRoles(),
                'admin_id' => $user->adminId(),
                'empleado' => $user->empleado,
            ],
            'data' => [
                'empleados' => $empleados,
                'clientes' => $clientes,
                'prestamos' => $prestamos,
                'pagos' => $pagos,
            ],
            'pending_operations' => MobileSyncOperation::where('user_id', $user->id)
                ->where('status', 'failed')
                ->latest()
                ->limit(50)
                ->get(),
        ]);
    }

    public function sync(Request $request)
    {
        $data = $request->validate([
            'operations' => 'required|array|max:100',
            'operations.*.client_operation_id' => 'required|string|max:120',
            'operations.*.type' => 'required|string|max:60',
            'operations.*.payload' => 'nullable|array',
        ]);

        $results = [];

        foreach ($data['operations'] as $incoming) {
            $results[] = $this->processOperation($incoming);
        }

        return response()->json([
            'ok' => true,
            'server_time' => now()->toIso8601String(),
            'results' => $results,
        ]);
    }

    private function processOperation(array $incoming): array
    {
        $user = Auth::user();
        $operation = MobileSyncOperation::firstOrCreate(
            [
                'user_id' => $user->id,
                'client_operation_id' => $incoming['client_operation_id'],
            ],
            [
                'type' => $incoming['type'],
                'payload' => $incoming['payload'] ?? [],
                'status' => 'pending',
            ]
        );

        if ($operation->status === 'processed') {
            return [
                'client_operation_id' => $operation->client_operation_id,
                'type' => $operation->type,
                'status' => $operation->status,
                'result' => $operation->result,
            ];
        }

        if ($operation->status === 'failed' && $operation->payload !== ($incoming['payload'] ?? [])) {
            $operation->forceFill([
                'type' => $incoming['type'],
                'payload' => $incoming['payload'] ?? [],
                'status' => 'pending',
                'error' => null,
                'result' => null,
                'processed_at' => null,
            ])->save();
        }

        try {
            $result = DB::transaction(function () use ($operation) {
                return match ($operation->type) {
                    'create_cliente' => $this->createCliente($operation->payload ?? []),
                    'create_prestamo' => $this->createPrestamo($operation->payload ?? []),
                    'register_payment' => $this->registerPayment($operation->payload ?? []),
                    'confirm_disbursement' => $this->confirmDisbursement($operation->payload ?? []),
                    default => throw ValidationException::withMessages([
                        'type' => 'Tipo de operacion no soportado: ' . $operation->type,
                    ]),
                };
            });

            $operation->forceFill([
                'status' => 'processed',
                'result' => $result,
                'error' => null,
                'processed_at' => now(),
            ])->save();

            return [
                'client_operation_id' => $operation->client_operation_id,
                'type' => $operation->type,
                'status' => 'processed',
                'result' => $result,
            ];
        } catch (\Throwable $exception) {
            $message = $exception instanceof ValidationException
                ? collect($exception->errors())->flatten()->implode(' ')
                : $exception->getMessage();

            $operation->forceFill([
                'status' => 'failed',
                'error' => $message,
                'processed_at' => now(),
            ])->save();

            return [
                'client_operation_id' => $operation->client_operation_id,
                'type' => $operation->type,
                'status' => 'failed',
                'error' => $message,
            ];
        }
    }

    private function createCliente(array $payload): array
    {
        $user = Auth::user();
        $roles = $user->getAllRoles();

        if (!array_intersect($roles, ['admin', 'promo'])) {
            throw ValidationException::withMessages(['cliente' => 'No tienes permiso para crear clientes.']);
        }

        validator($payload, [
            'nombre' => 'required|string|max:100',
            'celular' => 'nullable|string|max:20',
            'email' => 'nullable|email|max:100',
            'fijo' => 'nullable|string|max:20',
            'direccion' => 'nullable|string|max:255',
            'curp' => 'nullable|string|max:18',
            'ocupacion' => 'nullable|in:Empleado,Negocio propio,Independiente,Otro',
            'latitud' => 'nullable|numeric',
            'longitud' => 'nullable|numeric',
            'contacto_nombre' => 'nullable|string|max:100',
            'contacto_telefono' => 'nullable|string|max:20',
            'contacto_direccion' => 'nullable|string|max:255',
            'contacto_nombre2' => 'nullable|string|max:100',
            'contacto_telefono2' => 'nullable|string|max:20',
            'contacto_direccion2' => 'nullable|string|max:255',
            'promotor_id' => 'nullable|integer',
        ])->validate();

        $promotorId = in_array('admin', $roles)
            ? ($payload['promotor_id'] ?? $user->empleado?->id)
            : $user->empleado?->id;

        if (!$promotorId) {
            throw ValidationException::withMessages(['promotor_id' => 'No se pudo determinar el promotor.']);
        }

        $cliente = Cliente::create([
            'admin_id' => $user->adminId(),
            'promotor_id' => $promotorId,
            'nombre' => $payload['nombre'],
            'celular' => $payload['celular'] ?? null,
            'email' => $payload['email'] ?? null,
            'fijo' => $payload['fijo'] ?? null,
            'direccion' => $payload['direccion'] ?? null,
            'curp' => $payload['curp'] ?? null,
            'ocupacion' => $payload['ocupacion'] ?? null,
            'latitud' => $payload['latitud'] ?? null,
            'longitud' => $payload['longitud'] ?? null,
            'contacto_nombre' => $payload['contacto_nombre'] ?? null,
            'contacto_telefono' => $payload['contacto_telefono'] ?? null,
            'contacto_direccion' => $payload['contacto_direccion'] ?? null,
            'contacto_nombre2' => $payload['contacto_nombre2'] ?? null,
            'contacto_telefono2' => $payload['contacto_telefono2'] ?? null,
            'contacto_direccion2' => $payload['contacto_direccion2'] ?? null,
            'activo' => true,
        ]);

        return ['cliente' => $cliente->fresh()];
    }

    private function createPrestamo(array $payload): array
    {
        $user = Auth::user();
        $roles = $user->getAllRoles();

        if (!array_intersect($roles, ['admin', 'promo'])) {
            throw ValidationException::withMessages(['prestamo' => 'No tienes permiso para crear prestamos.']);
        }

        validator($payload, [
            'cliente_id' => 'nullable|integer',
            'cliente_operation_id' => 'nullable|string|max:120',
            'monto_entregado' => 'required|numeric|min:1',
            'monto_retornar' => 'required|numeric|min:1',
            'num_pagos' => 'required|integer|min:1',
            'frecuencia' => 'required|in:Diario,Semanal,Quincenal,Mensual',
            'fecha_inicio' => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            'fecha_primer_cobro' => 'required|date|after_or_equal:' . now()->subYear()->toDateString(),
            'promotor_id' => 'nullable|integer',
            'desembolsar' => 'nullable|boolean',
            'forma_entrega' => 'nullable|in:efectivo,transferencia',
            'fecha_entrega' => 'nullable|date',
            'nota_entrega' => 'nullable|string|max:1000',
        ])->validate();

        $clienteId = $this->resolveIdFromPayload($payload, 'cliente');
        $cliente = Cliente::where('id', $clienteId)->where('admin_id', $user->adminId())->firstOrFail();

        $prestamoActivo = Prestamo::where('cliente_id', $cliente->id)
            ->whereIn('estatus', ['Activo', 'Atrasado', 'Pendiente'])
            ->first();

        if ($prestamoActivo) {
            throw ValidationException::withMessages([
                'cliente_id' => 'Este cliente ya tiene un prestamo activo o pendiente.',
            ]);
        }

        $montoEntregado = round((float) $payload['monto_entregado'], 2);
        $montoRetornar = round((float) $payload['monto_retornar'], 2);
        $numPagos = (int) $payload['num_pagos'];
        $frecuencia = $payload['frecuencia'];
        $fechaPrimerCobro = $payload['fecha_primer_cobro'];
        $dias = ['Diario' => 1, 'Semanal' => 7, 'Quincenal' => 14, 'Mensual' => 30][$frecuencia];
        $cuotaBase = $numPagos > 1 ? (float) ((int) round($montoRetornar / $numPagos / 5) * 5) : $montoRetornar;
        $ultimoPago = $numPagos > 1 ? round($montoRetornar - $cuotaBase * ($numPagos - 1), 2) : $montoRetornar;
        $desembolsar = (bool) ($payload['desembolsar'] ?? false);

        $promotorId = in_array('admin', $roles)
            ? ($payload['promotor_id'] ?? $cliente->promotor_id)
            : $user->empleado?->id;

        $prestamo = Prestamo::create([
            'admin_id' => $user->adminId(),
            'cliente_id' => $cliente->id,
            'promotor_id' => $promotorId,
            'cobrador_id' => null,
            'monto' => $montoRetornar,
            'tasa_diaria' => 0,
            'num_pagos' => $numPagos,
            'frecuencia' => $frecuencia,
            'cuota' => $cuotaBase,
            'saldo_actual' => $montoRetornar,
            'interes_acumulado' => 0,
            'interes_activo' => false,
            'interes_diario' => 0,
            'interes_mora_activo' => false,
            'fecha_inicio' => $payload['fecha_inicio'],
            'fecha_fin' => Carbon::parse($fechaPrimerCobro)->addDays($dias * ($numPagos - 1))->toDateString(),
            'estatus' => $desembolsar ? 'Activo' : 'Pendiente',
            'monto_entregado' => $montoEntregado,
            'forma_entrega' => $desembolsar ? ($payload['forma_entrega'] ?? 'efectivo') : null,
            'fecha_entrega' => $desembolsar ? ($payload['fecha_entrega'] ?? now()->toDateString()) : null,
            'nota_entrega' => $payload['nota_entrega'] ?? null,
        ]);

        $interesRestante = round($montoRetornar - $montoEntregado, 2);
        $saldo = $montoEntregado;

        for ($i = 1; $i <= $numPagos; $i++) {
            $fecha = Carbon::parse($fechaPrimerCobro)->addDays($dias * ($i - 1))->toDateString();
            $cuota = ($i === $numPagos) ? $ultimoPago : $cuotaBase;
            $interes = min($cuota, round($interesRestante, 2));
            $capital = round($cuota - $interes, 2);
            $interesRestante = max(0, round($interesRestante - $interes, 2));
            $saldo = max(0, round($saldo - $capital, 2));

            Pago::create([
                'prestamo_id' => $prestamo->id,
                'numero_pago' => $i,
                'monto_cuota' => $cuota,
                'interes' => $interes,
                'capital' => $capital,
                'saldo_restante' => $saldo,
                'fecha_programada' => $fecha,
                'estatus' => 'Pendiente',
            ]);
        }

        PrestamoActividad::log($prestamo->id, 'creado',
            'Prestamo creado desde app movil offline por ' . $user->usuario . '.',
            ['offline_sync' => true, 'monto_entregado' => $montoEntregado, 'monto_retornar' => $montoRetornar]
        );

        return ['prestamo' => $prestamo->fresh(['cliente', 'pagos'])];
    }

    private function registerPayment(array $payload): array
    {
        validator($payload, [
            'prestamo_id' => 'nullable|integer',
            'prestamo_operation_id' => 'nullable|string|max:120',
            'pago_id' => 'nullable|integer',
            'monto' => 'required|numeric|min:0.01',
            'nota' => 'nullable|string|max:255',
            'fecha_pago' => 'nullable|date|after_or_equal:' . now()->subYear()->toDateString(),
            'carry_forward' => 'nullable|boolean',
        ])->validate();

        $prestamo = Prestamo::where('id', $this->resolveIdFromPayload($payload, 'prestamo'))
            ->where('admin_id', Auth::user()->adminId())
            ->firstOrFail();

        $this->assertCanUsePrestamo($prestamo, ['admin', 'promo', 'collector']);

        if (!in_array($prestamo->estatus, ['Activo', 'Atrasado'])) {
            throw ValidationException::withMessages(['prestamo' => 'El prestamo no esta activo.']);
        }

        $pagoQuery = Pago::where('prestamo_id', $prestamo->id)
            ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial']);

        if (!empty($payload['pago_id'])) {
            $pagoQuery->where('id', $payload['pago_id']);
        }

        $pago = $pagoQuery->orderBy('numero_pago')->firstOrFail();
        $fechaPago = $payload['fecha_pago'] ?? now()->toDateString();
        $montoRecibido = round((float) $payload['monto'], 2);
        $nota = $payload['nota'] ?? null;

        $this->bringMoraCurrent($prestamo, $fechaPago);

        $pagoMora = 0.0;
        if ((float) $prestamo->interes_acumulado > 0) {
            $pagoMora = min($montoRecibido, (float) $prestamo->interes_acumulado);
            $prestamo->interes_acumulado = round((float) $prestamo->interes_acumulado - $pagoMora, 2);
            $montoRecibido = round($montoRecibido - $pagoMora, 2);
            $notaMora = 'Mora: $' . number_format($pagoMora, 2);
            $nota = $nota ? $nota . ' | ' . $notaMora : $notaMora;
        }

        $tipo = 'Solo mora';
        if ($montoRecibido > 0) {
            $tipo = $montoRecibido >= (float) $pago->monto_cuota ? 'Pagado' : 'Parcial';
            $pago->monto_cobrado = $montoRecibido;
            $pago->tipo_cobro = $tipo === 'Pagado' ? 'completo' : 'parcial';
            $pago->nota_cobro = $nota;
            $pago->fecha_pago = $fechaPago;
            $pago->estatus = $tipo;
            $pago->cobrador_id = Auth::user()->empleado?->id;
            $pago->save();

            $prestamo->saldo_actual = max(0, round((float) $prestamo->saldo_actual - $montoRecibido, 2));

            if ($tipo === 'Pagado') {
                $remaining = Pago::where('prestamo_id', $prestamo->id)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado', 'Parcial'])
                    ->count();
                $prestamo->estatus = $remaining === 0 ? 'Finalizado' : 'Activo';
            }

            if (($payload['carry_forward'] ?? false) && $tipo === 'Parcial') {
                $diferencia = round((float) $pago->monto_cuota - $montoRecibido, 2);
                $nextPago = Pago::where('prestamo_id', $prestamo->id)
                    ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                    ->orderBy('numero_pago')
                    ->first();

                if ($diferencia > 0 && $nextPago) {
                    $nextPago->monto_cuota = round((float) $nextPago->monto_cuota + $diferencia, 2);
                    $nextPago->nota_cobro = trim(($nextPago->nota_cobro ? $nextPago->nota_cobro . ' | ' : '') . 'Incluye diferido de cuota #' . $pago->numero_pago);
                    $nextPago->save();
                }
            }
        } elseif ($pagoMora > 0) {
            $pago->nota_cobro = trim(($pago->nota_cobro ? $pago->nota_cobro . ' | ' : '') . $nota);
            $pago->save();
        }

        $prestamo->save();

        PrestamoActividad::log($prestamo->id, 'pago',
            'Pago sincronizado desde app movil por ' . Auth::user()->usuario . ': $' . number_format((float) $payload['monto'], 2) . '.',
            ['offline_sync' => true, 'pago_id' => $pago->id, 'monto' => (float) $payload['monto'], 'mora' => $pagoMora, 'tipo' => $tipo]
        );

        return [
            'prestamo' => $prestamo->fresh(),
            'pago' => $pago->fresh(),
        ];
    }

    private function confirmDisbursement(array $payload): array
    {
        validator($payload, [
            'prestamo_id' => 'nullable|integer',
            'prestamo_operation_id' => 'nullable|string|max:120',
            'monto' => 'required|numeric|min:1',
            'forma' => 'required|in:efectivo,transferencia',
            'nota' => 'nullable|string|max:1000',
            'fecha_entrega' => 'nullable|date|after_or_equal:' . now()->subYear()->toDateString(),
        ])->validate();

        $prestamo = Prestamo::where('id', $this->resolveIdFromPayload($payload, 'prestamo'))
            ->where('admin_id', Auth::user()->adminId())
            ->firstOrFail();

        $this->assertCanUsePrestamo($prestamo, ['admin', 'promo', 'desembolso']);

        if ($prestamo->estatus !== 'Pendiente') {
            throw ValidationException::withMessages(['prestamo' => 'Este prestamo ya fue procesado.']);
        }

        $prestamo->update([
            'estatus' => 'Activo',
            'monto_entregado' => round((float) $payload['monto'], 2),
            'forma_entrega' => $payload['forma'],
            'fecha_entrega' => $payload['fecha_entrega'] ?? now()->toDateString(),
            'nota_entrega' => $payload['nota'] ?? null,
            'desembolso_id' => Auth::user()->empleado?->id,
        ]);

        PrestamoActividad::log($prestamo->id, 'desembolso',
            'Desembolso sincronizado desde app movil por ' . Auth::user()->usuario . '.',
            ['offline_sync' => true, 'documentos_pendientes' => true, 'monto' => (float) $payload['monto']]
        );

        return ['prestamo' => $prestamo->fresh()];
    }

    private function visiblePrestamosQuery()
    {
        $user = Auth::user();
        $roles = $user->getAllRoles();
        $empleado = $user->empleado;

        $query = Prestamo::where('admin_id', $user->adminId());

        if (in_array('admin', $roles)) {
            return $query;
        }

        if (!$empleado) {
            return $query->whereRaw('1 = 0');
        }

        return $query->where(function ($q) use ($roles, $empleado) {
            if (in_array('promo', $roles)) {
                $q->orWhere('promotor_id', $empleado->id);
            }
            if (in_array('collector', $roles)) {
                $q->orWhere('cobrador_id', $empleado->id);
            }
            if (in_array('desembolso', $roles)) {
                $q->orWhere('desembolso_id', $empleado->id)
                    ->orWhere(function ($sub) {
                        $sub->where('estatus', 'Pendiente')->whereNull('desembolso_id');
                    });
            }
        });
    }

    private function assertCanUsePrestamo(Prestamo $prestamo, array $allowedRoles): void
    {
        $user = Auth::user();
        $roles = array_intersect($user->getAllRoles(), $allowedRoles);

        if (!$roles || (int) $prestamo->admin_id !== (int) $user->adminId()) {
            throw ValidationException::withMessages(['prestamo' => 'No tienes permiso para operar este prestamo.']);
        }

        if (in_array('admin', $roles)) {
            return;
        }

        $empleadoId = $user->empleado?->id;
        $canUse = (in_array('promo', $roles) && (int) $prestamo->promotor_id === (int) $empleadoId)
            || (in_array('collector', $roles) && (int) $prestamo->cobrador_id === (int) $empleadoId)
            || (in_array('desembolso', $roles) && (!$prestamo->desembolso_id || (int) $prestamo->desembolso_id === (int) $empleadoId));

        if (!$canUse) {
            throw ValidationException::withMessages(['prestamo' => 'Prestamo fuera de tu asignacion.']);
        }
    }

    private function bringMoraCurrent(Prestamo $prestamo, string $date): void
    {
        if ($prestamo->estatus === 'Activo') {
            $overdue = Pago::where('prestamo_id', $prestamo->id)
                ->whereIn('estatus', ['Pendiente', 'Atrasado'])
                ->where('fecha_programada', '<', $date)
                ->exists();

            if ($overdue) {
                $prestamo->estatus = 'Atrasado';
            }
        }

        if ((float) $prestamo->interes_diario <= 0 || (!$prestamo->interes_mora_activo && $prestamo->estatus !== 'Atrasado')) {
            return;
        }

        $desde = $prestamo->fecha_ultimo_interes
            ? $prestamo->fecha_ultimo_interes->toDateString()
            : $date;
        $dias = (int) Carbon::parse($desde)->diffInDays($date);

        if ($dias > 0) {
            $prestamo->interes_acumulado = round((float) $prestamo->interes_acumulado + ($dias * (float) $prestamo->interes_diario), 2);
            $prestamo->fecha_ultimo_interes = $date;
        }
    }

    private function resolveIdFromPayload(array $payload, string $entity): int
    {
        $directKey = $entity . '_id';
        if (!empty($payload[$directKey])) {
            return (int) $payload[$directKey];
        }

        $operationKey = $entity . '_operation_id';
        if (empty($payload[$operationKey])) {
            throw ValidationException::withMessages([$directKey => "Falta {$directKey} o {$operationKey}."]);
        }

        $operation = MobileSyncOperation::where('user_id', Auth::id())
            ->where('client_operation_id', $payload[$operationKey])
            ->where('status', 'processed')
            ->first();

        $id = $operation?->result[$entity]['id'] ?? null;
        if (!$id) {
            throw ValidationException::withMessages([$operationKey => 'No se encontro la operacion local referenciada.']);
        }

        return (int) $id;
    }
}
