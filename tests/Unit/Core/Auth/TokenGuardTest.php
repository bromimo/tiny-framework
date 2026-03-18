<?php

namespace Tests\Unit\Core\Auth;

use App\DTOs\UserDto;
use App\Models\User;
use App\Models\Token;
use TinyRouter\Http\Method;
use App\Core\Auth\TokenGuard;
use TinyRouter\Http\Request;
use Tests\Support\FeatureTestCase;

class TokenGuardTest extends FeatureTestCase
{
    private TokenGuard $guard;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seedRoles();
        $this->guard = new TokenGuard();
    }

    public function test_validate_with_valid_token_returns_user(): void
    {
        $user = User::create(new UserDto('John', 'Doe', 'john@example.com', 'secret123'));
        $token = Token::create($user->id);

        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer ' . $token->token],
        );

        $result = $this->guard->validate($request);

        $this->assertInstanceOf(User::class, $result);
        $this->assertSame($user->id, $result->id);
    }

    public function test_validate_without_token_returns_null(): void
    {
        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: [],
        );

        $this->assertNull($this->guard->validate($request));
    }

    public function test_validate_with_invalid_token_returns_null(): void
    {
        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer invalid-token-here'],
        );

        $this->assertNull($this->guard->validate($request));
    }

    public function test_user_returns_cached_result_after_validate(): void
    {
        $user = User::create(new UserDto('Jane', 'Doe', 'jane@example.com', 'secret123'));
        $token = Token::create($user->id);

        $request = new Request(
            method: Method::GET,
            path: '/api/v1/users',
            query: [],
            body: [],
            headers: ['authorization' => 'Bearer ' . $token->token],
        );

        $this->guard->validate($request);

        $this->assertSame($user->id, $this->guard->user()->id);
        $this->assertTrue($this->guard->check());
        $this->assertSame($user->id, $this->guard->id());
    }

    public function test_check_returns_false_before_validate(): void
    {
        $this->assertFalse($this->guard->check());
        $this->assertNull($this->guard->user());
        $this->assertNull($this->guard->id());
    }

    private function seedRoles(): void
    {
        qi("INSERT IGNORE INTO roles (id, name, description) VALUES (1, 'user', 'Пользователь')");
    }
}
