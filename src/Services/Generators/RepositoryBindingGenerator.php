<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class RepositoryBindingGenerator
{
    public function generate(CrudDefinition $definition): string
    {
        $providerPath = app_path('Providers/AppServiceProvider.php');
        $interface = "{$definition->interfaceNamespace()}\\{$definition->baseName}RepositoryInterface";
        $repository = "{$definition->repositoryNamespace()}\\{$definition->baseName}Repository";
        $marker = "// REPOSITORY: {$definition->baseName}";

        if (! File::exists($providerPath)) {
            return "Add repository binding manually:\n{$interface}::class => {$repository}::class,";
        }

        $content = File::get($providerPath);

        if (str_contains($content, $marker)) {
            return "Repository binding for {$definition->baseName} already exists.";
        }

        $binding = <<<PHP
        {$marker}
        \$this->app->bind({$interface}::class, {$repository}::class);

PHP;

        $updated = preg_replace(
            '/public function register\(\): void\s*\{/',
            "public function register(): void\n    {\n{$binding}",
            $content,
            1
        );

        if ($updated && $updated !== $content) {
            File::put($providerPath, $updated);

            return 'Repository binding added to AppServiceProvider.';
        }

        return "Add repository binding manually in AppServiceProvider register() method:\n\$this->app->bind({$interface}::class, {$repository}::class);";
    }
}
