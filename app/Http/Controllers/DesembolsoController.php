<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Prestamo;
use App\Models\Empleado;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;

class DesembolsoController extends Controller
{
    public function index()
    {
        $user     = Auth::user();
        $empleado = $user->empleado;

        // Auto-retire pending loans older than 5 days with no disbursement
        Prestamo::where('estatus', 'Pendiente')
            ->whereNull('fecha_entrega')
            ->where('created_at', '<', now()->subDays(5))
            ->update(['estatus' => 'Retirado']);

        $query = Prestamo::with(['cliente', 'promotor'])
            ->where('estatus', 'Pendiente')
            ->whereNull('fecha_entrega');

        if ($user->puesto === 'desembolso' && $empleado) {
            $query->where('desembolso_id', $empleado->id);
        }

        $prestamos_pendientes = $query->orderBy('created_at')->get();

        return view('desembolso.desembolsos', compact('prestamos_pendientes'));
    }

    public function confirmar(Request $request)
    {
        try {
            $prestamoId = $request->input('prestamo_id');
            $monto      = (float) $request->input('monto', 0);
            $forma      = $request->input('forma', 'efectivo');
            $nota       = $request->input('nota');

            // Detect if post_max_size was exceeded (PHP empties $_POST and $_FILES)
            if (empty($_POST) && $request->server('CONTENT_LENGTH', 0) > 0) {
                $limitMb = (int) ini_get('post_max_size');
                return response()->json(['ok' => false, 'error' => "El tamaño total de los archivos supera el límite permitido ({$limitMb}MB). Comprime las imágenes e intenta de nuevo."]);
            }

            if (!$prestamoId || $monto <= 0) {
                return response()->json(['ok' => false, 'error' => 'Datos incompletos. Verifica el monto.']);
            }

            // Validate required documents with helpful error messages
            foreach ([
                'doc_ine'          => 'INE del cliente',
                'doc_pagare'       => 'Pagaré firmado',
                'doc_comprobante'  => 'Comprobante de domicilio',
            ] as $field => $label) {
                if (!$request->hasFile($field)) {
                    return response()->json(['ok' => false, 'error' => "Falta el documento: {$label}"]);
                }
                $file = $request->file($field);
                if (!$file->isValid()) {
                    $phpError = $file->getError();
                    $msg = match($phpError) {
                        UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE =>
                            "El archivo \"{$label}\" es demasiado grande. Límite: " . ini_get('upload_max_filesize'),
                        default => "Error al subir \"{$label}\" (código {$phpError}). Intenta de nuevo.",
                    };
                    return response()->json(['ok' => false, 'error' => $msg]);
                }
            }

            $prestamo = Prestamo::find($prestamoId);
            if (!$prestamo) {
                return response()->json(['ok' => false, 'error' => 'Préstamo no encontrado']);
            }
            if ($prestamo->estatus !== 'Pendiente') {
                return response()->json(['ok' => false, 'error' => 'Este préstamo ya fue procesado']);
            }

            $dir = "documentos/prestamo_{$prestamoId}";

            $pathIne         = $request->file('doc_ine')->store($dir, 'public');
            $pathPagare      = $request->file('doc_pagare')->store($dir, 'public');
            $pathComprobante = $request->file('doc_comprobante')->store($dir, 'public');
            $pathFoto        = null;

            if ($request->hasFile('doc_foto_domicilio') && $request->file('doc_foto_domicilio')->isValid()) {
                $pathFoto = $request->file('doc_foto_domicilio')->store($dir, 'public');
            }

            $empleado = Auth::user()->empleado;

            $prestamo->update([
                'estatus'            => 'Activo',
                'monto_entregado'    => $monto,
                'forma_entrega'      => $forma,
                'fecha_entrega'      => now()->toDateString(),
                'nota_entrega'       => $nota,
                'desembolso_id'      => $empleado?->id,
                'doc_ine'            => $pathIne,
                'doc_pagare'         => $pathPagare,
                'doc_comprobante'    => $pathComprobante,
                'doc_foto_domicilio' => $pathFoto,
            ]);

            return response()->json(['ok' => true]);

        } catch (\Throwable $e) {
            \Log::error('DesembolsoController::confirmar error: ' . $e->getMessage());
            return response()->json(['ok' => false, 'error' => 'Error interno al guardar. Detalle: ' . $e->getMessage()]);
        }
    }
}
