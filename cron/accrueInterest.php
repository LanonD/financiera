<?php
/**
 * Cron de interés diario — ejecutar a medianoche
 *
 * Windows Task Scheduler (cmd):
 *   "C:\xampp\php\php.exe" "C:\xampp\htdocs\financiera_mvc\cron\accrueInterest.php"
 *
 * Horario recomendado: todos los días a las 00:05 AM
 */
if (php_sapi_name() !== 'cli') {
    http_response_code(403);
    exit('Acceso solo desde CLI.');
}

define('ROOT_PATH', dirname(__DIR__));
require_once ROOT_PATH . '/config/database.php';
require_once ROOT_PATH . '/app/models/Loan.php';

$loan = new Loan();

$n = $loan->accrueInterest();
echo date('Y-m-d H:i:s') . " — Interés diario acumulado en {$n} préstamo(s).\n";

$r = $loan->expirePending();
echo date('Y-m-d H:i:s') . " — {$r} préstamo(s) Pendiente → Retirado (más de 7 días sin desembolsar).\n";
