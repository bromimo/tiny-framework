<?php

namespace App\DTOs;

use App\Abstracts\BaseDto;

readonly class UserDto extends BaseDto
{
    public function __construct(
        public string  $name,
        public string  $surname,
        public string  $email,
        public ?string $password = null,
    ) {}

    public function toArray(): array
    {
        return array_filter([
            'name'     => $this->name,
            'surname'  => $this->surname,
            'email'    => $this->email,
            'password' => $this->password,
        ], fn($v) => $v !== null);
    }
}
