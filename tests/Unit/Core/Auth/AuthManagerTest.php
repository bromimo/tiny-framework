<?php

namespace Tests\Unit\Core\Auth;

use App\Core\Auth\AuthManager;
use App\Core\Auth\TokenGuard;
use App\Core\Auth\GuardInterface;
use PHPUnit\Framework\TestCase;

class AuthManagerTest extends TestCase
{
    public function test_guard_returns_default_guard(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $guard = $manager->guard();

        $this->assertInstanceOf(TokenGuard::class, $guard);
    }

    public function test_guard_returns_named_guard(): void
    {
        $tokenGuard = new TokenGuard();
        $manager = new AuthManager('api', ['api' => $tokenGuard]);

        $this->assertSame($tokenGuard, $manager->guard('api'));
    }

    public function test_guard_throws_for_unknown_name(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $this->expectException(\InvalidArgumentException::class);
        $manager->guard('web');
    }

    public function test_user_delegates_to_default_guard(): void
    {
        $manager = new AuthManager('api', ['api' => new TokenGuard()]);

        $this->assertNull($manager->user());
        $this->assertFalse($manager->check());
        $this->assertNull($manager->id());
    }

    public function test_set_user_stores_user_on_guard(): void
    {
        $guard = $this->createMock(GuardInterface::class);
        $guard->method('user')->willReturn(null);
        $guard->method('check')->willReturn(false);

        $manager = new AuthManager('api', ['api' => $guard]);

        $this->assertFalse($manager->check());
    }
}
