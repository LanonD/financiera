-- ============================================================
--  PrestaCRM — Pagos faltantes para préstamos 1 (completo), 2, 3, 4, 5
--  Ejecutar en phpMyAdmin DESPUÉS de haber importado financiera.sql
--  NO reimportes financiera.sql, solo corre este archivo
-- ============================================================

USE financiera;

-- ============================================================
--  PRÉSTAMO 1 — Laura Méndez (45,000 · 24 mensual · inicio 2024-02-01)
--  El seed original ya insertó pagos 1-5. Aquí van los pagos 4-24
--  (actualizamos 4 y 5 a Atrasado y añadimos 6-24)
-- ============================================================
UPDATE pagos SET estatus = 'Atrasado' WHERE prestamo_id = 1 AND numero_pago IN (4, 5);

INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(1, 2,  6, 2100, 365, 1735, 34747, '2024-08-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2,  7, 2100, 347, 1753, 32994, '2024-09-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2,  8, 2100, 330, 1770, 31224, '2024-10-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2,  9, 2100, 312, 1788, 29436, '2024-11-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 10, 2100, 294, 1806, 27630, '2024-12-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 11, 2100, 276, 1824, 25806, '2025-01-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 12, 2100, 258, 1842, 23964, '2025-02-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 13, 2100, 240, 1860, 22104, '2025-03-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 14, 2100, 221, 1879, 20225, '2025-04-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 15, 2100, 202, 1898, 18327, '2025-05-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 16, 2100, 183, 1917, 16410, '2025-06-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 17, 2100, 164, 1936, 14474, '2025-07-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 18, 2100, 145, 1955, 12519, '2025-08-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 19, 2100, 125, 1975, 10544, '2025-09-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 20, 2100, 105, 1995,  8549, '2025-10-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 21, 2100,  85, 2015,  6534, '2025-11-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 22, 2100,  65, 2035,  4499, '2025-12-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 23, 2100,  45, 2055,  2444, '2026-01-01', NULL, NULL, NULL, 'Atrasado'),
(1, 2, 24, 2468,  24, 2444,     0, '2026-02-01', NULL, NULL, NULL, 'Atrasado');

-- ============================================================
--  PRÉSTAMO 2 — Carlos Rivas (80,000 · 36 quincenal · inicio 2024-01-15)
--  4 pagos realizados, resto atrasado (todos vencieron antes de hoy)
-- ============================================================
INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(2, 2,  1, 2800, 800, 2000, 78000, '2024-01-29', '2024-01-29 09:10:00', 2800, 'completo', 'Pagado'),
(2, 2,  2, 2800, 780, 2020, 75980, '2024-02-12', '2024-02-14 10:00:00', 2800, 'completo', 'Pagado'),
(2, 2,  3, 2800, 760, 2040, 73940, '2024-02-26', '2024-02-26 09:00:00', 2800, 'completo', 'Pagado'),
(2, 2,  4, 2800, 739, 2061, 71879, '2024-03-11', '2024-03-12 11:30:00', 2800, 'completo', 'Pagado'),
(2, 2,  5, 2800, 719, 2081, 69798, '2024-03-25', NULL, NULL, NULL, 'Atrasado'),
(2, 2,  6, 2800, 698, 2102, 67696, '2024-04-08', NULL, NULL, NULL, 'Atrasado'),
(2, 2,  7, 2800, 677, 2123, 65573, '2024-04-22', NULL, NULL, NULL, 'Atrasado'),
(2, 2,  8, 2800, 656, 2144, 63429, '2024-05-06', NULL, NULL, NULL, 'Atrasado'),
(2, 2,  9, 2800, 634, 2166, 61263, '2024-05-20', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 10, 2800, 613, 2187, 59076, '2024-06-03', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 11, 2800, 591, 2209, 56867, '2024-06-17', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 12, 2800, 569, 2231, 54636, '2024-07-01', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 13, 2800, 546, 2254, 52382, '2024-07-15', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 14, 2800, 524, 2276, 50106, '2024-07-29', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 15, 2800, 501, 2299, 47807, '2024-08-12', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 16, 2800, 478, 2322, 45485, '2024-08-26', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 17, 2800, 455, 2345, 43140, '2024-09-09', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 18, 2800, 431, 2369, 40771, '2024-09-23', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 19, 2800, 408, 2392, 38379, '2024-10-07', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 20, 2800, 384, 2416, 35963, '2024-10-21', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 21, 2800, 360, 2440, 33523, '2024-11-04', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 22, 2800, 335, 2465, 31058, '2024-11-18', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 23, 2800, 311, 2489, 28569, '2024-12-02', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 24, 2800, 286, 2514, 26055, '2024-12-16', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 25, 2800, 261, 2539, 23516, '2024-12-30', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 26, 2800, 235, 2565, 20951, '2025-01-13', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 27, 2800, 210, 2590, 18361, '2025-01-27', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 28, 2800, 184, 2616, 15745, '2025-02-10', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 29, 2800, 157, 2643, 13102, '2025-02-24', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 30, 2800, 131, 2669, 10433, '2025-03-10', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 31, 2800, 104, 2696,  7737, '2025-03-24', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 32, 2800,  77, 2723,  5014, '2025-04-07', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 33, 2800,  50, 2750,  2264, '2025-04-21', NULL, NULL, NULL, 'Atrasado'),
(2, 2, 34, 2800,  23, 2264,     0, '2025-05-05', NULL, NULL, NULL, 'Atrasado');

-- ============================================================
--  PRÉSTAMO 3 — Ana Torres (22,000 · 12 mensual · inicio 2023-12-01)
--  8 pagos realizados (incluye tardíos), pagos 9-12 atrasados
-- ============================================================
INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(3, 2,  1, 2050, 220, 1830, 20170, '2024-01-01', '2024-01-01 10:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  2, 2050, 202, 1848, 18322, '2024-02-01', '2024-02-03 09:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  3, 2050, 183, 1867, 16455, '2024-03-01', '2024-03-01 11:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  4, 2050, 165, 1885, 14570, '2024-04-01', '2024-04-02 10:30:00', 2050, 'completo', 'Pagado'),
(3, 2,  5, 2050, 146, 1904, 12666, '2024-05-01', '2024-05-01 09:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  6, 2050, 127, 1923, 10743, '2024-06-01', '2024-06-06 14:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  7, 2050, 107, 1943,  8800, '2024-07-01', '2024-07-01 09:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  8, 2050,  88, 1962,  6838, '2024-08-01', '2024-08-04 10:00:00', 2050, 'completo', 'Pagado'),
(3, 2,  9, 2050,  68, 1982,  4856, '2024-09-01', NULL, NULL, NULL, 'Atrasado'),
(3, 2, 10, 2050,  49, 2001,  2855, '2024-10-01', NULL, NULL, NULL, 'Atrasado'),
(3, 2, 11, 2050,  29, 2021,   834, '2024-11-01', NULL, NULL, NULL, 'Atrasado'),
(3, 2, 12,  842,   8,  834,     0, '2024-12-01', NULL, NULL, NULL, 'Atrasado');

-- ============================================================
--  PRÉSTAMO 4 — Jorge López (120,000 · 48 mensual · Pendiente)
--  Aún no desembolsado: solo se genera el plan de pagos completo
--  Todos con estatus Pendiente (quedarán activos al desembolsar)
-- ============================================================
INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(4, NULL,  1, 3100, 1200, 1900, 118100, '2024-04-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  2, 3100, 1181, 1919, 116181, '2024-05-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  3, 3100, 1162, 1938, 114243, '2024-06-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  4, 3100, 1142, 1958, 112285, '2024-07-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  5, 3100, 1123, 1977, 110308, '2024-08-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  6, 3100, 1103, 1997, 108311, '2024-09-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  7, 3100, 1083, 2017, 106294, '2024-10-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  8, 3100, 1063, 2037, 104257, '2024-11-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL,  9, 3100, 1043, 2057, 102200, '2024-12-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 10, 3100, 1022, 2078, 100122, '2025-01-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 11, 3100, 1001,  2099, 98023, '2025-02-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 12, 3100,  980,  2120, 95903, '2025-03-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 13, 3100,  959,  2141, 93762, '2025-04-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 14, 3100,  938,  2162, 91600, '2025-05-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 15, 3100,  916,  2184, 89416, '2025-06-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 16, 3100,  894,  2206, 87210, '2025-07-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 17, 3100,  872,  2228, 84982, '2025-08-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 18, 3100,  850,  2250, 82732, '2025-09-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 19, 3100,  827,  2273, 80459, '2025-10-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 20, 3100,  805,  2295, 78164, '2025-11-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 21, 3100,  782,  2318, 75846, '2025-12-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 22, 3100,  758,  2342, 73504, '2026-01-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 23, 3100,  735,  2365, 71139, '2026-02-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 24, 3100,  711,  2389, 68750, '2026-03-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 25, 3100,  688,  2412, 66338, '2026-04-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 26, 3100,  663,  2437, 63901, '2026-05-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 27, 3100,  639,  2461, 61440, '2026-06-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 28, 3100,  614,  2486, 58954, '2026-07-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 29, 3100,  590,  2510, 56444, '2026-08-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 30, 3100,  564,  2536, 53908, '2026-09-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 31, 3100,  539,  2561, 51347, '2026-10-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 32, 3100,  513,  2587, 48760, '2026-11-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 33, 3100,  488,  2612, 46148, '2026-12-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 34, 3100,  461,  2639, 43509, '2027-01-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 35, 3100,  435,  2665, 40844, '2027-02-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 36, 3100,  408,  2692, 38152, '2027-03-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 37, 3100,  382,  2718, 35434, '2027-04-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 38, 3100,  354,  2746, 32688, '2027-05-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 39, 3100,  327,  2773, 29915, '2027-06-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 40, 3100,  299,  2801, 27114, '2027-07-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 41, 3100,  271,  2829, 24285, '2027-08-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 42, 3100,  243,  2857, 21428, '2027-09-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 43, 3100,  214,  2886, 18542, '2027-10-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 44, 3100,  185,  2915, 15627, '2027-11-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 45, 3100,  156,  2944, 12683, '2027-12-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 46, 3100,  127,  2973,  9710, '2028-01-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 47, 3100,   97,  3003,  6707, '2028-02-01', NULL, NULL, NULL, 'Pendiente'),
(4, NULL, 48, 3774,   67,  6707,     0, '2028-03-01', NULL, NULL, NULL, 'Pendiente');

-- ============================================================
--  PRÉSTAMO 5 — Sofía Ramírez (35,000 · 18 mensual · inicio 2024-01-01)
--  7 pagos realizados (con distintos días de atraso), pagos 8-18 atrasados
-- ============================================================
INSERT INTO pagos (prestamo_id, cobrador_id, numero_pago, monto_cuota, interes, capital, saldo_restante, fecha_programada, fecha_pago, monto_cobrado, tipo_cobro, estatus) VALUES
(5, 2,  1, 2200, 350, 1850, 33150, '2024-02-01', '2024-02-01 09:00:00', 2200, 'completo', 'Pagado'),
(5, 2,  2, 2200, 332, 1868, 31282, '2024-03-01', '2024-03-03 10:15:00', 2200, 'completo', 'Pagado'),
(5, 2,  3, 2200, 313, 1887, 29395, '2024-04-01', '2024-04-01 09:30:00', 2200, 'completo', 'Pagado'),
(5, 2,  4, 2200, 294, 1906, 27489, '2024-05-01', '2024-05-02 11:00:00', 2200, 'completo', 'Pagado'),
(5, 2,  5, 2200, 275, 1925, 25564, '2024-06-01', '2024-06-01 09:00:00', 2200, 'completo', 'Pagado'),
(5, 2,  6, 2200, 256, 1944, 23620, '2024-07-01', '2024-07-05 14:20:00', 2200, 'completo', 'Pagado'),
(5, 2,  7, 2200, 236, 1964, 21656, '2024-08-01', '2024-08-01 09:00:00', 2200, 'completo', 'Pagado'),
(5, 2,  8, 2200, 217, 1983, 19673, '2024-09-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2,  9, 2200, 197, 2003, 17670, '2024-10-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 10, 2200, 177, 2023, 15647, '2024-11-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 11, 2200, 156, 2044, 13603, '2024-12-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 12, 2200, 136, 2064, 11539, '2025-01-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 13, 2200, 115, 2085,  9454, '2025-02-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 14, 2200,  95, 2105,  7349, '2025-03-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 15, 2200,  73, 2127,  5222, '2025-04-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 16, 2200,  52, 2148,  3074, '2025-05-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 17, 2200,  31, 2169,   905, '2025-06-01', NULL, NULL, NULL, 'Atrasado'),
(5, 2, 18,  914,   9,  905,     0, '2025-07-01', NULL, NULL, NULL, 'Atrasado');
