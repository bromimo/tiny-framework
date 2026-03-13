<?php

namespace App\Abstracts;

use App\Console\CommandInterface;

/** Базовый класс для консольных команд. Предоставляет константы цветов ANSI. */
abstract class BaseCommand implements CommandInterface
{
    protected const RESET  = "\033[0m";
    protected const BOLD   = "\033[1m";
    protected const WHITE  = "\033[97m";
    protected const GREEN  = "\033[32m";
    protected const YELLOW = "\033[33m";
    protected const GRAY   = "\033[90m";
    protected const RED    = "\033[31m";
}
