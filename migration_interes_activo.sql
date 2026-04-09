-- ============================================================
--  Migración: interes_activo por préstamo
--  Ejecutar en phpMyAdmin si ya tienes la BD creada
-- ============================================================

USE financiera;

ALTER TABLE prestamos
    ADD COLUMN interes_activo TINYINT(1) NOT NULL DEFAULT 1
        COMMENT '1 = acumula interés diario, 0 = pausado manualmente'
    AFTER fecha_ultimo_interes;

-- Actualizar la vista para incluir el nuevo campo
CREATE OR REPLACE VIEW v_prestamos AS
SELECT
    p.id,
    p.estatus,
    p.monto,
    p.cuota,
    p.tasa_diaria,
    p.num_pagos,
    p.frecuencia,
    p.saldo_actual,
    p.interes_acumulado,
    p.fecha_ultimo_interes,
    p.interes_activo,
    p.fecha_inicio,
    p.fecha_fin,
    p.cobrador_id,
    p.promotor_id,
    p.monto_entregado,
    p.forma_entrega,
    p.fecha_entrega,
    p.created_at,
    c.nombre        AS cliente_nombre,
    c.celular       AS cliente_celular,
    c.direccion     AS cliente_direccion,
    c.curp          AS cliente_curp,
    c.id            AS cliente_id,
    ep.nombre       AS promotor_nombre,
    ec.nombre       AS cobrador_nombre
FROM prestamos p
JOIN clientes_f c      ON p.cliente_id   = c.id
JOIN empleados  ep     ON p.promotor_id  = ep.id
LEFT JOIN empleados ec ON p.cobrador_id  = ec.id;
