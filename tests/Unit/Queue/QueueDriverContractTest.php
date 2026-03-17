<?php

namespace Tests\Unit\Queue;

use App\Contracts\QueueDriver;
use PHPUnit\Framework\TestCase;

class QueueDriverContractTest extends TestCase
{
    public function test_interface_defines_required_methods(): void
    {
        $reflection = new \ReflectionClass(QueueDriver::class);

        $this->assertTrue($reflection->isInterface());
        $this->assertTrue($reflection->hasMethod('push'));
        $this->assertTrue($reflection->hasMethod('pop'));
        $this->assertTrue($reflection->hasMethod('delete'));
        $this->assertTrue($reflection->hasMethod('release'));
        $this->assertTrue($reflection->hasMethod('fail'));
        $this->assertTrue($reflection->hasMethod('size'));
    }
}
