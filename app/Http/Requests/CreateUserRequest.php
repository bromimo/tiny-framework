<?php

namespace App\Http\Requests;

use App\DTOs\UserDto;
use App\Abstracts\BaseRequest;
use App\Exceptions\ValidationException;

/** Запрос создания пользователя. */
class CreateUserRequest extends BaseRequest
{
    /** Правила валидации.
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'first_name' => ['required', 'max:100'],
            'last_name'  => ['required', 'max:100'],
            'email'      => ['required', 'email', 'unique:users'],
            'password'   => ['required', 'min:8', 'confirmed'],
        ];
    }

    /** Валидировать данные и вернуть DTO пользователя.
     * @return UserDto
     * @throws ValidationException
     */
    public function toDto(): UserDto
    {
        $data = $this->validated();

        return UserDto::from($data);
    }
}
