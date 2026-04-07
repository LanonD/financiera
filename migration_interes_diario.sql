-- ══════════════════════════════════════════════════════════════════
--  Migración: Interés diario acumulable
--  Ejecutar una sola vez en phpMyAdmin → base de datos: financiera
-- ══════════════════════════════════════════════════════════════════

ALTER TABLE prestamos
  ADD COLUMN interes_acumulado   DECIMAL(12,2) NOT NULL DEFAULT 0.00
      COMMENT 'Interés generado día a día que aún no ha sido pagado',
  ADD COLUMN fecha_ultimo_interes DATE NULL DEFAULT NULL
      COMMENT 'Última fecha en que se ejecutó el cálculo de interés diario';

-- Inicializar fecha_ultimo_interes en los préstamos ya existentes
-- para que el cron no intente cobrar interés desde el inicio de los tiempos.
UPDATE prestamos SET fecha_ultimo_interes = CURDATE()
WHERE estatus IN ('Activo','Atrasado') AND fecha_ultimo_interes IS NULL;
