<?php

namespace Tests\Unit\Console;

use App\Console\CommandInterface;
use PHPUnit\Framework\TestCase;
use App\Console\Commands\Queue\QueueStartCommand;

class QueueStartCommandTest extends TestCase
{
    public function test_implements_command_interface(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new QueueStartCommand());
    }

    public function test_has_static_name(): void
    {
        $this->assertSame('queue:start', QueueStartCommand::$name);
    }

    public function test_has_description(): void
    {
        $command = new QueueStartCommand();
        $this->assertNotEmpty($command->description());
    }
}
