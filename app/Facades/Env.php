<?php

namespace App\Facades;

use App\Core\Env as EnvCore;

/** Статический фасад над App\Core\Env.
 * Предоставляет удобный доступ к переменным окружения.
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
        EnvCore::load($path, $file);
    }

    /** Загрузить переменные окружения без исключения если файл отсутствует.
     * @param string $path Путь к директории, содержащей файл окружения.
     * @param string $file Имя файла окружения.
     * @return void
     */
    public static function safeLoad(string $path, string $file = '.env'): void
    {
        EnvCore::safeLoad($path, $file);
    }

    /** Получить переменную окружения по ключу.
     * @param string $key Имя переменной.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return EnvCore::get($key, $default);
    }
}
