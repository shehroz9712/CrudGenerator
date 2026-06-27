<?php

namespace Shehroz\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Str;
use Shehroz\CrudGenerator\DTO\CrudDefinition;
use Shehroz\CrudGenerator\Parsers\FieldParser;
use Shehroz\CrudGenerator\Parsers\NameParser;
use Shehroz\CrudGenerator\Services\CrudGeneratorService;

class MakeCrud extends Command
{
    protected $signature = 'make:crud
        {name : Model name, e.g. User or Admin/User}
        {--fields= : Comma-separated fields, e.g. title:string,content:text,price:decimal:nullable}
        {--admin : Generate admin panel CRUD (controller + views)}
        {--web : Alias for --admin}
        {--api : Generate API CRUD}
        {--both : Generate both admin and API CRUD}
        {--policy : Generate policy and permission keys}
        {--no-policy : Skip policy generation}
        {--menu : Register admin sidebar menu item}
        {--no-menu : Skip menu registration}
        {--soft-delete : Enable soft deletes}
        {--status : Add active/inactive status field}
        {--seeder : Generate model seeder}
        {--no-seeder : Skip seeder generation}
        {--searchable= : Comma-separated searchable fields}
        {--sortable= : Comma-separated sortable fields}
        {--icon= : Admin menu icon class}
        {--menu-label= : Admin menu label}
        {--menu-order=100 : Admin menu sort order}
        {--menu-parent= : Parent menu key for nested menus}
        {--routes : Auto-append routes to route files}';

    protected $description = 'Generate production-ready CRUD modules for admin panel and/or API';

    public function __construct(
        protected NameParser $nameParser,
        protected FieldParser $fieldParser,
        protected CrudGeneratorService $generator,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $parsed = $this->nameParser->parse($this->argument('name'));
        $fields = $this->fieldParser->parse($this->option('fields'));

        $generateAdmin = $this->option('admin')
            || $this->option('web')
            || $this->option('both')
            || (! $this->option('api') && ! $this->option('admin') && ! $this->option('web') && $this->confirm('Generate admin panel CRUD?', true));

        $generateApi = $this->option('api')
            || $this->option('both')
            || ((! $this->option('api') && ! $this->option('both')) && $this->confirm('Generate API CRUD?', false));

        if (! $generateAdmin && ! $generateApi) {
            $this->error('Nothing selected to generate. Use --admin, --api, or --both.');

            return self::FAILURE;
        }

        $generatePolicy = $this->resolveFlag('policy', 'no-policy', true);
        $generateMenu = $generateAdmin && $this->resolveFlag('menu', 'no-menu', true);
        $generateSeeder = $this->resolveFlag('seeder', 'no-seeder', false);

        if ($this->option('routes')) {
            config(['crud-generator.routes.auto_append' => true]);
        }

        $searchable = $this->parseListOption('searchable') ?: array_column($fields, 'name');
        $sortable = $this->parseListOption('sortable') ?: array_merge(['id', 'created_at'], array_column($fields, 'name'));

        $definition = new CrudDefinition(
            baseName: $parsed['baseName'],
            namespace: $parsed['namespace'],
            namespacePath: $parsed['namespacePath'],
            table: $parsed['table'],
            routePrefix: $parsed['routePrefix'],
            routeName: $parsed['routeName'],
            var: $parsed['var'],
            pluralVar: $parsed['pluralVar'],
            kebabName: $parsed['kebabName'],
            permissionModule: $parsed['permissionModule'],
            fields: $fields,
            generateAdmin: $generateAdmin,
            generateApi: $generateApi,
            generatePolicy: $generatePolicy,
            generateMenu: $generateMenu,
            softDeletes: (bool) $this->option('soft-delete'),
            hasStatus: (bool) $this->option('status') || $this->hasStatusField($fields),
            searchableFields: $searchable,
            sortableFields: $sortable,
            generateSeeder: $generateSeeder,
            menuIcon: $this->option('icon') ?: config('crud-generator.menu.default_icon', 'fas fa-circle'),
            menuLabel: $this->option('menu-label') ?: Str::headline($parsed['pluralVar']),
            menuOrder: (int) $this->option('menu-order'),
            menuParent: $this->option('menu-parent') ?: null,
        );

        $this->info("Generating CRUD for {$definition->baseName}...");

        $messages = $this->generator->generate($definition, $this->output);

        foreach ($messages as $message) {
            $this->newLine();
            $this->line("<comment>{$message}</comment>");
        }

        $this->newLine();
        $this->info("CRUD for {$definition->baseName} generated successfully.");
        $this->warn('Run: php artisan migrate');

        if ($definition->generatePolicy) {
            $this->line('Permissions: ' . collect(['view', 'create', 'edit', 'delete'])
                ->map(fn ($action) => "{$definition->permissionModule}.{$action}")
                ->implode(', '));
        }

        return self::SUCCESS;
    }

    protected function resolveFlag(string $enableOption, string $disableOption, bool $default): bool
    {
        if ($this->option($enableOption)) {
            return true;
        }

        if ($this->option($disableOption)) {
            return false;
        }

        return $default;
    }

    protected function parseListOption(string $option): array
    {
        $value = $this->option($option);

        if (! $value) {
            return [];
        }

        return array_values(array_filter(array_map('trim', explode(',', $value))));
    }

    protected function hasStatusField(array $fields): bool
    {
        return collect($fields)->contains(fn ($field) => $field['name'] === 'status');
    }
}
