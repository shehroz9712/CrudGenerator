<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class ApiControllerGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if (! $definition->generateApi) {
            return;
        }

        $path = app_path("Http/Controllers/Api/{$definition->namespacePath}{$definition->baseName}Controller.php");

        $content = $this->renderer->render('api_controller', $this->replacements($definition), [
            'conditionals' => $this->conditionals($definition),
        ]);

        $this->renderer->write($path, $content);
    }
}
