<?php

namespace Tests\Unit\Core;

use App\Core\Config;
use App\Facades\Cache;
use PHPUnit\Framework\TestCase;

class ConfigTest extends TestCase
{
    protected function setUp(): void
    {
        Cache::flush();
        Cache::set('config.app', [
            'name'  => 'TestApp',
            'debug' => true,
        ]);
        Cache::set('config.auth', [
            'token'    => ['lifetime' => '+28 days'],
            'password' => ['algo' => 'bcrypt', 'options' => []],
        ]);
    }

    protected function tearDown(): void
    {
        Cache::flush();
    }

    public function test_get_top_level_value(): void
    {
        $this->assertSame('TestApp', Config::get('app.name'));
    }

    public function test_get_nested_value(): void
    {
        $this->assertSame('+28 days', Config::get('auth.token.lifetime'));
    }

    public function test_get_section_returns_array(): void
    {
        $this->assertSame(['lifetime' => '+28 days'], Config::get('auth.token'));
    }

    public function test_get_returns_null_when_key_missing(): void
    {
        $this->assertNull(Config::get('auth.nonexistent'));
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('fallback', Config::get('auth.nonexistent', 'fallback'));
    }

    public function test_get_returns_null_when_file_not_loaded(): void
    {
        $this->assertNull(Config::get('nonexistent.key'));
    }

    public function test_get_returns_default_when_path_too_deep(): void
    {
        $this->assertNull(Config::get('auth.token.lifetime.extra'));
    }

    public function test_get_casts_string_true_to_bool(): void
    {
        Cache::set('config.test', ['flag' => 'true']);
        $this->assertTrue(Config::get('test.flag'));
    }
}
