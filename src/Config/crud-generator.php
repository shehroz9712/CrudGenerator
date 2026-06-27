<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Stub Path
    |--------------------------------------------------------------------------
    |
    | Published stubs override package defaults. Run:
    | php artisan vendor:publish --tag=crud-generator-stubs
    |
    */
    'stubs_path' => base_path('stubs/crud-generator'),

    /*
    |--------------------------------------------------------------------------
    | Admin Panel
    |--------------------------------------------------------------------------
    */
    'admin' => [
        'layout' => 'layouts.admin',
        'route_prefix' => 'admin',
        'route_name_prefix' => 'admin.',
        'middleware' => ['web', 'auth'],
    ],

    /*
    |--------------------------------------------------------------------------
    | API
    |--------------------------------------------------------------------------
    */
    'api' => [
        'prefix' => 'api/v1',
        'route_name_prefix' => 'api.',
        'middleware' => ['api', 'auth:sanctum'],
        'controller_namespace' => 'App\\Http\\Controllers\\Api',
    ],

    /*
    |--------------------------------------------------------------------------
    | Admin Menu
    |--------------------------------------------------------------------------
    */
    'menu' => [
        'config_path' => config_path('admin-menu.php'),
        'default_icon' => 'fas fa-circle',
        'default_order' => 100,
    ],

    /*
    |--------------------------------------------------------------------------
    | Route Files
    |--------------------------------------------------------------------------
    */
    'routes' => [
        'admin_file' => base_path('routes/admin.php'),
        'web_file' => base_path('routes/web.php'),
        'api_file' => base_path('routes/api.php'),
        'auto_append' => false,
    ],

    /*
    |--------------------------------------------------------------------------
    | Permissions
    |--------------------------------------------------------------------------
    */
    'permissions' => [
        'actions' => ['view', 'create', 'edit', 'delete'],
        'seeder_file' => database_path('seeders/PermissionSeeder.php'),
    ],

];
