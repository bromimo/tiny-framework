<?php

namespace Tests\Unit\Listeners;

use App\Facades\Cache;
use App\Core\Logger;
use App\Events\LoginFailed;
use App\Events\LoginSucceeded;
use App\Listeners\LogAuthEvent;
use PHPUnit\Framework\TestCase;

class LogAuthEventTest extends TestCase
{
    private string $logFile;

    protected function setUp(): void
    {
        $this->logFile = sys_get_temp_dir() . '/test_auth_' . uniqid() . '.log';

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

    public function test_handles_login_succeeded(): void
    {
        $listener = new LogAuthEvent();
        $listener(new LoginSucceeded(42, '127.0.0.1'));

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('Login succeeded for user 42 from 127.0.0.1', $log);
    }

    public function test_handles_login_failed(): void
    {
        $listener = new LogAuthEvent();
        $listener(new LoginFailed('test@example.com', '10.0.0.1'));

        $log = file_get_contents($this->logFile);
        $this->assertStringContainsString('Login failed for email test@example.com from 10.0.0.1', $log);
    }
}
