<?php

namespace Tests\Unit\Core\Auth;

use App\Core\Auth\Gate;
use App\Models\User;
use PHPUnit\Framework\TestCase;
use App\Core\Auth\PolicyInterface;

class StubModel
{
    public int $id = 1;
}

class StubPolicy implements PolicyInterface
{
    public bool $viewResult = true;
    public bool $createResult = false;

    public function view(User $authUser, StubModel $model): bool
    {
        return $this->viewResult;
    }

    public function create(User $authUser): bool
    {
        return $this->createResult;
    }
}

class GateTest extends TestCase
{
    private Gate $gate;
    private StubPolicy $policy;

    protected function setUp(): void
    {
        $this->policy = new StubPolicy();
        $this->gate = new Gate();
        $this->gate->register(StubModel::class, $this->policy);
    }

    public function test_authorize_calls_policy_method_with_model_instance(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->assertTrue($this->gate->authorize($user, 'view', $model));
    }

    public function test_authorize_calls_policy_method_with_class_string(): void
    {
        $user = $this->createStub(User::class);

        $this->assertFalse($this->gate->authorize($user, 'create', StubModel::class));
    }

    public function test_authorize_throws_for_unregistered_model(): void
    {
        $user = $this->createStub(User::class);

        $this->expectException(\InvalidArgumentException::class);
        $this->gate->authorize($user, 'view', new \stdClass());
    }

    public function test_authorize_throws_for_unknown_ability(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->expectException(\BadMethodCallException::class);
        $this->gate->authorize($user, 'nonexistent', $model);
    }

    public function test_denies_is_inverse_of_authorize(): void
    {
        $user = $this->createStub(User::class);
        $model = new StubModel();

        $this->assertFalse($this->gate->denies($user, 'view', $model));
        $this->assertTrue($this->gate->denies($user, 'create', StubModel::class));
    }

    public function test_policy_returns_registered_policy(): void
    {
        $policy = $this->gate->policy(StubModel::class);

        $this->assertSame($this->policy, $policy);
    }
}
