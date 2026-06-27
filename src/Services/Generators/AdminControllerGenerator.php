<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class AdminControllerGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if (! $definition->generateAdmin) {
            return;
        }

        $path = app_path("Http/Controllers/{$definition->namespacePath}{$definition->baseName}Controller.php");

        $content = $this->renderer->render('controller', $this->replacements($definition), [
            'conditionals' => $this->conditionals($definition),
        ]);

        $this->renderer->write($path, $content);
    }
}
