<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class SeederGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): ?string
    {
        if (! $definition->generateSeeder) {
            return null;
        }

        $path = database_path("seeders/{$definition->baseName}Seeder.php");
        $content = $this->renderer->render('seeder', $this->replacements($definition));
        $this->renderer->write($path, $content);

        $permissionMessage = null;

        if ($definition->generatePolicy) {
            $permissionMessage = $this->appendPermissions($definition);
        }

        return $permissionMessage;
    }

    protected function appendPermissions(CrudDefinition $definition): string
    {
        $seederFile = config('crud-generator.permissions.seeder_file', database_path('seeders/PermissionSeeder.php'));
        $permissions = collect(config('crud-generator.permissions.actions', ['view', 'create', 'edit', 'delete']))
            ->map(fn ($action) => "'{$definition->permissionModule}.{$action}'")
            ->implode(', ');

        $marker = "// PERMISSIONS: {$definition->baseName}";

        if (! File::exists($seederFile)) {
            $content = $this->renderer->render('permission_seeder', [
                '{{marker}}' => $marker,
                '{{permissions}}' => $permissions,
            ]);
            $this->renderer->write($seederFile, $content);

            return "PermissionSeeder created at {$seederFile}.";
        }

        $content = File::get($seederFile);

        if (str_contains($content, $marker)) {
            return "Permissions for {$definition->baseName} already exist in {$seederFile}.";
        }

        $content = str_replace(
            '$permissions = [',
            "\$permissions = [\n            {$marker}\n            {$permissions},",
            $content
        );

        File::put($seederFile, $content);

        return "Permissions appended to {$seederFile}.";
    }
}
