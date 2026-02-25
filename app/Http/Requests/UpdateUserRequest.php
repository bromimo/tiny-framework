<?php

namespace App\Http\Requests;

use App\Abstracts\BaseRequest;
use App\DTOs\UserDto;

class UpdateUserRequest extends BaseRequest
{
    protected function rules(): array
    {
        return [
            'name'     => ['max:100'],
            'surname'  => ['max:100'],
            'email'    => ['email'],
            'password' => ['min:8'],
        ];
    }

    public function toDto(): UserDto
    {
        return new UserDto(
            name:     $this->input('name', ''),
            surname:  $this->input('surname', ''),
            email:    $this->input('email', ''),
            password: $this->input('password'),
        );
    }
}
