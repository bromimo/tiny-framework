<?php

namespace App\Core;

use App\Facades\Cache;

/** Предоставляет доступ к конфигурации приложения через Cache с точечной нотацией.
 * Первый сегмент ключа соответствует имени файла в директории config/.
 */
class Config
{
    /** Загрузить все файлы из директории config/ в Cache.
     * @param string $path Путь к директории с конфигами.
     * @return void
     */
    public static function load(string $path): void
    {
        foreach (glob($path . '/*.php') as $file) {
            Cache::set('config.' . basename($file, '.php'), require $file);
        }
    }

    /** Получить значение конфигурации по ключу с точечной нотацией.
     * @param string $key Ключ вида 'file.section.param', например 'auth.token.lifetime'.
     * @param mixed $default Значение по умолчанию, если ключ не найден.
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $value    = Cache::get('config.' . array_shift($segments));

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        if ($value === null) {
            return $default;
        }

        return is_scalar($value) ? Env::cast($value) : $value;
    }
}
