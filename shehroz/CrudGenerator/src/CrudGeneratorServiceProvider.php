<?php

namespace Shehroz\CrudGenerator;

use Illuminate\Support\ServiceProvider;
use Shehroz\CrudGenerator\Commands\MakeCrud;

class CrudGeneratorServiceProvider extends ServiceProvider
{
    public function register()
    {
        $this->mergeConfigFrom(__DIR__.'/config/config.php', 'crud-generator');
    }

    public function boot()
    {
        if ($this->app->runningInConsole()) {
            $this->commands([
                MakeCrud::class,
            ]);

            $this->publishes([
                __DIR__.'/Stubs' => resource_path('views/commands/crud'),
            ], 'crud-generator-stubs');
        }
    }
}
