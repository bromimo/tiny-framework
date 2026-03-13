<?php

namespace Tests\Unit\Core;

use App\Core\Validator;
use PHPUnit\Framework\TestCase;

class ValidatorTest extends TestCase
{
    public function test_required_rule_fails_on_empty(): void
    {
        $errors = Validator::validate(['name' => ''], ['name' => ['required']]);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_required_rule_fails_on_null(): void
    {
        $errors = Validator::validate([], ['name' => ['required']]);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_required_rule_passes_on_value(): void
    {
        $errors = Validator::validate(['name' => 'John'], ['name' => ['required']]);
        $this->assertArrayNotHasKey('name', $errors);
    }

    public function test_email_rule_fails_on_invalid(): void
    {
        $errors = Validator::validate(['email' => 'not-an-email'], ['email' => ['email']]);
        $this->assertArrayHasKey('email', $errors);
    }

    public function test_email_rule_passes_on_valid(): void
    {
        $errors = Validator::validate(['email' => 'user@example.com'], ['email' => ['email']]);
        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_email_rule_skips_empty_value(): void
    {
        $errors = Validator::validate(['email' => ''], ['email' => ['email']]);
        $this->assertArrayNotHasKey('email', $errors);
    }

    public function test_min_rule_fails_when_too_short(): void
    {
        $errors = Validator::validate(['password' => '1234567'], ['password' => ['min:8']]);
        $this->assertArrayHasKey('password', $errors);
    }

    public function test_min_rule_passes_when_long_enough(): void
    {
        $errors = Validator::validate(['password' => '12345678'], ['password' => ['min:8']]);
        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_min_rule_skips_empty_value(): void
    {
        $errors = Validator::validate(['password' => ''], ['password' => ['min:8']]);
        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_max_rule_fails_when_too_long(): void
    {
        $errors = Validator::validate(['name' => str_repeat('a', 101)], ['name' => ['max:100']]);
        $this->assertArrayHasKey('name', $errors);
    }

    public function test_max_rule_passes_when_within_limit(): void
    {
        $errors = Validator::validate(['name' => 'John'], ['name' => ['max:100']]);
        $this->assertArrayNotHasKey('name', $errors);
    }

    public function test_confirmed_rule_passes_when_match(): void
    {
        $errors = Validator::validate(
            ['password' => 'secret123', 'password_confirmation' => 'secret123'],
            ['password' => ['confirmed']]
        );
        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_confirmed_rule_fails_when_mismatch(): void
    {
        $errors = Validator::validate(
            ['password' => 'secret123', 'password_confirmation' => 'wrong'],
            ['password' => ['confirmed']]
        );
        $this->assertArrayHasKey('password', $errors);
    }

    public function test_confirmed_rule_fails_when_confirmation_absent(): void
    {
        $errors = Validator::validate(
            ['password' => 'secret123'],
            ['password' => ['confirmed']]
        );
        $this->assertArrayHasKey('password', $errors);
    }

    public function test_confirmed_rule_passes_on_empty_value(): void
    {
        $errors = Validator::validate(
            ['password' => '', 'password_confirmation' => ''],
            ['password' => ['confirmed']]
        );
        $this->assertArrayNotHasKey('password', $errors);
    }

    public function test_stops_at_first_error_per_field(): void
    {
        $errors = Validator::validate(
            ['email' => ''],
            ['email' => ['required', 'email']]
        );
        $this->assertArrayHasKey('email', $errors);
        $this->assertStringContainsString('required', strtolower($errors['email']));
    }

    public function test_returns_empty_array_when_all_valid(): void
    {
        $errors = Validator::validate(
            ['name' => 'John', 'email' => 'john@example.com', 'password' => 'secret123'],
            ['name' => ['required', 'max:100'], 'email' => ['required', 'email'], 'password' => ['required', 'min:8']]
        );
        $this->assertEmpty($errors);
    }

    public function test_unknown_rule_is_ignored(): void
    {
        $errors = Validator::validate(['name' => 'John'], ['name' => ['unknown_rule']]);
        $this->assertEmpty($errors);
    }
}
