<?php

namespace Shehroz\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Shehroz\CrudGenerator\Console\MakeCrud;
use Shehroz\CrudGenerator\Services\CrudGeneratorService;
use Shehroz\CrudGenerator\Services\Generators\AdminControllerGenerator;
use Shehroz\CrudGenerator\Services\Generators\ApiControllerGenerator;
use Shehroz\CrudGenerator\Services\Generators\ApiResourceGenerator;
use Shehroz\CrudGenerator\Services\Generators\MenuGenerator;
use Shehroz\CrudGenerator\Services\Generators\MigrationGenerator;
use Shehroz\CrudGenerator\Services\Generators\ModelGenerator;
use Shehroz\CrudGenerator\Services\Generators\PolicyGenerator;
use Shehroz\CrudGenerator\Services\Generators\PolicyRegistrationGenerator;
use Shehroz\CrudGenerator\Services\Generators\RepositoryGenerator;
use Shehroz\CrudGenerator\Services\Generators\RequestGenerator;
use Shehroz\CrudGenerator\Services\Generators\RouteGenerator;
use Shehroz\CrudGenerator\Services\Generators\SeederGenerator;
use Shehroz\CrudGenerator\Services\Generators\ViewGenerator;
use Shehroz\CrudGenerator\Services\StubRenderer;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__ . '/Config/crud-generator.php', 'crud-generator');
        $this->mergeConfigFrom(__DIR__ . '/Config/admin-menu.php', 'admin-menu');

        $this->app->singleton(StubRenderer::class);
        $this->app->singleton(CrudGeneratorService::class);

        $generators = [
            ModelGenerator::class,
            MigrationGenerator::class,
            RequestGenerator::class,
            RepositoryGenerator::class,
            RepositoryBindingGenerator::class,
            PolicyGenerator::class,
            AdminControllerGenerator::class,
            ApiControllerGenerator::class,
            ApiResourceGenerator::class,
            ViewGenerator::class,
            RouteGenerator::class,
            MenuGenerator::class,
            SeederGenerator::class,
            PolicyRegistrationGenerator::class,
        ];

        foreach ($generators as $generator) {
            $this->app->singleton($generator);
        }
    }

    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__ . '/Resources/views', 'crud-generator');

        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrud::class,
            ]);

            $this->publishes([
                __DIR__ . '/Stubs' => base_path('stubs/crud-generator'),
            ], 'crud-generator-stubs');

            $this->publishes([
                __DIR__ . '/Config/crud-generator.php' => config_path('crud-generator.php'),
            ], 'crud-generator-config');

            $this->publishes([
                __DIR__ . '/Config/admin-menu.php' => config_path('admin-menu.php'),
                __DIR__ . '/Support/AdminMenu.php' => app_path('Support/AdminMenu.php'),
                __DIR__ . '/Resources/views/components/admin-sidebar.blade.php' => resource_path('views/components/admin-sidebar.blade.php'),
                __DIR__ . '/Resources/views/partials/menu-item.blade.php' => resource_path('views/partials/admin-menu-item.blade.php'),
            ], 'crud-generator-admin-menu');
        }
    }
}
