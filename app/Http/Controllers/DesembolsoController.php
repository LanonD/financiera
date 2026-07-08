<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Empleado;
use App\Models\PrestamoActividad;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;

class DesembolsoController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $empleado = $user->empleado;
        $roles    = $user->getAllRoles();
        $isAdmin  = in_array('admin', $roles);
        $adminId  = $user->adminId();

        Prestamo::where('admin_id', $adminId)
            ->where('estatus', 'Pendiente')
            ->whereNull('fecha_entrega')
            ->where('created_at', '<', now()->subDays(5))
            ->update(['estatus' => 'Retirado']);

        $query = Prestamo::with(['cliente', 'promotor'])
            ->where('admin_id', $adminId)
            ->where('estatus', 'Pendiente')
            ->whereNull('fecha_entrega');

        if (!$isAdmin && $empleado) {
            $isPromo      = in_array('promo', $roles);
            $isDesembolso = in_array('desembolso', $roles);

            if ($isPromo && $isDesembolso) {
                // Tiene ambos roles: ve sus préstamos como promotor O como desembolsador
                $empId = $empleado->id;
                $query->where(function ($q) use ($empId) {
                    $q->where('promotor_id', $empId)
                      ->orWhere('desembolso_id', $empId);
                });
            } elseif ($isDesembolso) {
                $query->where('desembolso_id', $empleado->id);
            } elseif ($isPromo) {
                $query->where('promotor_id', $empleado->id);
            }
        }

        $prestamos_pendientes = $query->with('desembolso')->orderBy('created_at')->get();

        $desembolsadores = Empleado::whereJsonContains('roles', 'desembolso')
            ->where('admin_id', $adminId)
            ->where('activo', true)
            ->orderBy('nombre')
            ->get();

        return view('desembolso.desembolsos', compact('prestamos_pendientes', 'desembolsadores'));
    }

    public function asignar(Request $request, $id)
    {
        $adminId  = Auth::user()->adminId();
        $prestamo = Prestamo::where('id', $id)->where('admin_id', $adminId)->firstOrFail();

        $data = $request->validate([
            // Scoped al tenant: impide enlazar un desembolsador de OTRO admin (IDOR cross-tenant)
            'desembolso_id' => ['nullable', Rule::exists('empleados', 'id')->where('admin_id', $adminId)],
        ]);

        $anteriorId = $prestamo->desembolso_id;
        $prestamo->desembolso_id = $data['desembolso_id'] ?? null;
        $prestamo->save();

        $quien      = Auth::user()->empleado?->nombre ?? Auth::user()->usuario;
        $nuevo      = $prestamo->desembolso_id
            ? Empleado::find($prestamo->desembolso_id)?->nombre ?? 'ID '.$prestamo->desembolso_id
            : 'Sin asignar';
        $anterior   = $anteriorId
            ? Empleado::find($anteriorId)?->nombre ?? 'ID '.$anteriorId
            : 'Sin asignar';

        PrestamoActividad::log($prestamo->id, 'configuracion',
            "Desembolsador cambiado de «{$anterior}» a «{$nuevo}» por {$quien}.",
            ['anterior' => $anteriorId, 'nuevo' => $prestamo->desembolso_id]
        );

        return response()->json(['ok' => true, 'nombre' => $nuevo]);
    }

    public function confirmar(Request $request)
    {
        try {
            if (empty($_POST) && $request->server('CONTENT_LENGTH', 0) > 0) {
                $limitMb = ini_get('post_max_size');

                return response()->json([
                    'ok' => false,
                    'error' => "El tamaño total de los archivos supera el límite permitido ({$limitMb})."
                ]);
            }

            $request->validate([
                'prestamo_id' => 'required|integer',
                'monto' => 'required|numeric|min:1',
                'forma' => 'required|string',
                'nota' => 'nullable|string',

                'doc_ine' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_pagare' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_comprobante' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_foto_domicilio' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            ]);

            $prestamoId = $request->input('prestamo_id');
            $monto      = (float) $request->input('monto');
            $forma      = $request->input('forma', 'efectivo');
            $nota       = $request->input('nota');

            $adminId  = Auth::user()->adminId();
            $prestamo = Prestamo::with('cliente')->where('id', $prestamoId)->where('admin_id', $adminId)->first();

            if (!$prestamo) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Préstamo no encontrado.'
                ]);
            }

            if ($prestamo->estatus !== 'Pendiente') {
                return response()->json([
                    'ok' => false,
                    'error' => 'Este préstamo ya fue procesado.'
                ]);
            }

            $cliente = $prestamo->cliente;
            if (!$cliente) {
                return response()->json([
                    'ok' => false,
                    'error' => 'Cliente no encontrado para este prestamo.'
                ]);
            }

            if (!$cliente->ine && !$request->hasFile('doc_ine')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'El documento INE es requerido porque el cliente aun no lo tiene registrado.'
                ]);
            }

            if (!$cliente->comprobante && !$request->hasFile('doc_comprobante')) {
                return response()->json([
                    'ok' => false,
                    'error' => 'El comprobante de domicilio es requerido porque el cliente aun no lo tiene registrado.'
                ]);
            }

            /*
             | Aquí guardamos directo en:
             | public/documentos/prestamo_ID/
             |
             | Ya NO depende de storage:link.
             */
            $carpetaRelativa = 'documentos/prestamo_' . $prestamoId;
            $carpetaFisica   = public_path($carpetaRelativa);

            if (!file_exists($carpetaFisica)) {
                if (!mkdir($carpetaFisica, 0775, true)) {
                    return response()->json([
                        'ok' => false,
                        'error' => 'No se pudo crear la carpeta: ' . $carpetaFisica
                    ]);
                }
            }

            if (!is_writable($carpetaFisica)) {
                return response()->json([
                    'ok' => false,
                    'error' => 'La carpeta no tiene permisos de escritura: ' . $carpetaFisica
                ]);
            }

            $pathPagare = $this->guardarDocumentoPublic(
                $request,
                'doc_pagare',
                $carpetaFisica,
                $carpetaRelativa,
                'pagare'
            );

            $this->guardarDocumentoCliente($request, $cliente, 'doc_ine', 'ine', 'ine');
            $this->guardarDocumentoCliente($request, $cliente, 'doc_comprobante', 'comprobante', 'comprobante');
            $this->guardarDocumentoCliente($request, $cliente, 'doc_foto_domicilio', 'foto_vivienda', 'foto_domicilio');

            $empleado = Auth::user()->empleado;

            $prestamo->update([
                'estatus'            => 'Activo',
                'monto_entregado'    => $monto,
                'forma_entrega'      => $forma,
                'fecha_entrega'      => now()->toDateString(),
                'nota_entrega'       => $nota,
                'desembolso_id'      => $empleado?->id,

                'doc_pagare'         => $pathPagare,
            ]);

            $quien = Auth::user()->empleado?->nombre ?? Auth::user()->usuario;
            PrestamoActividad::log($prestamo->id, 'desembolso',
                "Desembolso confirmado por {$quien} — $" . number_format($monto, 2) . " vía {$forma}.",
                ['monto' => $monto, 'forma' => $forma, 'desembolso_id' => $empleado?->id]
            );

            return response()->json([
                'ok' => true
            ]);

        } catch (\Throwable $e) {
            Log::error('DesembolsoController::confirmar error: ' . $e->getMessage());

            return response()->json([
                'ok' => false,
                'error' => 'Error interno al guardar. Detalle: ' . $e->getMessage()
            ]);
        }
    }

    private function guardarDocumentoPublic(
        Request $request,
        string $campo,
        string $carpetaFisica,
        string $carpetaRelativa,
        string $prefijo
    ): string {
        if (!$request->hasFile($campo)) {
            throw new \Exception("No se recibió el archivo {$campo}");
        }

        $archivo = $request->file($campo);

        if (!$archivo->isValid()) {
            throw new \Exception("El archivo {$campo} no es válido.");
        }

        $extension = strtolower($archivo->getClientOriginalExtension());

        $nombreArchivo = $prefijo . '_' . time() . '_' . uniqid() . '.' . $extension;

        $archivo->move($carpetaFisica, $nombreArchivo);

        return $carpetaRelativa . '/' . $nombreArchivo;
    }

    private function guardarDocumentoCliente(Request $request, \App\Models\Cliente $cliente, string $campo, string $atributo, string $prefijo): void
    {
        if (!$request->hasFile($campo)) {
            return;
        }

        $archivo = $request->file($campo);
        if (!$archivo->isValid()) {
            return;
        }

        $carpetaRelativa = 'documentos/cliente_' . $cliente->id;
        $carpetaFisica   = public_path($carpetaRelativa);
        if (!file_exists($carpetaFisica)) {
            mkdir($carpetaFisica, 0775, true);
        }

        $extension = strtolower($archivo->getClientOriginalExtension());
        $nombreArchivo = $prefijo . '_' . time() . '_' . uniqid() . '.' . $extension;
        $archivo->move($carpetaFisica, $nombreArchivo);

        $cliente->{$atributo} = $carpetaRelativa . '/' . $nombreArchivo;
        $cliente->save();
    }
}
