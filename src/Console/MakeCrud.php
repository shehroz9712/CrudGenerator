<?php

namespace Shehroz\CrudGenerator\Console;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File; 
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Schema; 
class MakeCrud extends Command
{
    protected $signature = 'make:crud 
        {name : Model name, e.g. Post or Admin/User} 
        {--fields= : Fields like title:string,content:text,price:decimal:nullable,published:boolean} 
        {--web : Generate web CRUD (views + controller)} 
        {--api : Generate API CRUD}';

    protected $description = 'Generate full CRUD with repository pattern, policies, Tailwind views and optional API';

    public function handle()
    {
        $name = $this->argument('name');
        $nameParts = explode('/', $name);
        $baseName = Str::studly(array_pop($nameParts));
        $namespacePath = count($nameParts) > 0 ? implode('/', $nameParts) . '/' : '';
        $namespace = count($nameParts) > 0 ? implode('\\', array_map('Str::studly', $nameParts)) . '\\' : '';

        // Naming
        $singularSnake = Str::snake($baseName);
        $pluralLower = Str::plural($singularSnake);
        $camel = Str::camel($baseName);
        $pluralCamel = Str::plural($camel);
        $routePrefix = Str::kebab($namespacePath . $pluralLower);

        // Base replacements
        $replacements = [
            '{{modelNamespace}}'       => $namespace ? "App\\Models\\{$namespace}" : 'App\\Models',
            '{{controllerNamespace}}'  => $namespace ? "App\\Http\\Controllers\\{$namespace}" : 'App\\Http\\Controllers',
            '{{requestNamespace}}'     => $namespace ? "App\\Http\\Requests\\{$namespace}" : 'App\\Http\\Requests',
            '{{repositoryNamespace}}'  => $namespace ? "App\\Repositories\\{$namespace}" : 'App\\Repositories',
            '{{interfaceNamespace}}'   => $namespace ? "App\\Repositories\\Interfaces\\{$namespace}" : 'App\\Repositories\\Interfaces',
            '{{policyNamespace}}'      => $namespace ? "App\\Policies\\{$namespace}" : 'App\\Policies',
            '{{model}}'                => $baseName,
            '{{table}}'                => $pluralLower,
            '{{routePrefix}}'          => $routePrefix,
            '{{routeName}}'            => $pluralCamel,
            '{{var}}'                  => $camel,
            '{{pluralVar}}'            => $pluralCamel,
        ];

        // Parse fields
        $fields = $this->parseFields($this->option('fields'));

        // Decide what to generate
        $generateWeb = $this->option('web') || (! $this->option('api') && $this->confirm('Generate web CRUD (with views)?', true));
        $generateApi = $this->option('api') || (! $this->option('web') && $this->confirm('Generate API CRUD?', false));

        if (! $generateWeb && ! $generateApi) {
            $this->error('Nothing selected to generate.');
            return;
        }

        $stubPath = __DIR__ . '/../stubs';

        // Common files
        $this->generateModel($stubPath, $replacements, $baseName, $namespacePath);
        $this->generateMigration($stubPath, $replacements, $pluralLower, $fields);
        $this->generateRequest($stubPath, $replacements, $baseName, $namespacePath, $fields);
        $this->generateRepository($stubPath, $replacements, $baseName, $namespacePath);
        $this->generateInterface($stubPath, $replacements, $baseName, $namespacePath);
        $this->generatePolicy($stubPath, $replacements, $baseName, $namespacePath);
        $this->generateSeeder($stubPath, $replacements, $baseName);

        if ($generateWeb) {
            $this->generateWebController($stubPath, $replacements, $baseName, $namespacePath);
            $this->generateViews($stubPath, $replacements, $pluralLower, $namespacePath, $fields);
            $this->outputWebRoutes($routePrefix, $namespace, $baseName, $pluralCamel);
        }

        if ($generateApi) {
            $this->generateApiController($stubPath, $replacements, $baseName, $namespacePath);
            $this->outputApiRoutes($routePrefix, $namespace, $baseName, $pluralCamel);
        }

        $this->info("\nCRUD for {$baseName} generated successfully!");
        $this->warn("Run: php artisan migrate");
        if ($fields) $this->line("Used custom fields: " . $this->option('fields'));
    }

    protected function parseFields($fieldsOption)
    {
        if (! $fieldsOption) return [];

        $fields = [];
        foreach (explode(',', $fieldsOption) as $part) {
            $part = trim($part);
            $segments = explode(':', $part);
            $name = $segments[0];
            $type = $segments[1] ?? 'string';
            $modifiers = $segments[2] ?? '';

            $fields[] = [
                'name'       => $name,
                'camel'      => Str::camel($name),
                'title'      => Str::title(str_replace('_', ' ', $name)),
                'type'       => $type,
                'input_type' => $this->getInputType($type),
                'rules'      => $this->getValidationRules($type, $modifiers, $name === 'email'),
                'nullable'   => str_contains($modifiers, 'nullable'),
                'unique'     => str_contains($modifiers, 'unique'),
            ];
        }
        return $fields;
    }

    protected function getInputType($type)
    {
        return match (strtolower($type)) {
            'text'              => 'textarea',
            'boolean'           => 'checkbox',
            'date', 'datetime'  => 'date',
            'email'             => 'email',
            'file'              => 'file',
            'integer', 'biginteger' => 'number',
            default             => 'text',
        };
    }

    protected function getValidationRules($type, $modifiers, $isEmailField)
    {
        $rules = [];

        if (! str_contains($modifiers, 'nullable')) $rules[] = 'required';

        if ($isEmailField || strtolower($type) === 'email') $rules[] = 'email';
        if (in_array(strtolower($type), ['integer', 'biginteger'])) $rules[] = 'integer';
        if (strtolower($type) === 'boolean') $rules[] = 'boolean';

        $rules[] = match (strtolower($type)) {
            'string' => 'string',
            'text'   => 'string',
            default  => 'string',
        };

        if (str_contains($modifiers, 'unique')) $rules[] = 'unique:{{table}},' . $type;

        return implode('|', $rules);
    }

    protected function generateFromStub($stub, $path, $replacements, $extra = null)
    {
        $stubFile = "$stub/$stub.stub";
        if (! File::exists($stubFile)) {
            $this->error("Stub missing: $stubFile");
            return;
        }

        $content = File::get($stubFile);

        // Fields loop
        if ($extra && isset($extra['loop'])) {
            $loop = '';
            foreach ($extra['fields'] as $field) {
                $loop .= str_replace(
                    ['{{field.name}}', '{{field.camel}}', '{{field.title}}', '{{field.input_type}}', '{{field.rules}}'],
                    [$field['name'], $field['camel'], $field['title'], $field['input_type'], $field['rules']],
                    $extra['loop']
                ) . "\n";
            }
            $content = str_replace('{{fields_loop}}', $loop, $content);
        }

        $content = str_replace(array_keys($replacements), array_values($replacements), $content);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->info(basename($path) . ' created');
    }

    // Individual generators
    protected function generateModel($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Models/{$namespacePath}{$baseName}.php");
        $this->generateFromStub("{$stubPath}/model", $path, $replacements);
    }

    protected function generateMigration($stubPath, $replacements, $table, $fields)
    {
        $path = database_path('migrations/' . date('Y_m_d_His') . "_create_{$table}_table.php");

        $columnsLoop = '';
        foreach ($fields as $field) {
            $nullable = $field['nullable'] ? '->nullable()' : '';
            $unique = $field['unique'] ? '->unique()' : '';
            $columnsLoop .= "            \$table->{$field['type']}('{$field['name']}'){$nullable}{$unique};\n";
        }

        $extra = ['fields' => $fields, 'loop' => $columnsLoop];
        $this->generateFromStub("{$stubPath}/migration", $path, $replacements, $extra);
    }

    protected function generateRequest($stubPath, $replacements, $baseName, $namespacePath, $fields)
    {
        $path = app_path("Http/Requests/{$namespacePath}{$baseName}Request.php");

        $storeRules = '';
        $updateRules = '';
        foreach ($fields as $field) {
            $rule = $field['rules'];

            // Store rules
            $storeRules .= "            '{$field['name']}' => '{$rule}',\n";

            // Update rules - sometimes + unique ignore current id
            $updateRule = 'sometimes|' . str_replace('required', '', $rule);
            if ($field['unique']) {
                $updateRule = str_replace('unique:{{table}}', "unique:{{table}},{$field['name']},\$id", $updateRule);
            }
            $updateRules .= "            '{$field['name']}' => '{$updateRule}',\n";
        }

        $extra = [
            'fields' => $fields,
            'loop' => $storeRules,
            'loop_update' => $updateRules, // hum stub mein alag placeholder nahi daale, direct replace kar denge
        ];

      
        $content = File::get("{$stubPath}/request.stub");
        $content = str_replace(array_keys($replacements), array_values($replacements), $content);
        $content = str_replace('{{fields_loop}}', $storeRules, $content);
        $content = str_replace('{{fields_loop_update}}', $updateRules, $content);

        File::ensureDirectoryExists(dirname($path));
        File::put($path, $content);
        $this->info("{$baseName}Request created");
    }

    protected function generateRepository($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Repositories/{$namespacePath}{$baseName}Repository.php");
        $this->generateFromStub("{$stubPath}/repository", $path, $replacements);
    }

    protected function generateInterface($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Repositories/Interfaces/{$namespacePath}{$baseName}RepositoryInterface.php");
        $this->generateFromStub("{$stubPath}/interface", $path, $replacements);
    }

    protected function generatePolicy($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Policies/{$namespacePath}{$baseName}Policy.php");
        $this->generateFromStub("{$stubPath}/policy", $path, $replacements);
    }

    protected function generateSeeder($stubPath, $replacements, $baseName)
    {
        $path = database_path("seeders/{$baseName}Seeder.php");
        $this->generateFromStub("{$stubPath}/seeder", $path, $replacements);
    }

    protected function generateWebController($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Http/Controllers/{$namespacePath}{$baseName}Controller.php");
        $this->generateFromStub("{$stubPath}/controller", $path, $replacements);
    }

    protected function generateApiController($stubPath, $replacements, $baseName, $namespacePath)
    {
        $path = app_path("Http/Controllers/Api/{$namespacePath}{$baseName}Controller.php");
        $this->generateFromStub("{$stubPath}/api_controller", $path, $replacements);
    }

    protected function generateViews($stubPath, $replacements, $pluralLower, $namespacePath, $fields)
    {
        $viewsPath = resource_path("views/{$namespacePath}{$pluralLower}");

        // index.blade.php - table columns
        $tableLoop = '';
        foreach ($fields as $field) {
            $tableLoop .= "<th class=\"px-6 py-3\">{$field['title']}</th>\n";
        }
        foreach ($fields as $field) {
            $tableLoop .= "<td class=\"px-6 py-4\">{{ \${$replacements['{{var}}']}->{$field['name']} }}</td>\n";
        }

        // create & edit - form fields
        $formLoop = '';
        foreach ($fields as $field) {
            if ($field['input_type'] === 'textarea') {
                $formLoop .= "<div class=\"mb-4\">\n    <label class=\"block text-gray-700\">{$field['title']}</label>\n    <textarea name=\"{$field['name']}\" class=\"mt-1 block w-full rounded-md border-gray-300\" required>{{ old('{$field['name']}', \${$replacements['{{var}}']}?->{$field['name']} ?? '') }}</textarea>\n</div>\n";
            } elseif ($field['input_type'] === 'checkbox') {
                $formLoop .= "<div class=\"mb-4\">\n    <label class=\"inline-flex items-center\">\n        <input type=\"checkbox\" name=\"{$field['name']}\" value=\"1\" {{ old('{$field['name']}', \${$replacements['{{var}}']}?->{$field['name']} ?? 0) ? 'checked' : '' }} class=\"rounded\">\n        <span class=\"ml-2 text-gray-700\">{$field['title']}</span>\n    </label>\n</div>\n";
            } else {
                $formLoop .= "<div class=\"mb-4\">\n    <label class=\"block text-gray-700\">{$field['title']}</label>\n    <input type=\"{$field['input_type']}\" name=\"{$field['name']}\" value=\"{{ old('{$field['name']}', \${$replacements['{{var}}']}?->{$field['name']} ?? '') }}\" class=\"mt-1 block w-full rounded-md border-gray-300\" required>\n</div>\n";
            }
        }

        // Generate each view
        $views = ['index', 'create', 'edit', 'show'];
        foreach ($views as $view) {
            $extra = null;
            if ($view === 'index') $extra = ['fields' => $fields, 'loop' => $tableLoop];
            if (in_array($view, ['create', 'edit'])) $extra = ['fields' => $fields, 'loop' => $formLoop];

            $path = "$viewsPath/{$view}.blade.php";
            $this->generateFromStub("{$stubPath}/views/{$view}", $path, $replacements, $extra);
        }
    }

    protected function outputWebRoutes($prefix, $namespace, $controller, $name)
    {
        $this->line("\n<info>Web routes (add to routes/web.php):</info>");
        $fullController = $namespace ? "\\App\\Http\\Controllers\\{$namespace}{$controller}Controller" : "\\App\\Http\\Controllers\\{$controller}Controller";
        $this->line("\nRoute::middleware(['auth'])->prefix('$prefix')->name('$name.')->group(function () {");
        $this->line("    Route::resource('/', $fullController::class);");
        $this->line("});");
    }

    protected function outputApiRoutes($prefix, $namespace, $controller, $name)
    {
        $this->line("\n<info>API routes (add to routes/api.php):</info>");
        $fullController = $namespace ? "\\App\\Http\\Controllers\\Api\\{$namespace}{$controller}Controller" : "\\App\\Http\\Controllers\\Api\\{$controller}Controller";
        $this->line("\nRoute::prefix('$prefix')->name('$name.')->group(function () {");
        $this->line("    Route::apiResource('/', $fullController::class);");
        $this->line("});");
    }
}
