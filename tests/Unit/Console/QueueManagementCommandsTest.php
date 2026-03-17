<?php

namespace Tests\Unit\Console;

use App\Console\CommandInterface;
use PHPUnit\Framework\TestCase;
use App\Console\Commands\Queue\QueueFlushCommand;
use App\Console\Commands\Queue\QueueClearCommand;
use App\Console\Commands\Queue\QueueFailedCommand;
use App\Console\Commands\Queue\QueueRetryCommand;
use App\Console\Commands\Queue\QueueStatusCommand;
use App\Console\Commands\Queue\QueueRestartCommand;

class QueueManagementCommandsTest extends TestCase
{
    public function test_status_command(): void
    {
        $cmd = new QueueStatusCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:status', QueueStatusCommand::$name);
    }

    public function test_failed_command(): void
    {
        $cmd = new QueueFailedCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:failed', QueueFailedCommand::$name);
    }

    public function test_retry_command(): void
    {
        $cmd = new QueueRetryCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:retry', QueueRetryCommand::$name);
    }

    public function test_flush_command(): void
    {
        $cmd = new QueueFlushCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:flush', QueueFlushCommand::$name);
    }

    public function test_clear_command(): void
    {
        $cmd = new QueueClearCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:clear', QueueClearCommand::$name);
    }

    public function test_restart_command(): void
    {
        $cmd = new QueueRestartCommand();
        $this->assertInstanceOf(CommandInterface::class, $cmd);
        $this->assertSame('queue:restart', QueueRestartCommand::$name);
    }
}
