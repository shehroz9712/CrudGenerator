<?php

namespace Shehroz\CrudGenerator\Parsers;

use Illuminate\Support\Str;

class FieldParser
{
    public function parse(?string $fieldsOption): array
    {
        if (! $fieldsOption) {
            return [];
        }

        $fields = [];

        foreach (explode(',', $fieldsOption) as $part) {
            $part = trim($part);
            if ($part === '') {
                continue;
            }

            $segments = explode(':', $part);
            $name = $segments[0];
            $type = $segments[1] ?? 'string';
            $modifiers = $segments[2] ?? '';

            $fields[] = [
                'name' => $name,
                'camel' => Str::camel($name),
                'title' => Str::title(str_replace('_', ' ', $name)),
                'type' => $this->normalizeMigrationType($type),
                'input_type' => $this->getInputType($type),
                'rules' => $this->getValidationRules($type, $modifiers, $name === 'email'),
                'nullable' => str_contains($modifiers, 'nullable'),
                'unique' => str_contains($modifiers, 'unique'),
            ];
        }

        return $fields;
    }

    protected function normalizeMigrationType(string $type): string
    {
        return match (strtolower($type)) {
            'text' => 'text',
            'boolean', 'bool' => 'boolean',
            'integer', 'int' => 'integer',
            'biginteger', 'bigint' => 'bigInteger',
            'decimal', 'float' => 'decimal',
            'date' => 'date',
            'datetime', 'timestamp' => 'dateTime',
            'json' => 'json',
            'email', 'string' => 'string',
            default => 'string',
        };
    }

    protected function getInputType(string $type): string
    {
        return match (strtolower($type)) {
            'text' => 'textarea',
            'boolean', 'bool' => 'checkbox',
            'date', 'datetime', 'timestamp' => 'date',
            'email' => 'email',
            'file' => 'file',
            'integer', 'int', 'biginteger', 'bigint' => 'number',
            default => 'text',
        };
    }

    protected function getValidationRules(string $type, string $modifiers, bool $isEmailField): string
    {
        $rules = [];

        if (! str_contains($modifiers, 'nullable')) {
            $rules[] = 'required';
        } else {
            $rules[] = 'nullable';
        }

        if ($isEmailField || strtolower($type) === 'email') {
            $rules[] = 'email';
        }

        if (in_array(strtolower($type), ['integer', 'int', 'biginteger', 'bigint'], true)) {
            $rules[] = 'integer';
        }

        if (in_array(strtolower($type), ['boolean', 'bool'], true)) {
            $rules[] = 'boolean';
        }

        if (in_array(strtolower($type), ['decimal', 'float'], true)) {
            $rules[] = 'numeric';
        }

        if (in_array(strtolower($type), ['date', 'datetime', 'timestamp'], true)) {
            $rules[] = 'date';
        }

        if (in_array(strtolower($type), ['json'], true)) {
            $rules[] = 'array';
        }

        if (! in_array(strtolower($type), ['boolean', 'bool', 'integer', 'int', 'decimal', 'float', 'date', 'datetime', 'timestamp', 'json'], true)) {
            $rules[] = 'string';
        }

        if (str_contains($modifiers, 'unique')) {
            $rules[] = 'unique:{{table}},{{field.name}}';
        }

        return implode('|', $rules);
    }
}
