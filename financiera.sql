-- ============================================================
--  PrestaCRM — Base de datos completa
--  Motor: MySQL 5.7+ / MariaDB
--  Ejecutar en phpMyAdmin o MySQL CLI
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
    promotor_id         INT          NOT NULL,       -- empleado que registró
    nombre              VARCHAR(120) NOT NULL,
    celular             VARCHAR(20),
    fijo                VARCHAR(20),
    direccion           VARCHAR(255),
    curp                VARCHAR(18)  UNIQUE,
    ocupacion           ENUM('Empleado','Negocio propio') NOT NULL DEFAULT 'Empleado',
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
    desembolso_id   INT,                            -- quien entregó el dinero
    -- Parámetros financieros
    monto           DECIMAL(12,2) NOT NULL,
    tasa_diaria     DECIMAL(6,4)  NOT NULL,          -- ej. 1.0000 = 1%
    num_pagos       INT           NOT NULL,
    frecuencia      ENUM('Diario','Semanal','Quincenal','Mensual') NOT NULL DEFAULT 'Mensual',
    cuota           DECIMAL(12,2) NOT NULL,          -- calculado al crear
    saldo_actual    DECIMAL(12,2) NOT NULL,
    -- Fechas
    fecha_inicio    DATE,
    fecha_fin       DATE,
    -- Estado
    estatus         ENUM('Pendiente','Activo','Atrasado','Finalizado','Cancelado') NOT NULL DEFAULT 'Pendiente',
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
    numero_pago      INT           NOT NULL,          -- 1, 2, 3...
    monto_cuota      DECIMAL(12,2) NOT NULL,
    interes          DECIMAL(12,2) NOT NULL,
    capital          DECIMAL(12,2) NOT NULL,
    saldo_restante   DECIMAL(12,2) NOT NULL,
    -- Cobro real
    monto_cobrado    DECIMAL(12,2),                   -- puede ser parcial
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
--  DATOS DE PRUEBA
--  Eliminar en producción o cambiar contraseñas
-- ============================================================

-- Usuarios (contraseña de todos: Admin123!)
-- Hash generado con password_hash('Admin123!', PASSWORD_DEFAULT)
INSERT INTO usuarios_f (usuario, password, puesto) VALUES
('admin',      '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'admin'),
('promotor1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'promo'),
('cobrador1',  '$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'collector'),
('desembolso1','$2y$10$92IXUNpkjO0rOQ5byMi.Ye4oKoEa3Ro9llC/.og/at2.uheWG/igi', 'desembolso');

-- Empleados
INSERT INTO empleados (usuario_id, nombre, celular, direccion, puesto, rango, capacidad_maxima) VALUES
(2, 'Juan Reyes',    '55 1111 2222', 'Av. Reforma 120, CDMX', 'promo',      'Oro',      80000),
(3, 'Pedro Morales', '55 3333 4444', 'Narvarte 12, CDMX',     'collector',  'Diamante', 200000),
(4, 'Carlos Vega',   '55 5555 6666', 'Polanco 77, CDMX',      'desembolso', 'Plata',    0);

-- Clientes
INSERT INTO clientes_f (promotor_id, nombre, celular, direccion, curp, ocupacion) VALUES
(1, 'Laura Méndez',    '55 1234 5678', 'Av. Reforma 120, CDMX', 'MELJ900101MDFRN01', 'Empleado'),
(1, 'Carlos Rivas',    '55 8765 4321', 'Insurgentes 45, CDMX',  'RICJ850601HDFSVL02', 'Negocio propio'),
(1, 'Ana Torres',      '55 9900 1122', 'Tlalpan 88, CDMX',      'TOAA920315MDFRRN03', 'Empleado'),
(1, 'Jorge López',     '55 5566 7788', 'Narvarte 12, CDMX',     'LOJJ880420HDFPRG04', 'Negocio propio'),
(1, 'Sofía Ramírez',   '55 3344 5500', 'Polanco 77, CDMX',      'RASF950710MDFMFS05', 'Empleado');

-- Préstamos
INSERT INTO prestamos (cliente_id, promotor_id, cobrador_id, monto, tasa_diaria, num_pagos, frecuencia, cuota, saldo_actual, fecha_inicio, fecha_fin, estatus) VALUES
(1, 1, 2, 45000, 1.0000, 24, 'Mensual',   2100, 38200, '2024-02-01', '2026-02-01', 'Activo'),
(2, 1, 2, 80000, 1.0000, 36, 'Quincenal', 2800, 71500, '2024-01-15', '2027-01-15', 'Activo'),
(3, 1, 2, 22000, 1.0000, 12, 'Mensual',   2050,  5100, '2023-12-01', '2024-12-01', 'Atrasado'),
(4, 1, 2, 120000,1.0000, 48, 'Mensual',   3100,115200, '2024-03-01', '2028-03-01', 'Pendiente'),
(5, 1, 2, 35000, 1.0000, 18, 'Mensual',   2200, 21000, '2024-01-01', '2025-07-01', 'Activo');

-- Pagos del préstamo 1 (Laura Méndez)
INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(1, 2, 1, 2100, 450, 1650, 43350, '2024-03-01', '2024-03-01 10:30:00', 2100, 'completo', 'Pagado'),
(1, 2, 2, 2100, 433, 1667, 41683, '2024-04-01', '2024-04-01 11:00:00', 2100, 'completo', 'Pagado'),
(1, 2, 3, 2100, 415, 1685, 39998, '2024-05-01', '2024-05-01 09:45:00', 2100, 'completo', 'Pagado'),
(1, 2, 4, 2100, 398, 1702, 38200, '2024-06-01', NULL, NULL, NULL, 'Pendiente'),
(1, 2, 5, 2100, 380, 1720, 36480, '2024-07-01', NULL, NULL, NULL, 'Pendiente');

-- ============================================================
--  VISTAS ÚTILES (opcional pero recomendado)
-- ============================================================

-- Vista: préstamos con nombre del cliente
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
    p.fecha_inicio,
    p.fecha_fin,
    c.nombre        AS cliente_nombre,
    c.celular       AS cliente_celular,
    c.direccion     AS cliente_direccion,
    c.curp          AS cliente_curp,
    c.id            AS cliente_id,
    ep.nombre       AS promotor_nombre,
    ep.id           AS promotor_id,
    ec.nombre       AS cobrador_nombre,
    ec.id           AS cobrador_id
FROM prestamos p
JOIN clientes_f c  ON p.cliente_id   = c.id
JOIN empleados ep  ON p.promotor_id  = ep.id
LEFT JOIN empleados ec ON p.cobrador_id = ec.id;

-- Vista: resumen de empleados con conteo de clientes y monto ocupado
CREATE OR REPLACE VIEW v_empleados AS
SELECT
    e.id,
    e.nombre,
    e.celular,
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
GROUP BY e.id, e.nombre, e.celular, e.direccion, e.puesto, e.rango, e.capacidad_maxima, u.usuario;
