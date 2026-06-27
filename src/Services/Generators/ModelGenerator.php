<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class ModelGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        $path = app_path("Models/{$definition->namespacePath}{$definition->baseName}.php");

        $content = $this->renderer->render('model', $this->replacements($definition), [
            'conditionals' => $this->conditionals($definition),
        ]);

        $this->renderer->write($path, $content);
    }
}
