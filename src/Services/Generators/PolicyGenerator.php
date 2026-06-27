<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class PolicyGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        if (! $definition->generatePolicy) {
            return;
        }

        $path = app_path("Policies/{$definition->namespacePath}{$definition->baseName}Policy.php");

        $content = $this->renderer->render('policy', $this->replacements($definition));
        $this->renderer->write($path, $content);
    }
}
