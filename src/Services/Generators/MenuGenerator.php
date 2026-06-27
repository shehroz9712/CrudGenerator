<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class MenuGenerator
{
    public function generate(CrudDefinition $definition): ?string
    {
        if (! $definition->generateMenu || ! $definition->generateAdmin) {
            return null;
        }

        $configPath = config('crud-generator.menu.config_path', config_path('admin-menu.php'));

        if (! File::exists($configPath)) {
            return "Publish admin menu config first:\nphp artisan vendor:publish --tag=crud-generator-admin-menu\n\nThen register manually:\n" . $this->menuSnippet($definition);
        }

        $content = File::get($configPath);
        $marker = "// MENU: {$definition->baseName}";

        if (str_contains($content, $marker)) {
            return "Menu entry for {$definition->baseName} already exists in {$configPath}.";
        }

        $entry = $this->menuEntry($definition);
        $content = str_replace("'items' => [", "'items' => [\n        {$marker}\n        {$entry},", $content);
        File::put($configPath, $content);

        return "Admin menu entry added to {$configPath}.";
    }

    protected function menuEntry(CrudDefinition $definition): string
    {
        $parent = $definition->menuParent ? "'parent' => '{$definition->menuParent}'," : '';

        return <<<PHP
[
            'label' => '{$definition->menuLabel}',
            'icon' => '{$definition->menuIcon}',
            'route' => '{$definition->adminRouteName()}.index',
            'permission' => '{$definition->permissionModule}.view',
            'order' => {$definition->menuOrder},
            {$parent}
        ]
PHP;
    }

    protected function menuSnippet(CrudDefinition $definition): string
    {
        return "AdminMenu::add(" . var_export([
            'label' => $definition->menuLabel,
            'icon' => $definition->menuIcon,
            'route' => $definition->adminRouteName() . '.index',
            'permission' => $definition->permissionModule . '.view',
            'order' => $definition->menuOrder,
            'parent' => $definition->menuParent,
        ], true) . ");";
    }
}
