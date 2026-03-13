<?php

namespace Tests\Feature\Models;

use App\DTOs\UserDto;
use App\Models\User;
use App\Exceptions\QueryException;
use Tests\Support\FeatureTestCase;

/** Интеграционные тесты модели User. */
class UserModelTest extends FeatureTestCase
{
    /** Вспомогательный метод для создания UserDto с заданными параметрами.
     * @param string $email
     * @param string $password
     * @return UserDto
     */
    private function makeDto(string $email = 'john@example.com', string $password = 'secret123'): UserDto
    {
        return new UserDto(
            first_name: 'John',
            last_name: 'Doe',
            email: $email,
            password: $password,
        );
    }

    /** Создание пользователя через DTO возвращает User с захешированным паролем. */
    public function test_create_returns_user_with_hashed_password(): void
    {
        $dto  = $this->makeDto();
        $user = User::create($dto);

        $this->assertInstanceOf(User::class, $user);
        $this->assertSame('John', $user->first_name);
        $this->assertSame('Doe', $user->last_name);
        $this->assertSame('john@example.com', $user->email);
        $this->assertNotEmpty($user->password);
        $this->assertTrue(password_verify('secret123', $user->password));
    }

    /** Попытка создать пользователя с дублирующимся email выбрасывает QueryException. */
    public function test_create_duplicate_email_throws_query_exception(): void
    {
        $this->expectException(QueryException::class);

        User::create($this->makeDto());
        User::create($this->makeDto());
    }

    /** Поиск пользователя по email возвращает корректную запись. */
    public function test_find_by_email_returns_user(): void
    {
        User::create($this->makeDto());

        $found = User::findByEmail('john@example.com');

        $this->assertInstanceOf(User::class, $found);
        $this->assertSame('john@example.com', $found->email);
    }

    /** Поиск пользователя по несуществующему email возвращает null. */
    public function test_find_by_email_returns_null_when_not_found(): void
    {
        $result = User::findByEmail('nobody@example.com');

        $this->assertNull($result);
    }

    /** Обновление пользователя меняет указанные поля, незаполненные — не перезаписывает. */
    public function test_update_changes_fields(): void
    {
        $user    = User::create($this->makeDto());
        $updateDto = new UserDto(
            first_name: 'Jane',
            last_name: 'Smith',
            email: 'john@example.com',
            password: null,
        );

        $updated = User::update($user->id, $updateDto);

        $this->assertInstanceOf(User::class, $updated);
        $this->assertSame('Jane', $updated->first_name);
        $this->assertSame('Smith', $updated->last_name);
        $this->assertSame('john@example.com', $updated->email);
    }

    /** Обновление пользователя с новым паролем хеширует его. */
    public function test_update_password_hashes_new_value(): void
    {
        $user      = User::create($this->makeDto());
        $updateDto = new UserDto(
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            password: 'newpassword456',
        );

        $updated = User::update($user->id, $updateDto);

        $this->assertNotEmpty($updated->password);
        $this->assertTrue(password_verify('newpassword456', $updated->password));
        $this->assertFalse(password_verify('secret123', $updated->password));
    }

    /** Обновление пользователя без пароля сохраняет старый хеш. */
    public function test_update_null_password_keeps_old_hash(): void
    {
        $user      = User::create($this->makeDto());
        $oldHash   = $user->password;
        $updateDto = new UserDto(
            first_name: 'John',
            last_name: 'Doe',
            email: 'john@example.com',
            password: null,
        );

        $updated = User::update($user->id, $updateDto);

        $this->assertSame($oldHash, $updated->password);
        $this->assertTrue(password_verify('secret123', $updated->password));
    }

    /** Удаление пользователя по ID приводит к тому, что findById возвращает null. */
    public function test_delete_removes_user(): void
    {
        $user = User::create($this->makeDto());
        $id   = $user->id;

        $result = User::deleteById($id);

        $this->assertTrue($result);
        $this->assertNull(User::findById($id));
    }

    /** jsonSerialize() не содержит поля 'password'. */
    public function test_json_serialize_hides_password(): void
    {
        $user       = User::create($this->makeDto());
        $serialized = $user->jsonSerialize();

        $this->assertArrayNotHasKey('password', $serialized);
        $this->assertArrayHasKey('email', $serialized);
    }
}
