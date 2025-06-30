<?php

namespace Shehroz\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Shehroz\CrudGenerator\Console\MakeCrud;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        // Merge the package configuration
        $this->mergeConfigFrom(__DIR__ . '/../config/crud-generator.php', 'crud-generator');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            // Register the make:crud command
            $this->commands([
                MakeCrud::class,
            ]);

            // Publish stub files to the user's project
            $this->publishes([
                __DIR__ . '/../stubs' => base_path('stubs/crud-generator'),
            ], 'crud-generator-stubs');
        }
    }
}