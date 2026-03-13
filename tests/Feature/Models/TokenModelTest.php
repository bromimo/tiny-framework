<?php

namespace Tests\Feature\Models;

use App\DTOs\UserDto;
use App\Models\Token;
use App\Models\User;
use Tests\Support\FeatureTestCase;

/** Интеграционные тесты модели Token. */
class TokenModelTest extends FeatureTestCase
{
    /** Создать тестового пользователя и вернуть его ID.
     * @return int
     */
    private function createUserId(): int
    {
        $dto  = new UserDto(
            first_name: 'Test',
            last_name: 'User',
            email: 'token_user_' . uniqid() . '@example.com',
            password: 'password123',
        );
        $user = User::create($dto);

        return (int) $user->id;
    }

    /** Создание токена возвращает Token с полем token в формате UUID v4. */
    public function test_create_returns_token_with_uuid(): void
    {
        $userId = $this->createUserId();
        $token  = Token::create($userId);

        $this->assertInstanceOf(Token::class, $token);
        $this->assertMatchesRegularExpression(
            '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i',
            $token->token,
        );
    }

    /** Созданный токен имеет expires_at в будущем. */
    public function test_create_sets_expires_at_in_future(): void
    {
        $userId    = $this->createUserId();
        $token     = Token::create($userId);
        $expiresAt = strtotime($token->expires_at);

        $this->assertGreaterThan(time(), $expiresAt);
    }

    /** findValid возвращает токен по его строковому значению. */
    public function test_find_valid_returns_token(): void
    {
        $userId  = $this->createUserId();
        $created = Token::create($userId);

        $found = Token::findValid($created->token);

        $this->assertInstanceOf(Token::class, $found);
        $this->assertSame($created->token, $found->token);
    }

    /** findValid возвращает null для несуществующего токена. */
    public function test_find_valid_returns_null_for_unknown_token(): void
    {
        $result = Token::findValid('00000000-0000-4000-8000-000000000000');

        $this->assertNull($result);
    }

    /** findValid возвращает null для истёкшего токена. */
    public function test_find_valid_returns_null_for_expired_token(): void
    {
        $userId  = $this->createUserId();
        $created = Token::create($userId);

        qi(
            'UPDATE tokens SET expires_at = ? WHERE id = ?',
            ['2000-01-01 00:00:00', $created->id],
        );

        $result = Token::findValid($created->token);

        $this->assertNull($result);
    }

    /** deleteByToken удаляет токен; findValid возвращает null после удаления. */
    public function test_delete_by_token_removes_token(): void
    {
        $userId  = $this->createUserId();
        $created = Token::create($userId);

        $deleted = Token::deleteByToken($created->token);

        $this->assertTrue($deleted);
        $this->assertNull(Token::findValid($created->token));
    }

    /** deleteByToken возвращает false для несуществующего токена. */
    public function test_delete_by_token_returns_false_for_unknown(): void
    {
        $result = Token::deleteByToken('00000000-0000-4000-8000-000000000000');

        $this->assertFalse($result);
    }

    /** deleteExpired удаляет только истёкшие токены, не затрагивая актуальные. */
    public function test_delete_expired_removes_only_expired(): void
    {
        $userId       = $this->createUserId();
        $validToken   = Token::create($userId);
        $expiredToken = Token::create($userId);

        qi(
            'UPDATE tokens SET expires_at = ? WHERE id = ?',
            ['2000-01-01 00:00:00', $expiredToken->id],
        );

        $deleted = Token::deleteExpired();

        $this->assertGreaterThanOrEqual(1, $deleted);
        $this->assertInstanceOf(Token::class, Token::findValid($validToken->token));
        $this->assertNull(Token::findValid($expiredToken->token));
    }

    /** Один пользователь может иметь несколько активных токенов одновременно. */
    public function test_multiple_tokens_per_user(): void
    {
        $userId  = $this->createUserId();
        $token1  = Token::create($userId);
        $token2  = Token::create($userId);

        $this->assertNotSame($token1->token, $token2->token);
        $this->assertInstanceOf(Token::class, Token::findValid($token1->token));
        $this->assertInstanceOf(Token::class, Token::findValid($token2->token));
    }
}
