<?php

namespace Tests\Unit\Queue\Drivers;

use App\Contracts\QueueDriver;
use PHPUnit\Framework\TestCase;
use App\Queue\Drivers\DatabaseDriver;

class DatabaseDriverTest extends TestCase
{
    public function test_implements_queue_driver(): void
    {
        $driver = new DatabaseDriver();
        $this->assertInstanceOf(QueueDriver::class, $driver);
    }
}

// Примечание: DatabaseDriver напрямую зависит от БД через фасад DB.
// Полноценное тестирование push/pop/release/fail/delete — в Feature тестах
// (QueueIntegrationTest), где есть реальная БД.
