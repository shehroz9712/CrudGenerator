<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class ApiResourceGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if (! $definition->generateApi) {
            return;
        }

        $path = app_path("Http/Resources/{$definition->namespacePath}{$definition->baseName}Resource.php");

        $resourceFields = collect($definition->fields)->map(function ($field) use ($definition) {
            return "            '{$field['name']}' => \$this->{$field['name']},";
        })->implode("\n");

        $replacements = $this->replacements($definition);
        $replacements['{{resourceFields}}'] = $resourceFields;

        $content = $this->renderer->render('api_resource', $replacements, [
            'conditionals' => $this->conditionals($definition),
        ]);

        $this->renderer->write($path, $content);
    }
}
