<?php

namespace Tests\Unit\Core;

use App\Core\Env;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class EnvTest extends TestCase
{
    protected function tearDown(): void
    {
        unset($_ENV['_TEST_VAR']);
    }

    public function test_get_returns_null_by_default_when_key_missing(): void
    {
        $this->assertNull(Env::get('_NONEXISTENT'));
    }

    public function test_get_returns_default_when_key_missing(): void
    {
        $this->assertSame('fallback', Env::get('_NONEXISTENT', 'fallback'));
    }

    public function test_get_returns_cast_value(): void
    {
        $_ENV['_TEST_VAR'] = 'true';
        $this->assertTrue(Env::get('_TEST_VAR'));
    }

    #[DataProvider('trueCastProvider')]
    public function test_cast_true_variants(string $input): void
    {
        $this->assertTrue(Env::cast($input));
    }

    public static function trueCastProvider(): array
    {
        return [['true'], ['(true)'], ['TRUE'], ['True']];
    }

    #[DataProvider('falseCastProvider')]
    public function test_cast_false_variants(string $input): void
    {
        $this->assertFalse(Env::cast($input));
    }

    public static function falseCastProvider(): array
    {
        return [['false'], ['(false)'], ['FALSE'], ['False']];
    }

    #[DataProvider('nullCastProvider')]
    public function test_cast_null_variants(string $input): void
    {
        $this->assertNull(Env::cast($input));
    }

    public static function nullCastProvider(): array
    {
        return [['null'], ['(null)'], ['NULL']];
    }

    #[DataProvider('emptyCastProvider')]
    public function test_cast_empty_variants(string $input): void
    {
        $this->assertSame('', Env::cast($input));
    }

    public static function emptyCastProvider(): array
    {
        return [['empty'], ['(empty)']];
    }

    public function test_cast_strips_double_quotes(): void
    {
        $this->assertSame('hello world', Env::cast('"hello world"'));
    }

    public function test_cast_strips_single_quotes(): void
    {
        $this->assertSame('hello world', Env::cast("'hello world'"));
    }

    public function test_cast_returns_plain_string_unchanged(): void
    {
        $this->assertSame('some value', Env::cast('some value'));
    }

    public function test_cast_passes_through_non_string_values(): void
    {
        $this->assertTrue(Env::cast(true));
        $this->assertFalse(Env::cast(false));
        $this->assertNull(Env::cast(null));
        $this->assertSame(42, Env::cast(42));
        $this->assertSame(3.14, Env::cast(3.14));
    }
}
