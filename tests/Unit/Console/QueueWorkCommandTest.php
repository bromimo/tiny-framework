<?php

namespace Tests\Unit\Console;

use App\Console\CommandInterface;
use PHPUnit\Framework\TestCase;
use App\Console\Commands\Queue\QueueWorkCommand;

class QueueWorkCommandTest extends TestCase
{
    public function test_implements_command_interface(): void
    {
        $this->assertInstanceOf(CommandInterface::class, new QueueWorkCommand());
    }

    public function test_has_static_name(): void
    {
        $this->assertSame('queue:work', QueueWorkCommand::$name);
    }

    public function test_has_description(): void
    {
        $command = new QueueWorkCommand();
        $this->assertNotEmpty($command->description());
    }
}
