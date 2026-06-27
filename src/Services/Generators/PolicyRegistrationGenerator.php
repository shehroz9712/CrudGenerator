<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class PolicyRegistrationGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): ?string
    {
        if (! $definition->generatePolicy) {
            return null;
        }

        $providerPath = app_path('Providers/AuthServiceProvider.php');

        if (! File::exists($providerPath)) {
            return "Register policy manually in AuthServiceProvider:\n{$definition->modelClass()}::class => {$definition->policyNamespace()}\\{$definition->baseName}Policy::class,";
        }

        $content = File::get($providerPath);
        $marker = "// POLICY: {$definition->baseName}";

        if (str_contains($content, $marker)) {
            return "Policy for {$definition->baseName} already registered.";
        }

        $entry = "{$marker}\n        {$definition->modelClass()}::class => {$definition->policyNamespace()}\\{$definition->baseName}Policy::class,";
        $content = str_replace('protected $policies = [', "protected \$policies = [\n        {$entry}", $content);
        File::put($providerPath, $content);

        return "Policy registered in AuthServiceProvider.";
    }
}
