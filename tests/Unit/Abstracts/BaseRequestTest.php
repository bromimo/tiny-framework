<?php

namespace Tests\Unit\Abstracts;

use App\Abstracts\BaseDto;
use App\Abstracts\BaseRequest;
use App\DTOs\UserDto;
use App\Exceptions\ValidationException;
use PHPUnit\Framework\TestCase;
use TinyRouter\Http\Method;
use TinyRouter\Http\Request;

/** Конкретный реквест-заглушка для тестирования BaseRequest. */
class StubRequest extends BaseRequest
{
    protected function rules(): array
    {
        return [
            'name'  => ['required', 'max:50'],
            'email' => ['required', 'email'],
        ];
    }

    public function toDto(): BaseDto
    {
        return UserDto::from(array_merge(
            ['first_name' => '', 'last_name' => '', 'password' => null],
            $this->validated()
        ));
    }
}

class BaseRequestTest extends TestCase
{
    private function makeRequest(array $body = [], array $query = []): StubRequest
    {
        $req = new Request(Method::POST, '/', $query, $body, []);
        return new StubRequest($req);
    }

    public function test_validated_returns_only_declared_fields(): void
    {
        $req = $this->makeRequest(['name' => 'John', 'email' => 'john@example.com', 'extra' => 'ignored']);

        $data = (fn() => $this->validated())->call($req);

        $this->assertArrayHasKey('name', $data);
        $this->assertArrayHasKey('email', $data);
        $this->assertArrayNotHasKey('extra', $data);
    }

    public function test_validated_throws_on_invalid_data(): void
    {
        $req = $this->makeRequest(['name' => '', 'email' => 'john@example.com']);

        $this->expectException(ValidationException::class);
        (fn() => $this->validated())->call($req);
    }

    public function test_validated_merges_query_and_body(): void
    {
        $req = $this->makeRequest(
            body:  ['name' => 'John'],
            query: ['email' => 'john@example.com']
        );

        $data = (fn() => $this->validated())->call($req);

        $this->assertSame('John', $data['name']);
        $this->assertSame('john@example.com', $data['email']);
    }

    public function test_to_dto_throws_on_invalid_data(): void
    {
        $req = $this->makeRequest(['name' => 'John', 'email' => 'not-an-email']);

        $this->expectException(ValidationException::class);
        $req->toDto();
    }

    public function test_validation_exception_contains_errors(): void
    {
        $req = $this->makeRequest(['name' => '', 'email' => 'not-an-email']);

        try {
            $req->toDto();
            $this->fail('ValidationException not thrown');
        } catch (ValidationException $e) {
            $this->assertArrayHasKey('name', $e->getErrors());
        }
    }
}
