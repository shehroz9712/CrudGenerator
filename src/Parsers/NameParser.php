<?php

namespace Shehroz\CrudGenerator\Parsers;

use Illuminate\Support\Str;

class NameParser
{
    public function parse(string $name): array
    {
        $nameParts = explode('/', $name);
        $baseName = Str::studly(array_pop($nameParts));
        $namespacePath = count($nameParts) > 0
            ? implode('/', array_map(fn ($part) => Str::studly($part), $nameParts)) . '/'
            : '';
        $namespace = count($nameParts) > 0
            ? implode('\\', array_map(fn ($part) => Str::studly($part), $nameParts))
            : '';

        $singularSnake = Str::snake($baseName);
        $pluralSnake = Str::plural($singularSnake);
        $camel = Str::camel($baseName);
        $pluralCamel = Str::plural($camel);

        $adminPrefix = config('crud-generator.admin.route_prefix', 'admin');
        $routePrefix = $namespacePath
            ? Str::kebab(rtrim($namespacePath, '/') . '/' . $pluralSnake)
            : Str::kebab($pluralSnake);

        if ($namespacePath && ! str_starts_with($routePrefix, $adminPrefix)) {
            $routePrefix = Str::kebab(rtrim($namespacePath, '/')) . '/' . Str::kebab($pluralSnake);
        }

        return [
            'baseName' => $baseName,
            'namespace' => $namespace,
            'namespacePath' => $namespacePath,
            'table' => $pluralSnake,
            'routePrefix' => $routePrefix,
            'routeName' => $pluralCamel,
            'var' => $camel,
            'pluralVar' => $pluralCamel,
            'kebabName' => Str::kebab($baseName),
            'permissionModule' => Str::snake($pluralSnake),
        ];
    }
}
