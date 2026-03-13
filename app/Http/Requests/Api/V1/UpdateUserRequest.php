<?php

namespace App\Http\Requests\Api\V1;

use App\DTOs\UserDto;
use App\Abstracts\BaseRequest;
use App\Exceptions\ValidationException;

/** Запрос обновления пользователя. */
class UpdateUserRequest extends BaseRequest
{
    /** Правила валидации.
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['max:100'],
            'last_name'  => ['max:100'],
            'email'    => ['email'],
            'password' => ['min:8'],
        ];
    }

    /** Валидировать данные и вернуть DTO пользователя.
     * @return UserDto
     * @throws ValidationException
     */
    public function toDto(): UserDto
    {
        $data = $this->validated();

        return new UserDto(
            first_name: $data['first_name'] ?? '',
            last_name:  $data['last_name']  ?? '',
            email:      $data['email']      ?? '',
            password:   $data['password']   ?? null,
        );
    }
}
