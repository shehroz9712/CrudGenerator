<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Shehroz\CrudGenerator\DTO\CrudDefinition;

class RepositoryGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): void
    {
        $repoPath = app_path("Repositories/{$definition->namespacePath}{$definition->baseName}Repository.php");
        $interfacePath = app_path("Repositories/Interfaces/{$definition->namespacePath}{$definition->baseName}RepositoryInterface.php");

        $replacements = $this->replacements($definition);

        $this->renderer->write(
            $interfacePath,
            $this->renderer->render('interface', $replacements)
        );

        $this->renderer->write(
            $repoPath,
            $this->renderer->render('repository', $replacements, [
                'conditionals' => $this->conditionals($definition),
            ])
        );
    }
}
