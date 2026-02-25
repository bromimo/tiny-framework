<?php

namespace App\Abstracts;

use App\Support\Database;
use PDO;

abstract class BaseModel
{
    protected static string $table = '';

    protected static function db(): PDO
    {
        return Database::get();
    }

    public static function findById(int $id): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM ' . static::$table . ' WHERE id = ?'
        );
        $stmt->execute([$id]);
        return $stmt->fetch() ?: null;
    }

    public static function findAll(): array
    {
        $stmt = static::db()->query('SELECT * FROM ' . static::$table);
        return $stmt->fetchAll();
    }

    public static function deleteById(int $id): bool
    {
        $stmt = static::db()->prepare(
            'DELETE FROM ' . static::$table . ' WHERE id = ?'
        );
        return $stmt->execute([$id]);
    }
}
