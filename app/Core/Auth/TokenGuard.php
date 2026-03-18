<?php

namespace App\Core\Auth;

use App\Models\User;
use App\Models\Token;
use TinyRouter\Http\Request;

/** Guard аутентификации по Bearer-токену. */
class TokenGuard implements GuardInterface
{
    private ?User $user = null;
    private bool $validated = false;

    /** Получить текущего аутентифицированного пользователя.
     * @return User|null
     */
    public function user(): ?User
    {
        return $this->user;
    }

    /** Проверить, аутентифицирован ли пользователь.
     * @return bool
     */
    public function check(): bool
    {
        return $this->user !== null;
    }

    /** Получить ID текущего пользователя.
     * @return int|null
     */
    public function id(): ?int
    {
        return $this->user?->id;
    }

    /** Провалидировать запрос по Bearer-токену и установить пользователя.
     * @param Request $request
     * @return User|null
     */
    public function validate(Request $request): ?User
    {
        if ($this->validated) {
            return $this->user;
        }

        $this->validated = true;

        $token = $this->extractBearerToken($request);
        if ($token === null) {
            return null;
        }

        $record = Token::findValid($token);
        if ($record === null) {
            return null;
        }

        $this->user = User::findById($record->user_id);

        return $this->user;
    }

    /** Извлечь Bearer-токен из заголовка Authorization.
     * @param Request $request
     * @return string|null
     */
    private function extractBearerToken(Request $request): ?string
    {
        $header = $request->headers['authorization'] ?? '';

        if (preg_match('/^Bearer\s+(.+)$/i', $header, $matches)) {
            return trim($matches[1]);
        }

        return null;
    }
}
