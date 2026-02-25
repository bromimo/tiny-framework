<?php

namespace App\Support;

class Validator
{
    public static function validate(array $data, array $rules): array
    {
        $errors = [];

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                $error = self::applyRule($field, $data[$field] ?? null, $rule);
                if ($error !== null) {
                    $errors[$field] = $error;
                    break;
                }
            }
        }

        return $errors;
    }

    private static function applyRule(string $field, mixed $value, string $rule): ?string
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

            default => null,
        };
    }
}
