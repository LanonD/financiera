<?php
define('DB_HOST',     'localhost');
define('DB_USER',     'root');
define('DB_PASSWORD', '');
define('DB_NAME',     'financiera');
define('DB_CHARSET',  'utf8mb4');

class Database {
    private static ?mysqli $instance = null;

    public static function connect(): mysqli {
        if (self::$instance === null) {
            $conn = new mysqli(DB_HOST, DB_USER, DB_PASSWORD, DB_NAME);
            if ($conn->connect_error) {
                error_log('[DB ERROR] ' . $conn->connect_error);
                die('Error de conexión');
            }
            $conn->set_charset(DB_CHARSET);
            self::$instance = $conn;
        }
        return self::$instance;
    }

    private function __clone() {}
}
