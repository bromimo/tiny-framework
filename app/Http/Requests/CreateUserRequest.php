<?php

namespace App\Http\Requests;

use App\Abstracts\BaseRequest;
use App\DTOs\UserDto;

class CreateUserRequest extends BaseRequest
{
    protected function rules(): array
    {
        return [
            'name'     => ['required', 'max:100'],
            'surname'  => ['required', 'max:100'],
            'email'    => ['required', 'email'],
            'password' => ['required', 'min:8'],
        ];
    }

    public function toDto(): UserDto
    {
        return new UserDto(
            name:     $this->input('name'),
            surname:  $this->input('surname'),
            email:    $this->input('email'),
            password: $this->input('password'),
        );
    }
}
