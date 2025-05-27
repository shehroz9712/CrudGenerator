<?php

namespace Shehroz\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    protected $signature = 'make:crud {name}';
    protected $description = 'Generate a complete CRUD with roles, permissions & policies';

    public function handle()
    {
        $name = Str::studly($this->argument('name'));
        $singularSnake = Str::snake($name);
        $pluralSnake = Str::plural($singularSnake);
        $camelName = lcfirst($name);
        $pluralCamel = Str::plural($camelName);
        $routeName = Str::kebab($pluralCamel);
        $kebabName = Str::kebab($name);
        $stubPath = __DIR__ . '/../../stubs';

        $files = [
            'controller' => app_path("Http/Controllers/Admin/{$name}Controller.php"),
            'model' => app_path("Models/{$name}.php"),
            'request' => app_path("Http/Requests/{$name}Request.php"),
            'interface' => app_path("Repositories/Interfaces/{$name}RepositoryInterface.php"),
            'repository' => app_path("Repositories/{$name}Repository.php"),
            'policy' => app_path("Policies/{$name}Policy.php"),
        ];

        $replacements = compact('name', 'camelName', 'pluralCamel', 'routeName', 'pluralSnake', 'kebabName');

        foreach ($files as $stub => $path) {
            $stubFile = "$stubPath/$stub.stub";
            if (File::exists($stubFile)) {
                $content = File::get($stubFile);
                foreach ($replacements as $key => $value) {
                    $content = str_replace("{{{$key}}}", $value, $content);
                }
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $content);
                $this->info("$stub created.");
            }
        }
        Route::middleware(['web', 'auth'])->prefix('admin')->name('{{pluralVarName}}.')->group(function () {
            Route::get('{{pluralSnake}}', [{{name}}Controller::class, 'index'])->name('index');
            Route::get('{{pluralSnake}}/create', [{{name}}Controller::class, 'create'])->name('create');
            Route::post('{{pluralSnake}}', [{{name}}Controller::class, 'store'])->name('store');
            Route::get('{{pluralSnake}}/{id}', [{{name}}Controller::class, 'show'])->name('show');
            Route::get('{{pluralSnake}}/{id}/edit', [{{name}}Controller::class, 'edit'])->name('edit');
            Route::put('{{pluralSnake}}/{id}', [{{name}}Controller::class, 'update'])->name('update');
            Route::delete('{{pluralSnake}}/{id}', [{{name}}Controller::class, 'destroy'])->name('destroy');
        });

        $this->info("CRUD for {$name} generated successfully!");
    }
}
