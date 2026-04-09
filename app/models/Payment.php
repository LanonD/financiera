<?php
class Payment {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    // $soloHoy = true → cobrador ve solo pagos vencidos/de hoy (desaparecen al cobrar).
    // $soloHoy = false → admin ve todos los préstamos activos sin filtro de fecha.
    public function getPendingByCollector(int $cobrador_id, bool $soloHoy = false): array {
        $loan = new Loan();
        return $loan->getByCollector($cobrador_id, $soloHoy);
    }

    // Registrar múltiples cobros en una transacción
    public function registerBatch(array $cobros, int $cobrador_id): int {
        $this->db->begin_transaction();
        $registrados = 0;

        try {
            foreach ($cobros as $prestamo_id => $cobro) {
                $prestamo_id = (int)$prestamo_id;
                $monto       = (float)$cobro['monto'];
                $tipo        = in_array($cobro['tipo'], ['completo','parcial']) ? $cobro['tipo'] : 'parcial';
                $nota        = substr($cobro['nota'] ?? '', 0, 500);

                // Verificar que el préstamo exista y esté activo/atrasado
                $stmt = $this->db->prepare("
                    SELECT saldo_actual, cuota, interes_acumulado, tasa_diaria, fecha_ultimo_interes, fecha_inicio
                    FROM prestamos WHERE id = ? AND estatus IN ('Activo','Atrasado') LIMIT 1
                ");
                $stmt->bind_param("i", $prestamo_id);
                $stmt->execute();
                $prestamo = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$prestamo) continue;

                // ── Calcular interés no acumulado aún (días desde último cron) ──
                $hoy  = date('Y-m-d');
                $base = $prestamo['fecha_ultimo_interes'] ?? $prestamo['fecha_inicio'] ?? $hoy;
                $td   = (float)$prestamo['tasa_diaria'] / 100;
                $diasExtra = ($base < $hoy)
                    ? (int)(new DateTime($hoy))->diff(new DateTime($base))->days
                    : 0;
                $interesTotal = round((float)$prestamo['interes_acumulado'] + ((float)$prestamo['saldo_actual'] * $td * $diasExtra), 2);

                // ── Distribuir pago: interés primero, luego principal ───────────
                $pagoAInteres   = min($monto, $interesTotal);
                $pagoAPrincipal = max(0, $monto - $pagoAInteres);
                $nuevo_interes  = round($interesTotal - $pagoAInteres, 2);
                $nuevo_saldo    = max(0, round((float)$prestamo['saldo_actual'] - $pagoAPrincipal, 2));
                $estatus_nuevo  = $nuevo_saldo <= 0 ? 'Finalizado' : 'Activo';

                // ── Actualizar fila de pagos si existe ──────────────────────────
                $stmt = $this->db->prepare("
                    SELECT id, monto_cuota FROM pagos
                    WHERE prestamo_id = ? AND estatus IN ('Pendiente','Atrasado')
                    ORDER BY numero_pago ASC LIMIT 1
                ");
                $stmt->bind_param("i", $prestamo_id);
                $stmt->execute();
                $pago = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if ($pago) {
                    $estatus_pago = ($monto >= (float)$pago['monto_cuota']) ? 'Pagado' : 'Parcial';
                    $stmt = $this->db->prepare("
                        UPDATE pagos SET
                            monto_cobrado = ?,
                            tipo_cobro    = ?,
                            nota_cobro    = ?,
                            fecha_pago    = NOW(),
                            estatus       = ?,
                            cobrador_id   = ?
                        WHERE id = ?
                    ");
                    $stmt->bind_param("dsssii", $monto, $tipo, $nota, $estatus_pago, $cobrador_id, $pago['id']);
                    $stmt->execute();
                    $stmt->close();
                }

                // ── Actualizar saldo, interés acumulado y fecha en el préstamo ──
                $stmt = $this->db->prepare("
                    UPDATE prestamos
                    SET saldo_actual         = ?,
                        interes_acumulado    = ?,
                        fecha_ultimo_interes = ?,
                        estatus              = ?
                    WHERE id = ?
                ");
                $stmt->bind_param("ddssi", $nuevo_saldo, $nuevo_interes, $hoy, $estatus_nuevo, $prestamo_id);
                $stmt->execute();
                $stmt->close();

                $registrados++;
            }

            $this->db->commit();
            return $registrados;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
    }

    // Eliminar pagos pendientes/atrasados para recalcular
    public function deletePending(int $prestamo_id): void {
        $stmt = $this->db->prepare(
            "DELETE FROM pagos WHERE prestamo_id = ? AND estatus IN ('Pendiente','Atrasado')"
        );
        $stmt->bind_param("i", $prestamo_id);
        $stmt->execute();
        $stmt->close();
    }

    // Insertar tabla de amortización al crear préstamo
    public function createSchedule(int $prestamo_id, array $tabla): void {
        $stmt = $this->db->prepare("
            INSERT INTO pagos (prestamo_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada)
            VALUES (?, ?, ?, ?, ?, ?, ?)
        ");
        foreach ($tabla as $fila) {
            $stmt->bind_param(
                "iidddds",
                $prestamo_id,
                $fila['pago'],
                $fila['cuota'],
                $fila['interes'],
                $fila['capital'],
                $fila['saldo'],
                $fila['fecha']
            );
            $stmt->execute();
        }
        $stmt->close();
    }
}
