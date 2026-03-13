<?php

namespace App\Core;

use PDO;
use PDOException;

/** Управляет единственным PDO-соединением приложения (синглтон).
 * Параметры подключения берутся из переменных окружения .env.
 */
class Database
{
    /** @var PDO|null Единственный экземпляр PDO-соединения. */
    private static ?PDO $instance = null;

    /** Вернуть общий экземпляр PDO, создав соединение при первом вызове.
     * @throws PDOException Если соединение с базой данных не удалось установить.
     */
    public static function get(): PDO
    {
        if (self::$instance === null) {
            try {
                self::$instance = new PDO(
                    'mysql:host=' . env('DB_HOST', '127.0.0.1')
                    . ';port=' . env('DB_PORT', '3306')
                    . ';dbname=' . env('DB_DATABASE', 'proj1')
                    . ';charset=utf8mb4',
                    env('DB_USERNAME', 'root'),
                    env('DB_PASSWORD', ''),
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

    /** Сбросить соединение. Используется в тестах для принудительного пересоздания PDO. */
    public static function reset(): void
    {
        self::$instance = null;
    }
}
