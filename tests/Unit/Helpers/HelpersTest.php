<?php

namespace Tests\Unit\Helpers;

use PHPUnit\Framework\TestCase;

class HelpersTest extends TestCase
{
    protected function tearDown(): void
    {
        unset(
            $_ENV['_TEST_VAR'],
            $_SERVER['HTTP_AUTHORIZATION'],
            $_SERVER['REDIRECT_HTTP_AUTHORIZATION'],
        );
    }

    public function test_env_returns_cast_value(): void
    {
        $_ENV['_TEST_VAR'] = 'true';
        $this->assertTrue(env('_TEST_VAR'));
    }

    public function test_env_returns_null_by_default_when_missing(): void
    {
        $this->assertNull(env('_NONEXISTENT'));
    }

    public function test_env_returns_default_when_missing(): void
    {
        $this->assertSame('default', env('_NONEXISTENT', 'default'));
    }

    public function test_get_bearer_token_returns_token(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer abc123token';
        $this->assertSame('abc123token', getBearerToken());
    }

    public function test_get_bearer_token_trims_whitespace(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Bearer  mytoken  ';
        $this->assertSame('mytoken', getBearerToken());
    }

    public function test_get_bearer_token_returns_null_when_no_header(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION'], $_SERVER['REDIRECT_HTTP_AUTHORIZATION']);
        $this->assertNull(getBearerToken());
    }

    public function test_get_bearer_token_returns_null_for_non_bearer(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'Basic dXNlcjpwYXNz';
        $this->assertNull(getBearerToken());
    }

    public function test_get_bearer_token_is_case_insensitive(): void
    {
        $_SERVER['HTTP_AUTHORIZATION'] = 'BEARER mytoken';
        $this->assertSame('mytoken', getBearerToken());
    }

    public function test_get_bearer_token_reads_redirect_header(): void
    {
        unset($_SERVER['HTTP_AUTHORIZATION']);
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer redirecttoken';
        $this->assertSame('redirecttoken', getBearerToken());
    }

    public function test_get_bearer_token_prefers_http_authorization(): void
    {
        $_SERVER['HTTP_AUTHORIZATION']          = 'Bearer primary';
        $_SERVER['REDIRECT_HTTP_AUTHORIZATION'] = 'Bearer secondary';
        $this->assertSame('primary', getBearerToken());
    }
}
