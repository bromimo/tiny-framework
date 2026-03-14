<?php

namespace Tests\Unit\Core;

use App\Core\Container;
use PHPUnit\Framework\TestCase;
use App\Exceptions\BindingResolutionException;

class ContainerTest extends TestCase
{
    private Container $container;

    protected function setUp(): void
    {
        $this->container = new Container();
    }

    public function test_make_resolves_concrete_class_without_binding(): void
    {
        $obj = $this->container->make(ConcreteStub::class);
        $this->assertInstanceOf(ConcreteStub::class, $obj);
    }

    public function test_bind_and_make_resolves_interface_to_concrete(): void
    {
        $this->container->bind(StubInterface::class, ConcreteStub::class);

        $obj = $this->container->make(StubInterface::class);
        $this->assertInstanceOf(ConcreteStub::class, $obj);
    }

    public function test_bind_returns_new_instance_each_time(): void
    {
        $this->container->bind(ConcreteStub::class);

        $a = $this->container->make(ConcreteStub::class);
        $b = $this->container->make(ConcreteStub::class);

        $this->assertNotSame($a, $b);
    }

    public function test_singleton_returns_same_instance(): void
    {
        $this->container->singleton(ConcreteStub::class);

        $a = $this->container->make(ConcreteStub::class);
        $b = $this->container->make(ConcreteStub::class);

        $this->assertSame($a, $b);
    }

    public function test_instance_returns_registered_object(): void
    {
        $obj = new ConcreteStub();
        $this->container->instance(ConcreteStub::class, $obj);

        $this->assertSame($obj, $this->container->make(ConcreteStub::class));
    }

    public function test_bind_with_callable_factory(): void
    {
        $this->container->bind(StubInterface::class, fn(Container $c) => new ConcreteStub());

        $obj = $this->container->make(StubInterface::class);
        $this->assertInstanceOf(ConcreteStub::class, $obj);
    }

    public function test_autowiring_resolves_typed_dependencies(): void
    {
        $obj = $this->container->make(DependentStub::class);

        $this->assertInstanceOf(DependentStub::class, $obj);
        $this->assertInstanceOf(ConcreteStub::class, $obj->dependency);
    }

    public function test_autowiring_uses_default_values_for_scalars(): void
    {
        $obj = $this->container->make(DefaultValueStub::class);

        $this->assertSame('default', $obj->value);
    }

    public function test_make_passes_params_by_name(): void
    {
        $obj = $this->container->make(DefaultValueStub::class, ['value' => 'custom']);

        $this->assertSame('custom', $obj->value);
    }

    public function test_make_throws_on_unresolvable_scalar(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Unresolvable dependency');

        $this->container->make(UnresolvableStub::class);
    }

    public function test_make_throws_on_non_existent_class(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('does not exist');

        $this->container->make('NonExistentClass');
    }

    public function test_make_throws_on_non_instantiable_class(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('not instantiable');

        $this->container->make(StubInterface::class);
    }

    public function test_circular_dependency_throws_exception(): void
    {
        $this->expectException(BindingResolutionException::class);
        $this->expectExceptionMessage('Circular dependency');

        $this->container->make(CircularA::class);
    }

    public function test_has_returns_true_for_binding(): void
    {
        $this->container->bind(StubInterface::class, ConcreteStub::class);
        $this->assertTrue($this->container->has(StubInterface::class));
    }

    public function test_has_returns_true_for_instance(): void
    {
        $this->container->instance('foo', new ConcreteStub());
        $this->assertTrue($this->container->has('foo'));
    }

    public function test_has_returns_false_for_unknown(): void
    {
        $this->assertFalse($this->container->has('unknown'));
    }

    public function test_flush_clears_everything(): void
    {
        $this->container->singleton(ConcreteStub::class);
        $this->container->make(ConcreteStub::class);
        $this->container->instance('foo', 'bar');

        $this->container->flush();

        $this->assertFalse($this->container->has(ConcreteStub::class));
        $this->assertFalse($this->container->has('foo'));
    }

    public function test_bind_overrides_previous_binding(): void
    {
        $this->container->bind(StubInterface::class, ConcreteStub::class);
        $this->container->bind(StubInterface::class, AlternateStub::class);

        $obj = $this->container->make(StubInterface::class);
        $this->assertInstanceOf(AlternateStub::class, $obj);
    }

    public function test_bind_clears_cached_singleton(): void
    {
        $this->container->singleton(StubInterface::class, ConcreteStub::class);
        $first = $this->container->make(StubInterface::class);

        $this->container->bind(StubInterface::class, AlternateStub::class);
        $second = $this->container->make(StubInterface::class);

        $this->assertInstanceOf(ConcreteStub::class, $first);
        $this->assertInstanceOf(AlternateStub::class, $second);
    }

    public function test_autowiring_with_interface_binding_in_chain(): void
    {
        $this->container->bind(StubInterface::class, ConcreteStub::class);

        $obj = $this->container->make(NeedsInterfaceStub::class);

        $this->assertInstanceOf(ConcreteStub::class, $obj->dep);
    }

    public function test_autowiring_uses_default_when_class_unresolvable(): void
    {
        $obj = $this->container->make(OptionalDependencyStub::class);

        $this->assertNull($obj->dep);
    }
}

// --- Стабы для тестов ---

interface StubInterface
{
}

class ConcreteStub implements StubInterface
{
}

class AlternateStub implements StubInterface
{
}

class DependentStub
{
    public function __construct(public readonly ConcreteStub $dependency)
    {
    }
}

class DefaultValueStub
{
    public function __construct(public readonly string $value = 'default')
    {
    }
}

class UnresolvableStub
{
    public function __construct(public readonly string $required)
    {
    }
}

class CircularA
{
    public function __construct(public readonly CircularB $b)
    {
    }
}

class CircularB
{
    public function __construct(public readonly CircularA $a)
    {
    }
}

class NeedsInterfaceStub
{
    public function __construct(public readonly StubInterface $dep)
    {
    }
}

class OptionalDependencyStub
{
    public function __construct(public readonly ?StubInterface $dep = null)
    {
    }
}
