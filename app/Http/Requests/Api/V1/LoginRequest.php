<?php

namespace App\Http\Requests\Api\V1;

use App\DTOs\LoginDto;
use App\Abstracts\BaseRequest;
use App\Exceptions\ValidationException;

/** Запрос аутентификации. */
class LoginRequest extends BaseRequest
{
    /** Правила валидации.
     * @return array<string, list<string>>
     */
    protected function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    /** Валидировать данные и вернуть DTO аутентификации.
     * @return LoginDto
     * @throws ValidationException
     */
    public function toDto(): LoginDto
    {
        $data = $this->validated();

        return LoginDto::from($data);
    }
}
