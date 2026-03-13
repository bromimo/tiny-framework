<?php

namespace App\DTOs;

use App\Abstracts\BaseDto;

readonly class LoginDto extends BaseDto
{
    public function __construct(
        public string $email,
        public string $password,
    ) {}


}
