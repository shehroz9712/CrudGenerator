<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;
use Shehroz\CrudGenerator\Services\StubRenderer;

abstract class BaseGenerator
{
    public function __construct(
        protected StubRenderer $renderer,
    ) {}

    abstract public function generate(CrudDefinition $definition): void;

    protected function replacements(CrudDefinition $definition): array
    {
        $replacements = $definition->toReplacements();

        $replacements['{{fillable}}'] = $this->buildFillable($definition);
        $replacements['{{casts}}'] = $this->buildCasts($definition);
        $replacements['{{allowedColumns}}'] = $this->buildAllowedColumns($definition);
        $replacements['{{searchScope}}'] = $this->buildSearchScope($definition);
        $replacements['{{sortableColumns}}'] = implode(', ', array_map(
            fn ($col) => "'{$col}'",
            $definition->sortableFields ?: $definition->allowedColumns()
        ));

        return $replacements;
    }

    protected function buildFillable(CrudDefinition $definition): string
    {
        return collect($definition->fillableFields())
            ->map(fn ($field) => "        '{$field}',")
            ->implode("\n");
    }

    protected function buildCasts(CrudDefinition $definition): string
    {
        $casts = [];

        if ($definition->hasStatus) {
            $casts[] = "        'status' => 'boolean',";
        }

        foreach ($definition->fields as $field) {
            if (in_array($field['type'], ['boolean'], true)) {
                $casts[] = "        '{$field['name']}' => 'boolean',";
            }
            if (in_array($field['type'], ['date', 'dateTime'], true)) {
                $casts[] = "        '{$field['name']}' => 'datetime',";
            }
            if ($field['type'] === 'json') {
                $casts[] = "        '{$field['name']}' => 'array',";
            }
        }

        return $casts ? implode("\n", $casts) : '';
    }

    protected function buildAllowedColumns(CrudDefinition $definition): string
    {
        return collect($definition->allowedColumns())
            ->map(fn ($col) => "'{$col}'")
            ->implode(', ');
    }

    protected function buildSearchScope(CrudDefinition $definition): string
    {
        $searchFields = $definition->searchableFields ?: array_column($definition->fields, 'name');

        if (empty($searchFields)) {
            return "            ->when(\$params['search'] ?? null, fn (\$q, \$search) => \$q->where('id', 'like', \"%{\$search}%\"))";
        }

        $lines = collect($searchFields)->map(function ($field) {
            return "                    \$q->orWhere('{$field}', 'like', \"%{\$search}%\");";
        })->implode("\n");

        return <<<PHP
            ->when(\$params['search'] ?? null, function (\$q, \$search) {
                \$q->where(function (\$q) use (\$search) {
{$lines}
                });
            })
PHP;
    }

    protected function conditionals(CrudDefinition $definition): array
    {
        $casts = $this->buildCasts($definition);

        return [
            'softDeletes' => $definition->softDeletes,
            'hasStatus' => $definition->hasStatus,
            'hasCasts' => $casts !== '' || $definition->hasStatus,
            'generatePolicy' => $definition->generatePolicy,
        ];
    }
}
