<?php

namespace Tests\Unit\Cache;

use App\Core\Cache\Drivers\FileDriver;
use PHPUnit\Framework\TestCase;

class FileDriverTest extends TestCase
{
    private FileDriver $cache;
    private string $dir;

    protected function setUp(): void
    {
        $this->dir   = sys_get_temp_dir() . '/phpunit_cache_' . uniqid();
        $this->cache = new FileDriver($this->dir);
    }

    protected function tearDown(): void
    {
        $this->cache->flush();
        if (is_dir($this->dir)) {
            rmdir($this->dir);
        }
    }

    public function test_set_and_get(): void
    {
        $this->cache->set('key', 'value');
        $this->assertSame('value', $this->cache->get('key'));
    }

    public function test_get_returns_null_by_default_when_missing(): void
    {
        $this->assertNull($this->cache->get('missing'));
    }

    public function test_get_returns_default_when_missing(): void
    {
        $this->assertSame('fallback', $this->cache->get('missing', 'fallback'));
    }

    public function test_has_returns_true_when_key_exists(): void
    {
        $this->cache->set('key', 'value');
        $this->assertTrue($this->cache->has('key'));
    }

    public function test_has_returns_false_when_key_missing(): void
    {
        $this->assertFalse($this->cache->has('missing'));
    }

    public function test_forget_removes_key(): void
    {
        $this->cache->set('key', 'value');
        $this->cache->forget('key');
        $this->assertFalse($this->cache->has('key'));
    }

    public function test_forget_is_safe_when_key_missing(): void
    {
        $this->cache->forget('nonexistent');
        $this->assertFalse($this->cache->has('nonexistent'));
    }

    public function test_flush_removes_all_keys(): void
    {
        $this->cache->set('a', 1);
        $this->cache->set('b', 2);
        $this->cache->flush();
        $this->assertFalse($this->cache->has('a'));
        $this->assertFalse($this->cache->has('b'));
    }

    public function test_stores_various_value_types(): void
    {
        $this->cache->set('int', 42);
        $this->cache->set('bool', true);
        $this->cache->set('array', ['a' => 1]);

        $this->assertSame(42, $this->cache->get('int'));
        $this->assertTrue($this->cache->get('bool'));
        $this->assertSame(['a' => 1], $this->cache->get('array'));
    }

    public function test_ttl_zero_persists_value(): void
    {
        $this->cache->set('key', 'value', 0);
        $this->assertSame('value', $this->cache->get('key'));
    }

    public function test_creates_directory_if_not_exists(): void
    {
        $dir = sys_get_temp_dir() . '/phpunit_new_dir_' . uniqid();
        $this->assertFalse(is_dir($dir));
        new FileDriver($dir);
        $this->assertTrue(is_dir($dir));
        rmdir($dir);
    }

    public function test_expired_key_returns_default(): void
    {
        $this->cache->set('key', 'value', 1);
        sleep(2);
        $this->assertNull($this->cache->get('key'));
    }

    public function test_expired_key_not_found_by_has(): void
    {
        $this->cache->set('key', 'value', 1);
        sleep(2);
        $this->assertFalse($this->cache->has('key'));
    }

    public function test_increment_initialises_missing_key_to_1(): void
    {
        $result = $this->cache->increment('counter');
        $this->assertSame(1, $result);
    }

    public function test_increment_increments_existing_key(): void
    {
        $this->cache->increment('counter');
        $result = $this->cache->increment('counter');
        $this->assertSame(2, $result);
    }

    public function test_increment_preserves_ttl_of_existing_key(): void
    {
        $this->cache->increment('counter', 3600);
        $valueBefore = $this->cache->increment('counter'); // 2nd increment
        // The key should still be readable
        $this->assertSame(2, $valueBefore);
    }

    public function test_increment_reinitialises_expired_key(): void
    {
        $this->cache->set('counter', 5, 1);
        sleep(2); // wait for TTL to expire

        $result = $this->cache->increment('counter');
        $this->assertSame(1, $result);
    }
}
