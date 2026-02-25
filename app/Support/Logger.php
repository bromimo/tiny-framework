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
        $line = sprintf("[%s] [%s] %s%s", date('Y-m-d H:i:s'), $level, $message, PHP_EOL);
        file_put_contents(self::logPath(), $line, FILE_APPEND | LOCK_EX);
    }
}
