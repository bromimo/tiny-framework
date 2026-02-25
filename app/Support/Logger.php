<?php

namespace App\Support;

class Logger
{
    private static function logPath(): string
    {
        return $_ENV['LOG_PATH'] ?? __DIR__ . '/../../storage/logs/app.log';
    }

    public static function info(string $message): void
    {
        self::write('INFO', $message);
    }

    public static function error(string $message): void
    {
        self::write('ERROR', $message);
    }

    public static function debug(string $message): void
    {
        self::write('DEBUG', $message);
    }

    private static function write(string $level, string $message): void
    {
        $path = self::logPath();
        $dir  = dirname($path);

        if (!is_dir($dir)) {
            mkdir($dir, 0755, true);
        }

        $line = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        file_put_contents($path, $line, FILE_APPEND | LOCK_EX);
    }
}
