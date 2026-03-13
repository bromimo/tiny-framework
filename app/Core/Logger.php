<?php

namespace App\Core;

/** Простой файловый логгер.
 * Путь к файлу логов берётся из `config/logging.php`.
 */
class Logger
{
    /** Вернуть путь к файлу лога для активного канала. */
    private static function logPath(): string
    {
        $channel = config('logging.default', 'single');
        $config  = config('logging.channels.' . $channel, []);
        $path    = $config['path'] ?? storage_path('logs/app.log');
        $driver  = $config['driver'] ?? 'single';

        if ($driver !== 'daily') {
            return $path;
        }

        $dir  = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $ext  = pathinfo($path, PATHINFO_EXTENSION);

        $daily = $dir . '/' . $base . '-' . date('Y-m-d') . ($ext !== '' ? '.' . $ext : '');

        self::rotate($dir, $base, $ext, (int)($config['days'] ?? 14));

        return $daily;
    }

    /** Удалить лог-файлы старше указанного количества дней.
     * @param string $dir  Директория с лог-файлами.
     * @param string $base Базовое имя файла (без расширения).
     * @param string $ext  Расширение файла.
     * @param int    $days Количество дней хранения.
     */
    private static function rotate(string $dir, string $base, string $ext, int $days): void
    {
        $pattern   = $dir . '/' . $base . '-*' . ($ext !== '' ? '.' . $ext : '');
        $files     = glob($pattern) ?: [];
        $threshold = strtotime("-{$days} days");

        foreach ($files as $file) {
            if (filemtime($file) < $threshold) {
                @unlink($file);
            }
        }
    }

    /** Записать сообщение с уровнем INFO. */
    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    /** Записать сообщение с уровнем ERROR. */
    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    /** Записать сообщение с уровнем DEBUG. */
    public static function debug(string $message): void
    {
        self::write('DEBUG', $message);
    }

    /** Записать строку в файл лога.
     * @param string $level Уровень логирования.
     * @param string $message Текст сообщения.
     */
    private static function write(string $level, string $message): void
    {
        $channel = config('logging.default', 'single');
        $config  = config('logging.channels.' . $channel, []);
        $path    = self::logPath();
        $dir     = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        if (($config['driver'] ?? 'single') === 'single') {
            $maxSize = (int)($config['max_size'] ?? 10 * 1024 * 1024);
            if (file_exists($path) && filesize($path) >= $maxSize) {
                self::rotateSingle($path, (int)($config['max_files'] ?? 5));
            }
        }

        $line = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }

    /** Сдвинуть архивные файлы и переименовать текущий лог.
     * app.4.log → app.5.log, ..., app.1.log → app.2.log, app.log → app.1.log
     * @param string $path      Путь к текущему лог-файлу.
     * @param int    $maxFiles  Максимальное количество архивных файлов.
     */
    private static function rotateSingle(string $path, int $maxFiles): void
    {
        $dir  = dirname($path);
        $base = pathinfo($path, PATHINFO_FILENAME);
        $ext  = pathinfo($path, PATHINFO_EXTENSION);
        $ext  = $ext !== '' ? '.' . $ext : '';

        // удалить самый старый файл если достигнут лимит
        $oldest = $dir . '/' . $base . '.' . $maxFiles . $ext;
        if (file_exists($oldest)) {
            @unlink($oldest);
        }

        // сдвинуть архивные файлы: app.3.log → app.4.log и т.д.
        for ($i = $maxFiles - 1; $i >= 1; $i--) {
            $from = $dir . '/' . $base . '.' . $i . $ext;
            $to   = $dir . '/' . $base . '.' . ($i + 1) . $ext;
            if (file_exists($from)) {
                rename($from, $to);
            }
        }

        // текущий файл → app.1.log
        rename($path, $dir . '/' . $base . '.1' . $ext);
    }
}
