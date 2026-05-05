<?php

declare(strict_types=1);

namespace App\Services;

final class Validator
{
    /**
     * @param array<string, mixed> $input
     * @param array<string, string> $rules
     * @return array{valid: bool, errors: array<string, array<int, string>>, data: array<string, mixed>}
     */
    public static function validate(array $input, array $rules): array
    {
        $errors = [];
        $data = [];

        foreach ($rules as $field => $ruleLine) {
            $value = $input[$field] ?? null;
            $fieldRules = explode('|', $ruleLine);

            foreach ($fieldRules as $rule) {
                if ($rule === 'required') {
                    if ($value === null || $value === '') {
                        $errors[$field][] = sprintf('%s is required', $field);
                        continue;
                    }
                }

                if ($rule === 'string' && $value !== null && !is_string($value)) {
                    $errors[$field][] = sprintf('%s must be a string', $field);
                    continue;
                }

                if (str_starts_with($rule, 'max:') && is_string($value)) {
                    $max = (int) substr($rule, 4);
                    if (mb_strlen($value) > $max) {
                        $errors[$field][] = sprintf('%s must not be greater than %d characters', $field, $max);
                    }
                }

                if ($rule === 'email' && $value !== null && filter_var($value, FILTER_VALIDATE_EMAIL) === false) {
                    $errors[$field][] = sprintf('%s must be a valid email address', $field);
                }
            }

            if (!array_key_exists($field, $errors) && $value !== null) {
                $data[$field] = $value;
            }
        }

        return [
            'valid' => $errors === [],
            'errors' => $errors,
            'data' => $data,
        ];
    }
}
