<?php

namespace App\Models;

use App\Abstracts\BaseModel;

class Token extends BaseModel
{
    protected static string $table = 'tokens';

    public static function create(int $userId): array
    {
        $token     = generateUuid();
        $expiresAt = date('Y-m-d H:i:s', strtotime('+28 days'));

        $stmt = static::db()->prepare(
            'INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)'
        );
        $stmt->execute([$userId, $token, $expiresAt]);

        return ['token' => $token, 'expires_at' => $expiresAt];
    }

    public static function findValid(string $token): ?array
    {
        $stmt = static::db()->prepare(
            'SELECT * FROM tokens WHERE token = ? AND expires_at > NOW()'
        );
        $stmt->execute([$token]);
        return $stmt->fetch() ?: null;
    }

    public static function deleteByToken(string $token): bool
    {
        $stmt = static::db()->prepare('DELETE FROM tokens WHERE token = ?');
        return $stmt->execute([$token]);
    }
}
