<?php

namespace App\Facades;

use App\Core\Container;

/** Статический фасад над DI-контейнером. */
class App
{
    /** @var Container|null Единственный экземпляр контейнера. */
    private static ?Container $instance = null;

    /** Вернуть экземпляр, создав при первом обращении.
     * @return Container
     */
    public static function getInstance(): Container
    {
        if (self::$instance === null) {
            self::$instance = new Container();
        }

        return self::$instance;
    }

    /** Установить экземпляр. Используется в bootstrap и тестах.
     * @param Container $container Экземпляр контейнера.
     */
    public static function setInstance(Container $container): void
    {
        self::$instance = $container;
    }

    /** Сбросить экземпляр. Используется в тестах. */
    public static function reset(): void
    {
        self::$instance = null;
    }

    /** Зарегистрировать привязку (новый экземпляр при каждом resolve).
     * @param string $abstract Абстракция (интерфейс или class-string).
     * @param string|callable|null $concrete Реализация; null = $abstract.
     */
    public static function bind(string $abstract, string|callable|null $concrete = null): void
    {
        self::getInstance()->bind($abstract, $concrete);
    }

    /** Зарегистрировать привязку-синглтон.
     * @param string $abstract Абстракция (интерфейс или class-string).
     * @param string|callable|null $concrete Реализация; null = $abstract.
     */
    public static function singleton(string $abstract, string|callable|null $concrete = null): void
    {
        self::getInstance()->singleton($abstract, $concrete);
    }

    /** Зарегистрировать готовый экземпляр.
     * @param string $abstract Абстракция.
     * @param mixed $inst Экземпляр.
     */
    public static function instance(string $abstract, mixed $inst): void
    {
        self::getInstance()->instance($abstract, $inst);
    }

    /** Зарезолвить абстракцию из контейнера.
     * @param string $abstract Абстракция или class-string.
     * @param array<string, mixed> $params Дополнительные параметры.
     * @return mixed
     * @throws \App\Exceptions\BindingResolutionException
     */
    public static function make(string $abstract, array $params = []): mixed
    {
        return self::getInstance()->make($abstract, $params);
    }

    /** Проверить наличие привязки или экземпляра.
     * @param string $abstract Абстракция.
     * @return bool
     */
    public static function has(string $abstract): bool
    {
        return self::getInstance()->has($abstract);
    }

    /** Очистить все привязки и экземпляры. */
    public static function flush(): void
    {
        self::getInstance()->flush();
    }
}
