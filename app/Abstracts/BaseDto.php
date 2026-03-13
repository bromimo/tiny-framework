<?php

namespace App\Abstracts;

abstract readonly class BaseDto
{
    /** Создать DTO из ассоциативного массива. Лишние ключи игнорируются.
     * @param array<string, mixed> $data Массив с ключами, совпадающими с параметрами конструктора.
     * @return static
     */
    public static function from(array $data): static
    {
        $params = (new \ReflectionClass(static::class))->getConstructor()->getParameters();
        $keys   = array_map(fn($p) => $p->getName(), $params);

        return new static(...array_intersect_key($data, array_flip($keys)));
    }

    /** Вернуть публичные свойства объекта в виде массива, исключая null-значения. */
    public function toArray(): array
    {
        return array_filter(get_object_vars($this), fn($v) => $v !== null);
    }
}
