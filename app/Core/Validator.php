<?php

namespace App\Core;

/** Валидатор входных данных по набору правил. */
class Validator
{
    /** Проверить данные по правилам и вернуть массив ошибок.
     * @param array<string, mixed> $data Входные данные.
     * @param array<string, list<string>> $rules Правила вида ['field' => ['required', 'min:3']].
     * @return array<string, string> Массив ошибок вида ['field' => 'сообщение'].
     */
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $error = self::applyRule($field, $data[$field] ?? null, $rule, $data);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    /** Проверить наличие значения в таблице БД.
     * @param string $param  Параметр правила: 'table' или 'table,column'.
     * @param string $field  Имя поля (используется как колонка если column не указана).
     * @param mixed  $value  Проверяемое значение.
     * @return bool True если запись найдена.
     */
    private static function existsInTable(string $param, string $field, mixed $value): bool
    {
        [$table, $column] = str_contains($param, ',')
            ? explode(',', $param, 2)
            : [$param, $field];

        return q1("SELECT 1 FROM {$table} WHERE {$column} = ? LIMIT 1", [$value]) !== null;
    }

    /** Применить одно правило к значению поля.
     * @param string               $field Имя поля.
     * @param mixed                $value Значение поля.
     * @param string               $rule  Правило (например 'required', 'min:3').
     * @param array<string, mixed> $data  Все данные запроса (для правил, требующих контекста).
     * @return string|null Сообщение об ошибке или null если значение прошло проверку.
     */
    private static function applyRule(string $field, mixed $value, string $rule, array $data): ?string
    {
        [$ruleName, $param] = str_contains($rule, ':')
            ? explode(':', $rule, 2)
            : [$rule, null];

        $isEmpty = $value === null || $value === '';

        return match ($ruleName) {
            'required' => $isEmpty
                ? "The {$field} field is required."
                : null,

            'email' => (!$isEmpty && !filter_var($value, FILTER_VALIDATE_EMAIL))
                ? "The {$field} must be a valid email address."
                : null,

            'min' => (!$isEmpty && strlen((string) $value) < (int) $param)
                ? "The {$field} must be at least {$param} characters."
                : null,

            'max' => (!$isEmpty && strlen((string) $value) > (int) $param)
                ? "The {$field} must not exceed {$param} characters."
                : null,

            'unique' => (!$isEmpty && self::existsInTable($param ?? '', $field, $value))
                ? "The {$field} has already been taken."
                : null,

            'confirmed' => ($value !== ($data["{$field}_confirmation"] ?? null))
                ? "The {$field} confirmation does not match."
                : null,

            default => null,
        };
    }
}
