<?php

namespace App\Models;

use App\Abstracts\BaseModel;
use App\DTOs\UserDto;

class User extends BaseModel
{
    protected static string $table = 'users';

    public static function findByEmail(string $email): ?array
    {
        $stmt = static::db()->prepare('SELECT * FROM users WHERE email = ?');
        $stmt->execute([$email]);
        return $stmt->fetch() ?: null;
    }

    public static function create(UserDto $dto): array
    {
        $stmt = static::db()->prepare(
            'INSERT INTO users (name, surname, email, password) VALUES (?, ?, ?, ?)'
        );
        $stmt->execute([
            $dto->name,
            $dto->surname,
            $dto->email,
            password_hash($dto->password, PASSWORD_BCRYPT),
        ]);

        return static::findById((int) static::db()->lastInsertId());
    }

    public static function update(int $id, UserDto $dto): ?array
    {
        $fields = [];
        $values = [];

        if ($dto->name !== '') {
            $fields[] = 'name = ?';
            $values[] = $dto->name;
        }
        if ($dto->surname !== '') {
            $fields[] = 'surname = ?';
            $values[] = $dto->surname;
        }
        if ($dto->email !== '') {
            $fields[] = 'email = ?';
            $values[] = $dto->email;
        }
        if ($dto->password !== null) {
            $fields[] = 'password = ?';
            $values[] = password_hash($dto->password, PASSWORD_BCRYPT);
        }

        if (empty($fields)) {
            return static::findById($id);
        }

        $values[] = $id;
        $stmt = static::db()->prepare(
            'UPDATE users SET ' . implode(', ', $fields) . ' WHERE id = ?'
        );
        $stmt->execute($values);

        return static::findById($id);
    }

    public static function withoutPassword(array $user): array
    {
        unset($user['password']);
        return $user;
    }
}
