<?php

namespace App\Http\Requests;

use App\Abstracts\BaseRequest;
use App\DTOs\LoginDto;

class LoginRequest extends BaseRequest
{
    protected function rules(): array
    {
        return [
            'email'    => ['required', 'email'],
            'password' => ['required'],
        ];
    }

    public function toDto(): LoginDto
    {
        return new LoginDto(
            email:    $this->input('email'),
            password: $this->input('password'),
        );
    }
}
