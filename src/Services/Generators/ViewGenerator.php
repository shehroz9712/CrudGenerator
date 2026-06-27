<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class ViewGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if (! $definition->generateAdmin) {
            return;
        }

        $viewsPath = resource_path("views/{$definition->viewPath()}");
        $replacements = $this->replacements($definition);

        $headerLoop = collect($definition->fields)->map(function ($field) {
            return "<th class=\"px-4 py-2 text-left text-xs font-semibold uppercase\">{$field['title']}</th>";
        })->implode("\n            ");

        if ($definition->hasStatus) {
            $headerLoop .= "\n            <th class=\"px-4 py-2 text-left text-xs font-semibold uppercase\">Status</th>";
        }

        $bodyLoop = collect($definition->fields)->map(function ($field) use ($definition) {
            return "<td class=\"px-4 py-2\">{{ \${$definition->var}->{$field['name']} }}</td>";
        })->implode("\n            ");

        if ($definition->hasStatus) {
            $bodyLoop .= "\n            <td class=\"px-4 py-2\">\n                <span class=\"px-2 py-1 text-xs rounded {{ \${$definition->var}->status ? 'bg-green-100 text-green-700' : 'bg-red-100 text-red-700' }}\">\n                    {{ \${$definition->var}->status ? 'Active' : 'Inactive' }}\n                </span>\n            </td>";
        }

        $formLoop = $this->buildFormFields($definition);

        $views = [
            'index' => ['{{tableHeaders}}' => $headerLoop, '{{tableCells}}' => $bodyLoop],
            'create' => ['{{formFields}}' => $formLoop],
            'edit' => ['{{formFields}}' => $formLoop],
            'show' => ['{{showFields}}' => $this->buildShowFields($definition)],
        ];

        foreach ($views as $view => $extra) {
            $path = "{$viewsPath}/{$view}.blade.php";
            $viewReplacements = array_merge($replacements, $extra);

            $content = $this->renderer->render("views/{$view}", $viewReplacements, [
                'conditionals' => $this->conditionals($definition),
            ]);

            $this->renderer->write($path, $content);
        }
    }

    protected function buildFormFields(CrudDefinition $definition): string
    {
        $html = '';

        foreach ($definition->fields as $field) {
            $html .= $this->renderFieldInput($definition, $field);
        }

        if ($definition->hasStatus) {
            $html .= <<<BLADE
<div class="mb-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="status" value="1" {{ old('status', \${$definition->var}?->status ?? true) ? 'checked' : '' }} class="rounded border-gray-300">
        <span>Active</span>
    </label>
</div>

BLADE;
        }

        return $html;
    }

    protected function renderFieldInput(CrudDefinition $definition, array $field): string
    {
        $required = $field['nullable'] ? '' : 'required';

        return match ($field['input_type']) {
            'textarea' => <<<BLADE
<div class="mb-4">
    <label class="block text-sm font-medium mb-1">{$field['title']}</label>
    <textarea name="{$field['name']}" rows="4" class="w-full rounded border-gray-300" {$required}>{{ old('{$field['name']}', \${$definition->var}?->{$field['name']} ?? '') }}</textarea>
    @error('{$field['name']}')<p class="text-sm text-red-600 mt-1">{{ \$message }}</p>@enderror
</div>

BLADE,
            'checkbox' => <<<BLADE
<div class="mb-4">
    <label class="inline-flex items-center gap-2">
        <input type="checkbox" name="{$field['name']}" value="1" {{ old('{$field['name']}', \${$definition->var}?->{$field['name']} ?? false) ? 'checked' : '' }} class="rounded border-gray-300">
        <span>{$field['title']}</span>
    </label>
    @error('{$field['name']}')<p class="text-sm text-red-600 mt-1">{{ \$message }}</p>@enderror
</div>

BLADE,
            default => <<<BLADE
<div class="mb-4">
    <label class="block text-sm font-medium mb-1">{$field['title']}</label>
    <input type="{$field['input_type']}" name="{$field['name']}" value="{{ old('{$field['name']}', \${$definition->var}?->{$field['name']} ?? '') }}" class="w-full rounded border-gray-300" {$required}>
    @error('{$field['name']}')<p class="text-sm text-red-600 mt-1">{{ \$message }}</p>@enderror
</div>

BLADE,
        };
    }

    protected function buildShowFields(CrudDefinition $definition): string
    {
        $html = collect($definition->fields)->map(function ($field) use ($definition) {
            return <<<BLADE
<div class="mb-3">
    <dt class="text-sm text-gray-500">{$field['title']}</dt>
    <dd class="font-medium">{{ \${$definition->var}->{$field['name']} }}</dd>
</div>
BLADE;
        })->implode("\n");

        if ($definition->hasStatus) {
            $html .= <<<BLADE

<div class="mb-3">
    <dt class="text-sm text-gray-500">Status</dt>
    <dd class="font-medium">{{ \${$definition->var}->status ? 'Active' : 'Inactive' }}</dd>
</div>
BLADE;
        }

        return $html;
    }
}
