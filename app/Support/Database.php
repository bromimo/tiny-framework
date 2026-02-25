<?php

namespace App\Support;

use PDO;
use PDOException;

class Database
{
    private static ?PDO $instance = null;

    public static function get(): PDO
    {
        if (self::$instance === null) {
            $config = require __DIR__ . '/../../config/database.php';

            try {
                self::$instance = new PDO(
                    "mysql:host={$config['host']};port={$config['port']};dbname={$config['database']};charset=utf8mb4",
                    $config['username'],
                    $config['password'],
                    [
                        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    ]
                );
            } catch (PDOException $e) {
                Logger::error('DB connection failed: ' . $e->getMessage());
                throw $e;
            }
        }

        return self::$instance;
    }

    // Allow resetting in tests
    public static function reset(): void
    {
        self::$instance = null;
    }
}
