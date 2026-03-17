<?php

namespace Tests\Unit\Queue;

use PHPUnit\Framework\TestCase;

class QueueConfigTest extends TestCase
{
    public function test_queue_config_has_required_keys(): void
    {
        $config = require __DIR__ . '/../../../config/queue.php';

        $this->assertArrayHasKey('default', $config);
        $this->assertArrayHasKey('timeout', $config);
        $this->assertArrayHasKey('tries', $config);
        $this->assertArrayHasKey('backoff', $config);
        $this->assertArrayHasKey('queues', $config);
    }

    public function test_queue_config_has_valid_defaults(): void
    {
        $config = require __DIR__ . '/../../../config/queue.php';

        $this->assertIsString($config['default']);
        $this->assertIsInt($config['timeout']);
        $this->assertIsInt($config['tries']);
        $this->assertIsArray($config['backoff']);
        $this->assertIsArray($config['queues']);
        $this->assertGreaterThan(0, $config['timeout']);
        $this->assertGreaterThan(0, $config['tries']);
    }

    public function test_queue_config_queues_have_workers(): void
    {
        $config = require __DIR__ . '/../../../config/queue.php';

        foreach ($config['queues'] as $name => $queueConfig) {
            $this->assertArrayHasKey('workers', $queueConfig, "Queue '{$name}' missing 'workers' key");
            $this->assertIsInt($queueConfig['workers']);
        }
    }
}
