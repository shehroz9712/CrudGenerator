<?php

namespace Shehroz\CrudGenerator\Services\Generators;

use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Shehroz\CrudGenerator\DTO\CrudDefinition;

class RouteGenerator
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
        $controller = $definition->controllerNamespace() . '\\' . $definition->baseName . 'Controller';
        $middleware = implode("', '", config('crud-generator.admin.middleware', ['web', 'auth']));
        $prefix = config('crud-generator.admin.route_prefix', 'admin');
        $namePrefix = rtrim(config('crud-generator.admin.route_name_prefix', 'admin.'), '.') . '.';
        $resourcePath = $this->resourcePath($definition, $prefix);

        $snippet = <<<PHP

Route::middleware(['{$middleware}'])->prefix('{$prefix}')->name('{$namePrefix}')->group(function () {
    Route::resource('{$resourcePath}', {$controller}::class);
});

PHP;

        return $this->appendOrPrint($snippet, 'admin', $definition);
    }

    protected function buildApiRoutes(CrudDefinition $definition): string
    {
        $controller = $definition->apiControllerNamespace() . '\\' . $definition->baseName . 'Controller';
        $middleware = implode("', '", config('crud-generator.api.middleware', ['api', 'auth:sanctum']));
        $prefix = config('crud-generator.api.prefix', 'v1');
        $namePrefix = rtrim(config('crud-generator.api.route_name_prefix', 'api.'), '.') . '.';
        $resourcePath = $this->resourcePath($definition, config('crud-generator.admin.route_prefix', 'admin'));

        $snippet = <<<PHP

Route::middleware(['{$middleware}'])->prefix('{$prefix}')->name('{$namePrefix}')->group(function () {
    Route::apiResource('{$resourcePath}', {$controller}::class);
});

PHP;

        return $this->appendOrPrint($snippet, 'api', $definition);
    }

    protected function resourcePath(CrudDefinition $definition, string $skipPrefix = ''): string
    {
        $resource = Str::kebab($definition->pluralVar);

        if (! $definition->namespacePath) {
            return $resource;
        }

        $parts = collect(explode('/', rtrim($definition->namespacePath, '/')))
            ->map(fn ($part) => Str::kebab($part))
            ->values();

        if ($skipPrefix && $parts->first() === Str::kebab($skipPrefix)) {
            $parts = $parts->slice(1);
        }

        if ($parts->isEmpty()) {
            return $resource;
        }

        return $parts->implode('/') . '/' . $resource;
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
