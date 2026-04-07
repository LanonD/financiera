-- Agregar email a clientes y empleados
-- Ejecutar en phpMyAdmin → base de datos: financiera

ALTER TABLE clientes_f
  ADD COLUMN email VARCHAR(150) NULL DEFAULT NULL AFTER celular;

ALTER TABLE empleados
  ADD COLUMN email VARCHAR(150) NULL DEFAULT NULL AFTER celular;
