<?php

namespace App\Core;

use Dotenv\Dotenv;

/** Обёртка над суперглобальным массивом $_ENV.
 * Предоставляет типобезопасный доступ к переменным окружения.
 */
class Env
{
    /** Загрузить переменные окружения из указанного файла.
     * @param string $path Путь к директории, содержащей файл окружения.
     * @param string $file Имя файла окружения.
     * @return void
     */
    public static function load(string $path, string $file = '.env'): void
    {
        Dotenv::createImmutable($path, $file)->load();
    }

    /** Загрузить переменные окружения без исключения если файл отсутствует.
     * Используется в тестах и окружениях без файла окружения.
     * @param string $path Путь к директории, содержащей файл окружения.
     * @param string $file Имя файла окружения.
     * @return void
     */
    public static function safeLoad(string $path, string $file = '.env'): void
    {
        Dotenv::createImmutable($path, $file)->safeLoad();
    }

    /** Получить переменную окружения по ключу с приведением типов.
     * Строки 'true'/'false' → bool, 'null' → null, '(empty)' → ''.
     * Значения в кавычках возвращаются без них.
     * @param string $key Имя переменной.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        if (!array_key_exists($key, $_ENV)) {
            return $default;
        }

        return self::cast($_ENV[$key]);
    }

    /** Привести строковое значение к PHP-типу.
     * Не-строки возвращаются без изменений.
     * @param mixed $value Исходное значение.
     * @return mixed
     */
    public static function cast(mixed $value): mixed
    {
        if (!is_string($value)) {
            return $value;
        }

        return match (strtolower($value)) {
            'true',  '(true)'  => true,
            'false', '(false)' => false,
            'null',  '(null)'  => null,
            'empty', '(empty)' => '',
            default => preg_match('/\A([\'"])(.*)\1\z/s', $value, $m) ? $m[2] : $value,
        };
    }
}
