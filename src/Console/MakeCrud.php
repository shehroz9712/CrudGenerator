<?php

namespace Shehroz\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class MakeCrud extends Command
{
    protected $signature = 'make:crud {name}';
    protected $description = 'Generate a complete CRUD with roles, permissions & policies for web and/or API';

    public function handle()
    {
        $name = $this->argument('name');
        $nameParts = explode('/', $name);
        $baseName = Str::studly(array_pop($nameParts));
        $namespacePath = count($nameParts) > 0 ? implode('/', $nameParts) . '/' : '';
        $namespace = count($nameParts) > 0 ? str_replace('/', '\\', Str::studly($namespacePath)) : '';

        $singularSnake = Str::snake($baseName);
        $pluralSnake = Str::plural($singularSnake);
        $camelName = lcfirst($baseName);
        $pluralCamel = Str::plural($camelName);
        $routeName = Str::kebab($pluralCamel);
        $kebabName = Str::kebab($baseName);
        $tableName = Str::plural($singularSnake);

        // Add additional replacement keys for consistency
        $replacements = array_merge(compact(
            'baseName',
            'camelName',
            'pluralCamel',
            'routeName',
            'pluralSnake',
            'kebabName',
            'namespace',
            'namespacePath',
            'tableName'
        ), [
            'name'    => $baseName,    // for {{ name }}
            'varName' => $camelName,   // for {{ varName }}
        ]);

        // Prompt user for generation type
        $generationType = $this->choice(
            'What do you want to generate?',
            ['web', 'api', 'both'],
            'both'
        );

        $generateWeb = in_array($generationType, ['web', 'both']);
        $generateApi = in_array($generationType, ['api', 'both']);

        // File mappings
        $files = [];
        if ($generateWeb) {
            $files['controller'] = app_path("Http/Controllers/{$namespacePath}{$baseName}Controller.php");
            $files['views/index'] = resource_path("views/{$namespacePath}{$pluralSnake}/index.blade.php");
            $files['views/create'] = resource_path("views/{$namespacePath}{$pluralSnake}/create.blade.php");
            $files['views/edit'] = resource_path("views/{$namespacePath}{$pluralSnake}/edit.blade.php");
            $files['views/show'] = resource_path("views/{$namespacePath}{$pluralSnake}/show.blade.php");
        }
        if ($generateApi) {
            $files['api_controller'] = app_path("Http/Controllers/Api/{$namespacePath}{$baseName}Controller.php");
        }
        $files = array_merge($files, [
            'model' => app_path("Models/{$namespacePath}{$baseName}.php"),
            'request' => app_path("Http/Requests/{$namespacePath}{$baseName}Request.php"),
            'interface' => app_path("Repositories/Interfaces/{$namespacePath}{$baseName}RepositoryInterface.php"),
            'repository' => app_path("Repositories/{$namespacePath}{$baseName}Repository.php"),
            'policy' => app_path("Policies/{$namespacePath}{$baseName}Policy.php"),
            'migration' => database_path("migrations/" . date('Y_m_d_His') . "_create_{$pluralSnake}_table.php"),
            'seeder' => database_path("seeders/{$baseName}Seeder.php"),
        ]);
        $stubPath = __DIR__ . '/../stubs'; // ✅ Fix path to stubs

        foreach ($files as $stub => $path) {
            $stubFile = "$stubPath/$stub.stub";
            if (File::exists($stubFile)) {
                $content = File::get($stubFile);
                foreach ($replacements as $key => $value) {
                    info("Replacing in $stubFile");

                    $content = preg_replace('/{{\s*' . preg_quote($key, '/') . '\s*}}/', $value, $content);
                }
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $content);
                $this->info("$stub created at $path.");
            } else {
                $this->error("Stub file for $stub not found at $stubFile.");
            }
        }

        // Generate routes
        if ($generateWeb) {
            $routeFile = base_path('routes/web.php');
            $routeContent = <<<EOT

Route::middleware(['web', 'auth'])->prefix('{$namespacePath}{$routeName}')->name('{$pluralCamel}.')->group(function () {
    Route::get('/', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'index'])->name('index');
    Route::get('/create', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'create'])->name('create');
    Route::post('/', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'store'])->name('store');
    Route::get('/{id}', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'show'])->name('show');
    Route::get('/{id}/edit', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'edit'])->name('edit');
    Route::put('/{id}', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'update'])->name('update');
    Route::delete('/{id}', [App\\Http\\Controllers\\{$namespacePath}{$baseName}Controller::class, 'destroy'])->name('destroy');
});
EOT;
            File::append($routeFile, $routeContent);
            $this->info("Web routes appended to routes/web.php");
        }

        if ($generateApi) {
            $apiRouteFile = base_path('routes/api.php');
            $apiRouteContent = <<<EOT

Route::middleware(['api'])->prefix('{$namespacePath}{$routeName}')->name('{$pluralCamel}.')->group(function () {
    Route::get('/', [App\\Http\\Controllers\\Api\\{$namespacePath}{$baseName}Controller::class, 'index'])->name('index');
    Route::post('/', [App\\Http\\Controllers\\Api\\{$namespacePath}{$baseName}Controller::class, 'store'])->name('store');
    Route::get('/{id}', [App\\Http\\Controllers\\Api\\{$namespacePath}{$baseName}Controller::class, 'show'])->name('show');
    Route::put('/{id}', [App\\Http\\Controllers\\Api\\{$namespacePath}{$baseName}Controller::class, 'update'])->name('update');
    Route::delete('/{id}', [App\\Http\\Controllers\\Api\\{$namespacePath}{$baseName}Controller::class, 'destroy'])->name('destroy');
});
EOT;
            File::append($apiRouteFile, $apiRouteContent);
            $this->info("API routes appended to routes/api.php");
        }

        $this->info("CRUD for {$baseName} generated successfully!");
    }
}
