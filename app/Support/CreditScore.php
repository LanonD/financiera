<?php

namespace App\Support;

use Illuminate\Support\Facades\DB;

/**
 * Credit score del cliente (escala 500–850) a partir de su historial de pagos.
 *
 * La misma fórmula existe en dos formas que deben producir el MISMO número:
 *   - paraPrestamos(): cálculo en PHP, para pintar la columna de la tabla.
 *   - expresionSql():  expresión SQL equivalente, para filtrar en la consulta
 *     antes de paginar (no se puede filtrar en PHP sobre una página ya cortada).
 * Si cambias la fórmula, cámbiala en ambos lugares.
 */
class CreditScore
{
    /** Alias de la subconsulta agregada dentro de las consultas que filtran. */
    public const ALIAS = 'cs';

    public const MIN    = 500;
    public const MAX    = 850;
    /** Cliente sin pagos registrados: aún no tiene historial que juzgar. */
    public const NEUTRO = 700;
    /** Con algún préstamo atrasado el score no puede superar este techo. */
    public const TOPE_ATRASADO = 650;

    private const BASE         = 520;  // piso del tramo que otorga el % de cumplimiento
    private const AMPLITUD     = 320;  // puntos que reparte el % de cuotas pagadas
    private const PENALIZACION = 8;    // puntos que descuenta cada cuota atrasada

    /**
     * Rangos ofrecidos por el filtro. Los cortes coinciden con los colores de
     * la columna Credit Score (verde / ámbar / rojo).
     */
    public const RANGOS = [
        'excelente' => ['label' => 'Excelente (750 – 850)', 'min' => 750, 'max' => self::MAX],
        'bueno'     => ['label' => 'Bueno (650 – 749)',     'min' => 650, 'max' => 749],
        'riesgo'    => ['label' => 'Riesgo (500 – 649)',    'min' => self::MIN, 'max' => 649],
    ];

    /**
     * Score de un cliente a partir de su colección de préstamos, que debe traer
     * los pagos cargados (with('prestamos.pagos')).
     */
    public static function paraPrestamos($prestamos): int
    {
        $pagos = $prestamos->flatMap(fn ($p) => $p->pagos);
        $total = $pagos->count();

        if ($total === 0) {
            $score = self::NEUTRO;
        } else {
            $ratio = $pagos->where('estatus', 'Pagado')->count() / $total;
            $score = (int) (self::BASE + $ratio * self::AMPLITUD)
                   - $pagos->where('estatus', 'Atrasado')->count() * self::PENALIZACION;
            $score = max(self::MIN, min(self::MAX, $score));
        }

        if ($prestamos->where('estatus', 'Atrasado')->isNotEmpty()) {
            $score = min($score, self::TOPE_ATRASADO);
        }

        return $score;
    }

    /**
     * Insumos del score agregados por cliente: una fila por cliente, de modo
     * que unirla con leftJoinSub no duplica registros. El LEFT JOIN a pagos
     * conserva los préstamos sin cuotas generadas.
     */
    public static function subconsulta()
    {
        return DB::table('prestamos as p')
            ->leftJoin('pagos as g', 'g.prestamo_id', '=', 'p.id')
            ->selectRaw("p.cliente_id,
                COUNT(g.id)                 as total_pagos,
                SUM(g.estatus = 'Pagado')   as pagos_ok,
                SUM(g.estatus = 'Atrasado') as pagos_mal,
                MAX(p.estatus = 'Atrasado') as tiene_atrasado")
            ->groupBy('p.cliente_id');
    }

    /**
     * Traducción a SQL de paraPrestamos(), sobre las columnas de subconsulta().
     * Los COALESCE cubren al cliente sin préstamos, cuya fila del LEFT JOIN
     * viene en NULL.
     */
    public static function expresionSql(): string
    {
        $a = self::ALIAS;

        return "LEAST(
            CASE WHEN COALESCE({$a}.tiene_atrasado, 0) = 1 THEN " . self::TOPE_ATRASADO . " ELSE " . self::MAX . " END,
            CASE WHEN COALESCE({$a}.total_pagos, 0) = 0 THEN " . self::NEUTRO . "
                 ELSE GREATEST(" . self::MIN . ", LEAST(" . self::MAX . ",
                     FLOOR(" . self::BASE . " + (COALESCE({$a}.pagos_ok, 0) / {$a}.total_pagos) * " . self::AMPLITUD . ")
                     - COALESCE({$a}.pagos_mal, 0) * " . self::PENALIZACION . "))
            END
        )";
    }
}
