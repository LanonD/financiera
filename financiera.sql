-- ============================================================
--  PrestaCRM — Esquema completo de base de datos
--  Motor: MySQL 5.7+ / MariaDB
--  Ejecutar en phpMyAdmin o MySQL CLI para instalación limpia
--
--  Historial de cambios:
--  v1.0  — Esquema inicial (usuarios, empleados, clientes, préstamos, pagos)
--  v2.0  — email en empleados y clientes
--          interes_acumulado + fecha_ultimo_interes en prestamos
--          estatus 'Retirado' en prestamos
--          ocupacion 'Independiente','Otro' en clientes_f
--          Vista v_prestamos ampliada con campos de interés
-- ============================================================

CREATE DATABASE IF NOT EXISTS financiera
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_spanish_ci;

USE financiera;

-- ============================================================
--  TABLA: usuarios_f
--  Login de todos los roles del sistema
-- ============================================================
CREATE TABLE IF NOT EXISTS usuarios_f (
    id            INT AUTO_INCREMENT PRIMARY KEY,
    usuario       VARCHAR(60)  NOT NULL UNIQUE,
    password      VARCHAR(255) NOT NULL,           -- bcrypt hash
    puesto        ENUM('admin','promo','collector','desembolso') NOT NULL,
    activo        TINYINT(1)   NOT NULL DEFAULT 1,
    created_at    TIMESTAMP    NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: empleados
--  Perfil extendido de promotores, cobradores y desembolso
-- ============================================================
CREATE TABLE IF NOT EXISTS empleados (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    usuario_id          INT          NOT NULL,
    nombre              VARCHAR(120) NOT NULL,
    celular             VARCHAR(20),
    email               VARCHAR(150),              -- [v2.0] correo de contacto
    fijo                VARCHAR(20),
    direccion           VARCHAR(255),
    puesto              ENUM('promo','collector','desembolso') NOT NULL,
    rango               ENUM('Bronce','Plata','Oro','Platino','Diamante') NOT NULL DEFAULT 'Bronce',
    capacidad_maxima    DECIMAL(12,2) NOT NULL DEFAULT 0,
    monto_ocupado       DECIMAL(12,2) NOT NULL DEFAULT 0,
    -- Documentos
    ine                 VARCHAR(255),
    pagare              VARCHAR(255),
    contrato            VARCHAR(255),
    comprobante         VARCHAR(255),
    -- Ubicación
    latitud             DECIMAL(10,7),
    longitud            DECIMAL(10,7),
    -- Contactos de emergencia
    contacto_nombre     VARCHAR(120),
    contacto_telefono   VARCHAR(20),
    contacto_direccion  VARCHAR(255),
    contacto_nombre2    VARCHAR(120),
    contacto_telefono2  VARCHAR(20),
    contacto_direccion2 VARCHAR(255),
    -- Control
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (usuario_id) REFERENCES usuarios_f(id) ON DELETE CASCADE
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: clientes_f
--  Personas que reciben préstamos
-- ============================================================
CREATE TABLE IF NOT EXISTS clientes_f (
    id                  INT AUTO_INCREMENT PRIMARY KEY,
    promotor_id         INT          NOT NULL,     -- empleado que registró
    nombre              VARCHAR(120) NOT NULL,
    celular             VARCHAR(20),
    email               VARCHAR(150),              -- [v2.0] correo de contacto
    fijo                VARCHAR(20),
    direccion           VARCHAR(255),
    curp                VARCHAR(18)  UNIQUE,
    ocupacion           ENUM('Empleado','Negocio propio','Independiente','Otro') NOT NULL DEFAULT 'Empleado',
    -- Documentos
    ine                 VARCHAR(255),
    pagare              VARCHAR(255),
    contrato            VARCHAR(255),
    comprobante         VARCHAR(255),
    foto_vivienda       VARCHAR(255),
    -- Ubicación
    latitud             DECIMAL(10,7),
    longitud            DECIMAL(10,7),
    -- Contactos de emergencia
    contacto_nombre     VARCHAR(120),
    contacto_telefono   VARCHAR(20),
    contacto_direccion  VARCHAR(255),
    contacto_nombre2    VARCHAR(120),
    contacto_telefono2  VARCHAR(20),
    contacto_direccion2 VARCHAR(255),
    -- Control
    activo              TINYINT(1) NOT NULL DEFAULT 1,
    created_at          TIMESTAMP  NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (promotor_id) REFERENCES empleados(id)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: prestamos
--  Un préstamo por cliente (puede tener histórico)
-- ============================================================
CREATE TABLE IF NOT EXISTS prestamos (
    id              INT AUTO_INCREMENT PRIMARY KEY,
    cliente_id      INT           NOT NULL,
    promotor_id     INT           NOT NULL,
    cobrador_id     INT,
    desembolso_id   INT,                           -- quien entregó el dinero
    -- Parámetros financieros
    monto           DECIMAL(12,2) NOT NULL,
    tasa_diaria     DECIMAL(6,4)  NOT NULL,        -- ej. 1.0000 = 1%
    num_pagos       INT           NOT NULL,
    frecuencia      ENUM('Diario','Semanal','Quincenal','Mensual') NOT NULL DEFAULT 'Mensual',
    cuota           DECIMAL(12,2) NOT NULL,        -- calculado al crear
    saldo_actual    DECIMAL(12,2) NOT NULL,        -- capital pendiente
    -- Interés diario acumulable [v2.0]
    interes_acumulado    DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Interés generado día a día que aún no ha sido pagado',
    fecha_ultimo_interes DATE NULL DEFAULT NULL
        COMMENT 'Última fecha en que el cron ejecutó el cálculo de interés',
    interes_activo       TINYINT(1)    NOT NULL DEFAULT 1
        COMMENT '1 = acumula interés regular (tasa_diaria %), 0 = pausado manualmente',
    interes_diario       DECIMAL(12,2) NOT NULL DEFAULT 0.00
        COMMENT 'Cargo fijo en $ que se suma cada día cuando interes_mora_activo = 1',
    interes_mora_activo  TINYINT(1)    NOT NULL DEFAULT 0
        COMMENT '1 = mora activa, el cron añade interes_diario al interes_acumulado',
    -- Fechas
    fecha_inicio    DATE,
    fecha_fin       DATE,
    -- Estado
    estatus         ENUM('Pendiente','Activo','Atrasado','Finalizado','Cancelado','Retirado') NOT NULL DEFAULT 'Pendiente',
    -- 'Retirado' [v2.0]: préstamo Pendiente no desembolsado en 7 días → expira por cron
    -- Desembolso
    monto_entregado DECIMAL(12,2),
    forma_entrega   ENUM('efectivo','transferencia') DEFAULT 'efectivo',
    fecha_entrega   DATETIME,
    nota_entrega    TEXT,
    -- Control
    created_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at      TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (cliente_id)    REFERENCES clientes_f(id),
    FOREIGN KEY (promotor_id)   REFERENCES empleados(id),
    FOREIGN KEY (cobrador_id)   REFERENCES empleados(id),
    FOREIGN KEY (desembolso_id) REFERENCES empleados(id)
) ENGINE=InnoDB;

-- ============================================================
--  TABLA: pagos
--  Cada cuota del plan de amortización
-- ============================================================
CREATE TABLE IF NOT EXISTS pagos (
    id               INT AUTO_INCREMENT PRIMARY KEY,
    prestamo_id      INT           NOT NULL,
    cobrador_id      INT,
    numero_pago      INT           NOT NULL,       -- 1, 2, 3…
    monto_cuota      DECIMAL(12,2) NOT NULL,
    interes          DECIMAL(12,2) NOT NULL,
    capital          DECIMAL(12,2) NOT NULL,
    saldo_restante   DECIMAL(12,2) NOT NULL,       -- saldo del préstamo DESPUÉS de este pago
    -- Cobro real
    monto_cobrado    DECIMAL(12,2),               -- puede ser parcial
    tipo_cobro       ENUM('completo','parcial'),
    nota_cobro       TEXT,
    -- Fechas
    fecha_programada DATE          NOT NULL,
    fecha_pago       DATETIME,
    -- Estado
    estatus          ENUM('Pendiente','Pagado','Parcial','Atrasado') NOT NULL DEFAULT 'Pendiente',
    created_at       TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (prestamo_id) REFERENCES prestamos(id) ON DELETE CASCADE,
    FOREIGN KEY (cobrador_id) REFERENCES empleados(id)
) ENGINE=InnoDB;

-- ============================================================
--  VISTAS
-- ============================================================

-- Vista: préstamos con datos de cliente y empleados
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
    p.interes_diario,
    p.interes_mora_activo,
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

-- Vista: resumen de empleados con conteo de clientes y monto ocupado
CREATE OR REPLACE VIEW v_empleados AS
SELECT
    e.id,
    e.nombre,
    e.celular,
    e.email,
    e.direccion,
    e.puesto,
    e.rango,
    e.capacidad_maxima,
    u.usuario,
    COUNT(DISTINCT CASE WHEN e.puesto = 'promo' THEN p.id END) AS clientes_activos,
    COALESCE(SUM(CASE WHEN e.puesto = 'promo' THEN p.saldo_actual END), 0) AS monto_ocupado
FROM empleados e
JOIN usuarios_f u ON e.usuario_id = u.id
LEFT JOIN prestamos p ON (p.promotor_id = e.id OR p.cobrador_id = e.id)
    AND p.estatus IN ('Activo','Atrasado')
GROUP BY e.id, e.nombre, e.celular, e.email, e.direccion, e.puesto, e.rango, e.capacidad_maxima, u.usuario;
