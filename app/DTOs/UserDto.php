<?php

namespace App\DTOs;

use App\Abstracts\BaseDto;

readonly class UserDto extends BaseDto
{
    public function __construct(
        public string  $first_name,
        public string  $last_name,
        public string  $email,
        public ?string $password = null,
    ) {}
}
