<?php

namespace Tests\Unit\Console;

use App\Console\Kernel;
use PHPUnit\Framework\TestCase;

class KernelTest extends TestCase
{
    public function test_unknown_command_outputs_error_and_exits(): void
    {
        $this->expectOutputRegex('/Unknown command/');

        try {
            (new Kernel())->handle(['run', 'nonexistent']);
        } catch (\Throwable) {
            // exit() бросает в тестах — перехватываем
        }
    }
}
