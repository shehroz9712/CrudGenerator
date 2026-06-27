<?php

namespace Shehroz\CrudGenerator\DTO;

class CrudDefinition
{
    public function __construct(
        public readonly string $baseName,
        public readonly string $namespace,
        public readonly string $namespacePath,
        public readonly string $table,
        public readonly string $routePrefix,
        public readonly string $routeName,
        public readonly string $var,
        public readonly string $pluralVar,
        public readonly string $kebabName,
        public readonly string $permissionModule,
        public readonly array $fields,
        public readonly bool $generateAdmin,
        public readonly bool $generateApi,
        public readonly bool $generatePolicy,
        public readonly bool $generateMenu,
        public readonly bool $softDeletes,
        public readonly bool $hasStatus,
        public readonly array $searchableFields,
        public readonly array $sortableFields,
        public readonly bool $generateSeeder,
        public readonly string $menuIcon,
        public readonly string $menuLabel,
        public readonly int $menuOrder,
        public readonly ?string $menuParent,
    ) {}

    public function modelNamespace(): string
    {
        return $this->namespace ? "App\\Models\\{$this->namespace}" : 'App\\Models';
    }

    public function modelClass(): string
    {
        return "{$this->modelNamespace()}\\{$this->baseName}";
    }

    public function controllerNamespace(): string
    {
        return $this->namespace ? "App\\Http\\Controllers\\{$this->namespace}" : 'App\\Http\\Controllers';
    }

    public function apiControllerNamespace(): string
    {
        $apiNs = config('crud-generator.api.controller_namespace', 'App\\Http\\Controllers\\Api');

        return $this->namespace ? "{$apiNs}\\{$this->namespace}" : $apiNs;
    }

    public function requestNamespace(): string
    {
        return $this->namespace ? "App\\Http\\Requests\\{$this->namespace}" : 'App\\Http\\Requests';
    }

    public function repositoryNamespace(): string
    {
        return $this->namespace ? "App\\Repositories\\{$this->namespace}" : 'App\\Repositories';
    }

    public function interfaceNamespace(): string
    {
        return $this->namespace ? "App\\Repositories\\Interfaces\\{$this->namespace}" : 'App\\Repositories\\Interfaces';
    }

    public function policyNamespace(): string
    {
        return $this->namespace ? "App\\Policies\\{$this->namespace}" : 'App\\Policies';
    }

    public function resourceNamespace(): string
    {
        return $this->namespace ? "App\\Http\\Resources\\{$this->namespace}" : 'App\\Http\\Resources';
    }

    public function viewPath(): string
    {
        return "{$this->namespacePath}{$this->table}";
    }

    public function adminRouteName(): string
    {
        $prefix = rtrim(config('crud-generator.admin.route_name_prefix', 'admin.'), '.');

        if ($this->namespacePath) {
            $ns = str_replace('/', '.', rtrim($this->namespacePath, '/'));

            if (strtolower($ns) === strtolower($prefix)) {
                return "{$prefix}.{$this->pluralVar}";
            }

            return "{$prefix}.{$ns}.{$this->pluralVar}";
        }

        return "{$prefix}.{$this->pluralVar}";
    }

    public function apiRouteName(): string
    {
        $prefix = rtrim(config('crud-generator.api.route_name_prefix', 'api.'), '.');

        if ($this->namespacePath) {
            $ns = collect(explode('/', rtrim($this->namespacePath, '/')))
                ->map(fn ($part) => str($part)->kebab()->toString())
                ->implode('.');

            if (strtolower($ns) === strtolower($prefix)) {
                return "{$prefix}.{$this->pluralVar}";
            }

            return "{$prefix}.{$ns}.{$this->pluralVar}";
        }

        return "{$prefix}.{$this->pluralVar}";
    }

    public function fillableFields(): array
    {
        $fields = array_column($this->fields, 'name');

        if ($this->hasStatus && ! in_array('status', $fields, true)) {
            $fields[] = 'status';
        }

        return $fields;
    }

    public function allowedColumns(): array
    {
        $columns = array_merge(['id', 'created_at', 'updated_at'], array_column($this->fields, 'name'));

        if ($this->hasStatus) {
            $columns[] = 'status';
        }

        if ($this->softDeletes) {
            $columns[] = 'deleted_at';
        }

        return array_values(array_unique($columns));
    }

    public function toReplacements(): array
    {
        $adminRoutePrefix = config('crud-generator.admin.route_prefix', 'admin');
        $apiPrefix = config('crud-generator.api.prefix', 'v1');

        return [
            '{{model}}' => $this->baseName,
            '{{modelNamespace}}' => $this->modelNamespace(),
            '{{modelClass}}' => $this->modelClass(),
            '{{controllerNamespace}}' => $this->controllerNamespace(),
            '{{apiControllerNamespace}}' => $this->apiControllerNamespace(),
            '{{requestNamespace}}' => $this->requestNamespace(),
            '{{repositoryNamespace}}' => $this->repositoryNamespace(),
            '{{interfaceNamespace}}' => $this->interfaceNamespace(),
            '{{policyNamespace}}' => $this->policyNamespace(),
            '{{resourceNamespace}}' => $this->resourceNamespace(),
            '{{namespace}}' => $this->namespace,
            '{{namespacePath}}' => $this->namespacePath,
            '{{table}}' => $this->table,
            '{{routePrefix}}' => $this->routePrefix,
            '{{routeName}}' => $this->routeName,
            '{{adminRouteName}}' => $this->adminRouteName(),
            '{{apiRouteName}}' => $this->apiRouteName(),
            '{{var}}' => $this->var,
            '{{pluralVar}}' => $this->pluralVar,
            '{{kebabName}}' => $this->kebabName,
            '{{pluralKebab}}' => str($this->table)->kebab()->toString(),
            '{{pluralSnake}}' => $this->table,
            '{{permissionModule}}' => $this->permissionModule,
            '{{menuLabel}}' => $this->menuLabel,
            '{{menuIcon}}' => $this->menuIcon,
            '{{menuOrder}}' => (string) $this->menuOrder,
            '{{menuParent}}' => $this->menuParent ?? 'null',
            '{{adminRoutePrefix}}' => $adminRoutePrefix,
            '{{apiPrefix}}' => $apiPrefix,
            '{{viewPath}}' => $this->viewPath(),
            '{{adminLayout}}' => config('crud-generator.admin.layout', 'layouts.admin'),
        ];
    }
}
