<?php

namespace App\Models;

use App\Abstracts\BaseModel;

/** Модель таблицы `tokens`. */
class Token extends BaseModel
{
    protected static string $table = 'tokens';

    /** Создать новый токен аутентификации для указанного пользователя.
     * @param int $userId
     * @return static
     * @throws \Random\RandomException Если не удалось получить случайные байты для UUID.
     */
    public static function create(int $userId): static
    {
        $token     = generateUuid();
        $expiresAt = date('Y-m-d H:i:s', strtotime(config('auth.token.lifetime')));

        $id = qi('INSERT INTO tokens (user_id, token, expires_at) VALUES (?, ?, ?)', [$userId, $token, $expiresAt]);

        return static::findById($id);
    }

    /** Найти токен, срок действия которого ещё не истёк, и обновить last_used_at.
     * @param string $token Значение токена.
     * @return static|null Null если токен не найден или истёк.
     */
    public static function findValid(string $token): ?static
    {
        $data = q1('SELECT * FROM tokens WHERE token = ? AND expires_at > NOW()', [$token]);

        if ($data === null) {
            return null;
        }

        qi('UPDATE tokens SET last_used_at = NOW() WHERE id = ?', [$data['id']]);

        return new static($data);
    }

    /** Удалить все истёкшие токены.
     * @return int Количество удалённых токенов.
     */
    public static function deleteExpired(): int
    {
        return qi('DELETE FROM tokens WHERE expires_at <= NOW()');
    }

    /** Удалить токен по его значению.
     * @param string $token Значение токена.
     * @return bool True если токен был найден и удалён.
     */
    public static function deleteByToken(string $token): bool
    {
        return qi('DELETE FROM tokens WHERE token = ?', [$token]) > 0;
    }
}
