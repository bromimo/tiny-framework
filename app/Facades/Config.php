<?php

namespace App\Facades;

use App\Core\Config as ConfigCore;

/** Статический фасад над App\Core\Config.
 * Предоставляет удобный доступ к конфигурации приложения.
 */
class Config
{
    /** Загрузить все файлы из директории config/ в Cache.
     * @param string $path Путь к директории с конфигами.
     * @return void
     */
    public static function load(string $path): void
    {
        ConfigCore::load($path);
    }

    /** Получить значение конфигурации по ключу с точечной нотацией.
     * @param string $key Ключ вида 'file.section.param', например 'auth.token.lifetime'.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        return ConfigCore::get($key, $default);
    }
}
