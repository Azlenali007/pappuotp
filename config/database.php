<?php
/**
 * Database Connection & PDO Helper
 * Supports MySQL / MariaDB via PDO
 */

require_once __DIR__ . '/constants.php';

class Database {
    private static ?PDO $instance = null;

    public static function getConnection(): PDO {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $host = getenv('DB_HOST') ?: '127.0.0.1';
        $port = getenv('DB_PORT') ?: '3306';
        $dbname = getenv('DB_NAME') ?: 'otp_platform';
        $username = getenv('DB_USER') ?: 'otp_user';
        $password = getenv('DB_PASS') !== false ? getenv('DB_PASS') : 'otp_password';

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4";

        $options = [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES => false,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci"
        ];

        try {
            self::$instance = new PDO($dsn, $username, $password, $options);
            return self::$instance;
        } catch (PDOException $e) {
            // Try root with no password if local development socket
            try {
                $altDsn = "mysql:host=127.0.0.1;port=3306;dbname={$dbname};charset=utf8mb4";
                self::$instance = new PDO($altDsn, 'root', '', $options);
                return self::$instance;
            } catch (PDOException $ex) {
                error_log("Database connection failure: " . $ex->getMessage());
                die("<div style='font-family:system-ui;padding:40px;background:#fff5f5;border-left:5px solid #e53e3e;margin:40px;'>
                    <h2 style='color:#c53030;margin:0 0 10px;'>Database Connection Error</h2>
                    <p style='color:#4a5568;'>Could not establish connection to the database. Please verify MySQL service is running and credentials in <code>config/database.php</code> are valid.</p>
                </div>");
            }
        }
    }
}

function get_db(): PDO {
    return Database::getConnection();
}

function getDB(): PDO {
    return Database::getConnection();
}
