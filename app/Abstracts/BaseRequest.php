<?php

namespace App\Abstracts;

use TinyRouter\Http\Request;
use App\Core\Validator;
use App\Exceptions\ValidationException;

/** Базовый класс HTTP-запроса: парсит тело и строку запроса, валидирует данные. */
abstract class BaseRequest
{
    protected array $data = [];

    /** Инициализировать данные из тела и строки запроса.
     * @param Request $req
     */
    public function __construct(Request $req)
    {
        $this->data = array_merge($req->query, $req->body);
    }

    /** Правила валидации запроса. Ключ — имя поля, значение — список правил.
     * @return array<string, list<string>>
     */
    abstract protected function rules(): array;

    /** Валидировать данные и вернуть DTO.
     * @return BaseDto
     * @throws ValidationException При наличии ошибок валидации.
     */
    abstract public function toDto(): BaseDto;

    /** Валидировать данные и вернуть только прошедшие валидацию поля.
     * @return array<string, mixed>
     * @throws ValidationException При наличии ошибок валидации.
     */
    protected function validated(): array
    {
        $errors = Validator::validate($this->data, $this->rules());
        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
        return array_intersect_key($this->data, $this->rules());
    }

    /** Получить значение из данных запроса.
     * @param string $key
     * @param mixed  $default
     * @return mixed
     */
    protected function input(string $key, mixed $default = null): mixed
    {
        return $this->data[$key] ?? $default;
    }
}
