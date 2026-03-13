<?php

namespace Tests\Unit\Cache;

use App\Core\Cache\Drivers\ArrayDriver;
use PHPUnit\Framework\TestCase;

class ArrayDriverTest extends TestCase
{
    private ArrayDriver $cache;

    protected function setUp(): void
    {
        $this->cache = new ArrayDriver();
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

    public function test_has_detects_null_value(): void
    {
        $this->cache->set('key', null);
        $this->assertTrue($this->cache->has('key'));
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

    public function test_set_with_positive_ttl_stores_value(): void
    {
        $this->cache->set('key', 'value', 1);
        $this->assertSame('value', $this->cache->get('key'));
    }

    public function test_set_with_ttl_value_is_accessible_before_expiry(): void
    {
        $this->cache->set('key', 'value', 3600);
        $this->assertSame('value', $this->cache->get('key'));
    }

    public function test_has_returns_false_for_expired_key(): void
    {
        // Use internal reflection to force an expired entry
        $reflection = new \ReflectionProperty($this->cache, 'store');
        $reflection->setValue($this->cache, [
            'key' => ['value' => 'expired', 'expires_at' => time() - 1],
        ]);

        $this->assertFalse($this->cache->has('key'));
    }

    public function test_get_returns_null_for_expired_key(): void
    {
        $reflection = new \ReflectionProperty($this->cache, 'store');
        $reflection->setValue($this->cache, [
            'key' => ['value' => 'expired', 'expires_at' => time() - 1],
        ]);

        $this->assertNull($this->cache->get('key'));
    }

    public function test_set_with_zero_ttl_never_expires(): void
    {
        $this->cache->set('permanent', 'value', 0);
        $this->assertSame('value', $this->cache->get('permanent'));
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
        // Set a key with short TTL via increment
        $this->cache->increment('counter', 3600);

        // Read raw store to verify expires_at was set
        $reflection = new \ReflectionProperty($this->cache, 'store');
        $store = $reflection->getValue($this->cache);
        $expiresAt = $store['counter']['expires_at'];

        // Increment again — should NOT reset expires_at
        $this->cache->increment('counter');

        $store = $reflection->getValue($this->cache);
        $this->assertSame($expiresAt, $store['counter']['expires_at']);
    }

    public function test_increment_reinitialises_expired_key(): void
    {
        // Force an expired entry
        $reflection = new \ReflectionProperty($this->cache, 'store');
        $reflection->setValue($this->cache, [
            'counter' => ['value' => 5, 'expires_at' => time() - 1],
        ]);

        $result = $this->cache->increment('counter');
        $this->assertSame(1, $result);
    }
}
