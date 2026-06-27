<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class RouteGenerator extends BaseGenerator
{
    public function generate(CrudDefinition $definition): array
    {
        $messages = [];

        if ($definition->generateAdmin) {
            $messages[] = $this->buildAdminRoutes($definition);
        }

        if ($definition->generateApi) {
            $messages[] = $this->buildApiRoutes($definition);
        }

        return array_filter($messages);
    }

    protected function buildAdminRoutes(CrudDefinition $definition): string
    {
        $controller = "\\{$definition->controllerNamespace()}\\{$definition->baseName}Controller";
        $middleware = implode("', '", config('crud-generator.admin.middleware', ['web', 'auth']));
        $prefix = config('crud-generator.admin.route_prefix', 'admin');
        $routeName = $definition->adminRouteName();
        $resource = Str::kebab($definition->pluralVar);

        if ($definition->namespacePath) {
            $ns = rtrim($definition->namespacePath, '/');
            $resourcePath = Str::kebab($ns) . '/' . $resource;
        } else {
            $resourcePath = $resource;
        }

        $snippet = <<<PHP

Route::middleware(['{$middleware}'])->prefix('{$prefix}')->name('{$routeName}.')->group(function () {
    Route::resource('{$resourcePath}', {$controller}::class);
});

PHP;

        return $this->appendOrPrint($snippet, 'admin', $definition);
    }

    protected function buildApiRoutes(CrudDefinition $definition): string
    {
        $controller = "\\{$definition->apiControllerNamespace()}\\{$definition->baseName}Controller";
        $middleware = implode("', '", config('crud-generator.api.middleware', ['api', 'auth:sanctum']));
        $prefix = config('crud-generator.api.prefix', 'api/v1');
        $routeName = $definition->apiRouteName();
        $resource = Str::kebab($definition->pluralVar);

        if ($definition->namespacePath) {
            $ns = rtrim($definition->namespacePath, '/');
            $resourcePath = Str::kebab($ns) . '/' . $resource;
        } else {
            $resourcePath = $resource;
        }

        $snippet = <<<PHP

Route::middleware(['{$middleware}'])->prefix('{$prefix}')->name('{$routeName}.')->group(function () {
    Route::apiResource('{$resourcePath}', {$controller}::class);
});

PHP;

        return $this->appendOrPrint($snippet, 'api', $definition);
    }

    protected function appendOrPrint(string $snippet, string $type, CrudDefinition $definition): string
    {
        if (! config('crud-generator.routes.auto_append', false)) {
            $file = $type === 'admin'
                ? (config('crud-generator.routes.admin_file') ?: config('crud-generator.routes.web_file'))
                : config('crud-generator.routes.api_file');

            return "Add the following to {$file}:\n{$snippet}";
        }

        $file = $type === 'admin'
            ? (File::exists(config('crud-generator.routes.admin_file'))
                ? config('crud-generator.routes.admin_file')
                : config('crud-generator.routes.web_file'))
            : config('crud-generator.routes.api_file');

        if (! File::exists($file)) {
            return "Create {$file} and add:\n{$snippet}";
        }

        $marker = "// CRUD: {$definition->baseName}";
        $content = File::get($file);

        if (str_contains($content, $marker)) {
            return "Routes for {$definition->baseName} already exist in {$file}.";
        }

        File::append($file, "\n{$marker}\n{$snippet}");

        return "Routes appended to {$file}.";
    }
}
