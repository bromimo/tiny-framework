<?php

namespace App\Core;

use ReflectionClass;
use ReflectionParameter;
use ReflectionNamedType;
use App\Exceptions\BindingResolutionException;

/** DI-контейнер с autowiring через конструкторы. */
class Container
{
    /** @var array<string, array{concrete: string|callable, singleton: bool}> */
    private array $bindings = [];

    /** @var array<string, mixed> Зарезолвленные синглтоны и instance-привязки. */
    private array $instances = [];

    /** @var string[] Стек сборки для обнаружения циклических зависимостей. */
    private array $buildStack = [];

    /** Зарегистрировать привязку (новый экземпляр при каждом resolve).
     * @param string $abstract Абстракция (интерфейс или class-string).
     * @param string|callable|null $concrete Реализация; null = $abstract.
     */
    public function bind(string $abstract, string|callable|null $concrete = null): void
    {
        unset($this->instances[$abstract]);

        $this->bindings[$abstract] = [
            'concrete'  => $concrete ?? $abstract,
            'singleton' => false,
        ];
    }

    /** Зарегистрировать привязку-синглтон (один экземпляр на всё время жизни контейнера).
     * @param string $abstract Абстракция (интерфейс или class-string).
     * @param string|callable|null $concrete Реализация; null = $abstract.
     */
    public function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        unset($this->instances[$abstract]);

        $this->bindings[$abstract] = [
            'concrete'  => $concrete ?? $abstract,
            'singleton' => true,
        ];
    }

    /** Зарегистрировать готовый экземпляр.
     * @param string $abstract Абстракция.
     * @param mixed $instance Экземпляр.
     */
    public function instance(string $abstract, mixed $instance): void
    {
        $this->instances[$abstract] = $instance;
    }

    /** Зарезолвить абстракцию из контейнера.
     * @param string $abstract Абстракция или class-string.
     * @param array<string, mixed> $params Дополнительные параметры для конструктора.
     * @return mixed
     * @throws BindingResolutionException Если зависимость не может быть зарезолвлена.
     */
    public function make(string $abstract, array $params = []): mixed
    {
        if (isset($this->instances[$abstract])) {
            return $this->instances[$abstract];
        }

        $binding   = $this->bindings[$abstract] ?? null;
        $concrete  = $binding['concrete'] ?? $abstract;
        $singleton = $binding['singleton'] ?? false;

        if (is_callable($concrete)) {
            $resolved = $concrete($this, ...$params);
        } else {
            $resolved = $this->build($concrete, $params);
        }

        if ($singleton) {
            $this->instances[$abstract] = $resolved;
        }

        return $resolved;
    }

    /** Проверить наличие привязки или экземпляра.
     * @param string $abstract Абстракция.
     * @return bool
     */
    public function has(string $abstract): bool
    {
        return isset($this->bindings[$abstract]) || isset($this->instances[$abstract]);
    }

    /** Очистить все привязки и экземпляры. */
    public function flush(): void
    {
        $this->bindings   = [];
        $this->instances  = [];
        $this->buildStack = [];
    }

    /** Собрать экземпляр класса через Reflection с autowiring.
     * @param string $concrete Class-string для инстанцирования.
     * @param array<string, mixed> $params Дополнительные параметры.
     * @return mixed
     * @throws BindingResolutionException
     */
    private function build(string $concrete, array $params = []): mixed
    {
        if (in_array($concrete, $this->buildStack, true)) {
            $chain = implode(' → ', [...$this->buildStack, $concrete]);
            throw new BindingResolutionException("Circular dependency detected: {$chain}");
        }

        $this->buildStack[] = $concrete;

        try {
            $reflector = new ReflectionClass($concrete);
        } catch (\ReflectionException $e) {
            throw new BindingResolutionException("Class [{$concrete}] does not exist.", 0, $e);
        }

        if (!$reflector->isInstantiable()) {
            throw new BindingResolutionException("Class [{$concrete}] is not instantiable.");
        }

        $constructor = $reflector->getConstructor();

        if ($constructor === null) {
            array_pop($this->buildStack);
            return new $concrete();
        }

        $dependencies = $this->resolveDependencies($constructor->getParameters(), $params);

        array_pop($this->buildStack);

        return $reflector->newInstanceArgs($dependencies);
    }

    /** Зарезолвить параметры конструктора.
     * @param ReflectionParameter[] $parameters
     * @param array<string, mixed> $params
     * @return array<int, mixed>
     * @throws BindingResolutionException
     */
    private function resolveDependencies(array $parameters, array $params): array
    {
        $resolved = [];

        foreach ($parameters as $parameter) {
            $name = $parameter->getName();

            if (array_key_exists($name, $params)) {
                $resolved[] = $params[$name];
                continue;
            }

            $type = $parameter->getType();

            if ($type instanceof ReflectionNamedType && !$type->isBuiltin()) {
                try {
                    $resolved[] = $this->make($type->getName());
                    continue;
                } catch (BindingResolutionException $e) {
                    if ($parameter->isDefaultValueAvailable()) {
                        $resolved[] = $parameter->getDefaultValue();
                        continue;
                    }
                    throw $e;
                }
            }

            if ($parameter->isDefaultValueAvailable()) {
                $resolved[] = $parameter->getDefaultValue();
                continue;
            }

            throw new BindingResolutionException(
                "Unresolvable dependency [{$parameter}] in class [{$parameter->getDeclaringClass()->getName()}]."
            );
        }

        return $resolved;
    }
}
