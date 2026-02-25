<?php

namespace App\Abstracts;

use App\Support\Validator;

abstract class BaseRequest
{
    protected array $data = [];

    public function __construct()
    {
        $body       = json_decode(file_get_contents('php://input'), true) ?? [];
        $this->data = array_merge($_GET, $body);
    }

    abstract protected function rules(): array;

    abstract public function toDto(): BaseDto;

    public function validate(): array
    {
        return Validator::validate($this->data, $this->rules());
    }

    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
