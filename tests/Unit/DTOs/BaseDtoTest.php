<?php

namespace Tests\Unit\DTOs;

use App\DTOs\LoginDto;
use App\DTOs\UserDto;
use PHPUnit\Framework\TestCase;

class BaseDtoTest extends TestCase
{
    public function test_from_creates_dto_with_correct_values(): void
    {
        $dto = UserDto::from([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ]);

        $this->assertSame('John', $dto->first_name);
        $this->assertSame('Doe', $dto->last_name);
        $this->assertSame('john@example.com', $dto->email);
    }

    public function test_from_ignores_extra_keys(): void
    {
        $dto = UserDto::from([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'extra'      => 'ignored',
        ]);

        $this->assertSame('John', $dto->first_name);
    }

    public function test_from_sets_nullable_to_null_when_absent(): void
    {
        $dto = UserDto::from([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ]);

        $this->assertNull($dto->password);
    }

    public function test_from_sets_nullable_when_provided(): void
    {
        $dto = UserDto::from([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'password'   => 'secret',
        ]);

        $this->assertSame('secret', $dto->password);
    }

    public function test_to_array_excludes_null_properties(): void
    {
        $dto = UserDto::from([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ]);

        $this->assertArrayNotHasKey('password', $dto->toArray());
    }

    public function test_to_array_includes_all_non_null_properties(): void
    {
        $dto = new UserDto('John', 'Doe', 'john@example.com', 'secret');

        $this->assertSame([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
            'password'   => 'secret',
        ], $dto->toArray());
    }

    public function test_to_array_without_optional_property(): void
    {
        $dto = new UserDto('John', 'Doe', 'john@example.com');

        $this->assertSame([
            'first_name' => 'John',
            'last_name'  => 'Doe',
            'email'      => 'john@example.com',
        ], $dto->toArray());
    }

    public function test_login_dto_from_array(): void
    {
        $dto = LoginDto::from(['email' => 'user@example.com', 'password' => 'secret123']);

        $this->assertSame('user@example.com', $dto->email);
        $this->assertSame('secret123', $dto->password);
    }

    public function test_login_dto_from_ignores_extra_keys(): void
    {
        $dto = LoginDto::from([
            'email'    => 'user@example.com',
            'password' => 'secret123',
            'extra'    => 'ignored',
        ]);

        $this->assertSame('user@example.com', $dto->email);
    }
}
