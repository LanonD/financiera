<?php
class Payment {

    private mysqli $db;

    public function __construct() {
        $this->db = Database::connect();
    }

    public function getPendingByCollector(int $cobrador_id): array {
        $loan = new Loan();
        return $loan->getByCollector($cobrador_id);
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

                // Obtener pago pendiente más antiguo
                $stmt = $this->db->prepare("
                    SELECT id, monto_cuota, capital, interes, saldo_restante
                    FROM pagos
                    WHERE prestamo_id = ? AND estatus IN ('Pendiente','Atrasado')
                    ORDER BY numero_pago ASC LIMIT 1
                ");
                $stmt->bind_param("i", $prestamo_id);
                $stmt->execute();
                $pago = $stmt->get_result()->fetch_assoc();
                $stmt->close();

                if (!$pago) continue;

                $estatus_pago = $tipo === 'completo' ? 'Pagado' : 'Parcial';

                // Actualizar pago
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

                // Actualizar saldo del préstamo si es completo
                if ($tipo === 'completo') {
                    $nuevo_saldo = max(0, $pago['saldo_restante'] - $pago['capital']);
                    $estatus_nuevo = $nuevo_saldo <= 0 ? 'Finalizado' : 'Activo';

                    $stmt = $this->db->prepare("
                        UPDATE prestamos SET saldo_actual = ?, estatus = ? WHERE id = ?
                    ");
                    $stmt->bind_param("dsi", $nuevo_saldo, $estatus_nuevo, $prestamo_id);
                    $stmt->execute();
                    $stmt->close();
                }

                $registrados++;
            }

            $this->db->commit();
            return $registrados;

        } catch (Exception $e) {
            $this->db->rollback();
            throw $e;
        }
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
