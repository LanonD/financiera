<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

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

        if (!$isAdmin && in_array('desembolso', $roles) && $empleado) {
            $query->where('desembolso_id', $empleado->id);
        } elseif (!$isAdmin && in_array('promo', $roles) && $empleado) {
            $query->where('promotor_id', $empleado->id);
        }

        $prestamos_pendientes = $query->orderBy('created_at')->get();

        return view('desembolso.desembolsos', compact('prestamos_pendientes'));
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

                'doc_ine' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_pagare' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_comprobante' => 'required|file|mimes:jpg,jpeg,png,pdf|max:10240',
                'doc_foto_domicilio' => 'nullable|file|mimes:jpg,jpeg,png|max:10240',
            ]);

            $prestamoId = $request->input('prestamo_id');
            $monto      = (float) $request->input('monto');
            $forma      = $request->input('forma', 'efectivo');
            $nota       = $request->input('nota');

            $adminId  = Auth::user()->adminId();
            $prestamo = Prestamo::where('id', $prestamoId)->where('admin_id', $adminId)->first();

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

            $pathIne = $this->guardarDocumentoPublic(
                $request,
                'doc_ine',
                $carpetaFisica,
                $carpetaRelativa,
                'ine'
            );

            $pathPagare = $this->guardarDocumentoPublic(
                $request,
                'doc_pagare',
                $carpetaFisica,
                $carpetaRelativa,
                'pagare'
            );

            $pathComprobante = $this->guardarDocumentoPublic(
                $request,
                'doc_comprobante',
                $carpetaFisica,
                $carpetaRelativa,
                'comprobante'
            );

            $pathFoto = null;

            if ($request->hasFile('doc_foto_domicilio')) {
                $pathFoto = $this->guardarDocumentoPublic(
                    $request,
                    'doc_foto_domicilio',
                    $carpetaFisica,
                    $carpetaRelativa,
                    'foto_domicilio'
                );
            }

            $empleado = Auth::user()->empleado;

            $prestamo->update([
                'estatus'            => 'Activo',
                'monto_entregado'    => $monto,
                'forma_entrega'      => $forma,
                'fecha_entrega'      => now()->toDateString(),
                'nota_entrega'       => $nota,
                'desembolso_id'      => $empleado?->id,

                // Estas rutas quedan listas para usarse con asset()
                'doc_ine'            => $pathIne,
                'doc_pagare'         => $pathPagare,
                'doc_comprobante'    => $pathComprobante,
                'doc_foto_domicilio' => $pathFoto,
            ]);

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
}