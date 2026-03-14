<?php

namespace Tests\Unit\Listeners;

use App\Facades\Cache;
use App\Core\Logger;
use App\Events\UserCreated;
use App\Events\UserDeleted;
use App\Events\UserUpdated;
use App\Listeners\LogUserChange;
use PHPUnit\Framework\TestCase;

class LogUserChangeTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/test_user_' . uniqid() . '.log';

        Cache::set('config.logging', [
            'default'  => 'single',
            'channels' => [
                'single' => [
                    'driver' => 'single',
                    'path'   => $this->logFile,
                ],
            ],
        ]);
    }

    protected function tearDown(): void
    {
        if (file_exists($this->logFile)) {
            @unlink($this->logFile);
        }
    }

    public function test_handles_user_created(): void
    {
        $listener = new LogUserChange();
        $listener(new UserCreated(1, 'user@example.com'));

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('User created: 1 (user@example.com)', $log);
    }

    public function test_handles_user_updated(): void
    {
        $listener = new LogUserChange();
        $listener(new UserUpdated(5, ['email', 'first_name']));

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('User updated: 5, fields: email, first_name', $log);
    }

    public function test_handles_user_deleted(): void
    {
        $listener = new LogUserChange();
        $listener(new UserDeleted(10));

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('User deleted: 10', $log);
    }
}
