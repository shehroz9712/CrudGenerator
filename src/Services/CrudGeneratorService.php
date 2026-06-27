<?php

namespace Shehroz\CrudGenerator\Services;

use Illuminate\Console\OutputStyle;
use Shehroz\CrudGenerator\DTO\CrudDefinition;
use Shehroz\CrudGenerator\Services\Generators\AdminControllerGenerator;
use Shehroz\CrudGenerator\Services\Generators\ApiControllerGenerator;
use Shehroz\CrudGenerator\Services\Generators\ApiResourceGenerator;
use Shehroz\CrudGenerator\Services\Generators\MenuGenerator;
use Shehroz\CrudGenerator\Services\Generators\MigrationGenerator;
use Shehroz\CrudGenerator\Services\Generators\ModelGenerator;
use Shehroz\CrudGenerator\Services\Generators\PolicyGenerator;
use Shehroz\CrudGenerator\Services\Generators\PolicyRegistrationGenerator;
use Shehroz\CrudGenerator\Services\Generators\RepositoryBindingGenerator;
use Shehroz\CrudGenerator\Services\Generators\RepositoryGenerator;
use Shehroz\CrudGenerator\Services\Generators\RequestGenerator;
use Shehroz\CrudGenerator\Services\Generators\RouteGenerator;
use Shehroz\CrudGenerator\Services\Generators\SeederGenerator;
use Shehroz\CrudGenerator\Services\Generators\ViewGenerator;

class CrudGeneratorService
{
    public function __construct(
        protected StubRenderer $renderer,
        protected ModelGenerator $modelGenerator,
        protected MigrationGenerator $migrationGenerator,
        protected RequestGenerator $requestGenerator,
        protected RepositoryGenerator $repositoryGenerator,
        protected RepositoryBindingGenerator $repositoryBindingGenerator,
        protected PolicyGenerator $policyGenerator,
        protected AdminControllerGenerator $adminControllerGenerator,
        protected ApiControllerGenerator $apiControllerGenerator,
        protected ApiResourceGenerator $apiResourceGenerator,
        protected ViewGenerator $viewGenerator,
        protected RouteGenerator $routeGenerator,
        protected MenuGenerator $menuGenerator,
        protected SeederGenerator $seederGenerator,
        protected PolicyRegistrationGenerator $policyRegistrationGenerator,
    ) {}

    public function generate(CrudDefinition $definition, OutputStyle $output): array
    {
        $messages = [];

        $this->modelGenerator->generate($definition);
        $output->info("Model {$definition->baseName} created.");

        $this->migrationGenerator->generate($definition);
        $output->info("Migration for {$definition->table} created.");

        $this->requestGenerator->generate($definition);
        $output->info('Form request classes created.');

        $this->repositoryGenerator->generate($definition);
        $output->info('Repository and interface created.');

        $bindingMessage = $this->repositoryBindingGenerator->generate($definition);
        $messages[] = $bindingMessage;

        if ($definition->generatePolicy) {
            $this->policyGenerator->generate($definition);
            $output->info("Policy {$definition->baseName}Policy created.");

            $policyMessage = $this->policyRegistrationGenerator->generate($definition);
            if ($policyMessage) {
                $messages[] = $policyMessage;
            }
        }

        $this->seederGenerator->generate($definition);
        if ($definition->generateSeeder) {
            $output->info("Seeder {$definition->baseName}Seeder created.");
        }

        $permissionMessage = $this->seederGenerator->permissionMessage($definition);
        if ($permissionMessage) {
            $messages[] = $permissionMessage;
        }

        if ($definition->generateAdmin) {
            $this->adminControllerGenerator->generate($definition);
            $output->info("Admin controller {$definition->baseName}Controller created.");

            $this->viewGenerator->generate($definition);
            $output->info('Admin views created.');
        }

        if ($definition->generateApi) {
            $this->apiControllerGenerator->generate($definition);
            $output->info("API controller {$definition->baseName}Controller created.");

            $this->apiResourceGenerator->generate($definition);
            $output->info("API resource {$definition->baseName}Resource created.");
        }

        $messages = array_merge($messages, $this->routeGenerator->generate($definition));

        $menuMessage = $this->menuGenerator->generate($definition);
        if ($menuMessage) {
            $messages[] = $menuMessage;
        }

        return $messages;
    }
}
