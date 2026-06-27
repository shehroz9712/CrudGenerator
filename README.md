# Shehroz CRUD Generator

A Laravel package that generates production-ready CRUD modules for **Admin Panel** and **API** with policies, permissions, repositories, and a dynamic admin menu system.

Compatible with **PHP 8.1+** and **Laravel 9–12**.

## Features

- Admin panel CRUD (controller, views, search/filter, pagination, status, soft deletes)
- API CRUD (controller, API resources, consistent JSON responses)
- Policy and permission keys (`module.view`, `module.create`, `module.edit`, `module.delete`)
- Dynamic admin sidebar menu registration
- Repository pattern with interface binding
- Publishable stubs and config for full customization
- Nested namespaces (e.g. `admin/user`)

## Installation

```bash
composer require shehroz/crud-generator
```

Publish configuration and admin menu (recommended):

```bash
php artisan vendor:publish --tag=crud-generator-config
php artisan vendor:publish --tag=crud-generator-admin-menu
```

Publish stubs to customize templates (optional):

```bash
php artisan vendor:publish --tag=crud-generator-stubs
```

## Quick Start

### Admin + API with policy and menu

```bash
php artisan make:crud User \
  --fields="name:string,email:string:unique,phone:string:nullable" \
  --admin --api --policy --menu --status --soft-delete
```

### Admin only

```bash
php artisan make:crud admin/Category \
  --fields="title:string,description:text:nullable" \
  --admin --menu --icon="fas fa-tags"
```

### API only

```bash
php artisan make:crud Product \
  --fields="name:string,price:decimal,stock:integer" \
  --api --policy
```

## Command Options

| Option | Description |
|--------|-------------|
| `name` | Model name (`User` or `Admin/User`) |
| `--fields=` | Comma-separated fields: `name:type:modifiers` |
| `--admin` / `--web` | Generate admin panel CRUD |
| `--api` | Generate API CRUD |
| `--both` | Generate admin + API |
| `--policy` | Generate policy (default: enabled) |
| `--no-policy` | Skip policy generation |
| `--menu` | Register admin menu item (default with admin) |
| `--no-menu` | Skip menu registration |
| `--soft-delete` | Enable soft deletes |
| `--status` | Add active/inactive status field |
| `--seeder` | Generate model seeder |
| `--searchable=` | Searchable fields (comma-separated) |
| `--sortable=` | Sortable fields (comma-separated) |
| `--icon=` | Menu icon class (Font Awesome, etc.) |
| `--menu-label=` | Custom menu label |
| `--menu-order=` | Menu sort order (default: 100) |
| `--menu-parent=` | Parent menu key for nested menus |
| `--routes` | Auto-append routes to route files |

### Field format

```
fieldName:type:modifiers
```

**Types:** `string`, `text`, `integer`, `boolean`, `decimal`, `date`, `datetime`, `json`

**Modifiers:** `nullable`, `unique`

**Example:**

```bash
--fields="title:string,body:text:nullable,email:string:unique,published:boolean"
```

## Generated Files

| Component | Path |
|-----------|------|
| Model | `app/Models/[Namespace]/Model.php` |
| Migration | `database/migrations/*_create_*_table.php` |
| Admin Controller | `app/Http/Controllers/[Namespace]/ModelController.php` |
| API Controller | `app/Http/Controllers/Api/[Namespace]/ModelController.php` |
| API Resource | `app/Http/Resources/[Namespace]/ModelResource.php` |
| Store/Update Requests | `app/Http/Requests/[Namespace]/` |
| Repository + Interface | `app/Repositories/` |
| Policy | `app/Policies/[Namespace]/ModelPolicy.php` |
| Views | `resources/views/[namespace]/[table]/` |
| Seeder | `database/seeders/ModelSeeder.php` (with `--seeder`) |

## Admin Menu System

After publishing the admin menu package:

```bash
php artisan vendor:publish --tag=crud-generator-admin-menu
```

Register menu items in `config/admin-menu.php` or at runtime:

```php
use Shehroz\CrudGenerator\Support\AdminMenu;

AdminMenu::add([
    'label' => 'Users',
    'icon' => 'fas fa-users',
    'route' => 'admin.users.index',
    'permission' => 'users.view',
    'order' => 10,
]);
```

Include the sidebar in your admin layout:

```blade
<x-admin-sidebar />
{{-- or --}}
@include('components.admin-sidebar')
```

When you run `make:crud` with `--menu`, a menu entry is automatically appended to `config/admin-menu.php`.

### Nested menus

```php
AdminMenu::add([
    'key' => 'catalog',
    'label' => 'Catalog',
    'icon' => 'fas fa-box',
    'order' => 20,
]);

AdminMenu::add([
    'label' => 'Products',
    'icon' => 'fas fa-tag',
    'route' => 'admin.products.index',
    'permission' => 'products.view',
    'parent' => 'catalog',
    'order' => 1,
]);
```

## API Response Format

Generated API controllers return a consistent JSON structure:

```json
{
  "success": true,
  "message": "Data fetched successfully",
  "data": {},
  "meta": {
    "current_page": 1,
    "last_page": 5,
    "per_page": 15,
    "total": 72
  }
}
```

Validation errors follow Laravel's standard `422` response format via Form Requests.

## Permissions

Each generated module creates these permission keys:

- `{module}.view`
- `{module}.create`
- `{module}.edit`
- `{module}.delete`

Example for `User` model: `users.view`, `users.create`, `users.edit`, `users.delete`

Policies are auto-registered in `AuthServiceProvider` when possible. Permission keys are appended to `PermissionSeeder` (requires [spatie/laravel-permission](https://github.com/spatie/laravel-permission) in your host app).

## Configuration

Publish and edit `config/crud-generator.php`:

```php
'admin' => [
    'layout' => 'layouts.admin',
    'route_prefix' => 'admin',
    'route_name_prefix' => 'admin.',
    'middleware' => ['web', 'auth'],
],

'api' => [
    'prefix' => 'api/v1',
    'middleware' => ['api', 'auth:sanctum'],
],

'routes' => [
    'auto_append' => false, // set true or use --routes flag
],
```

## Host App Requirements

Your Laravel application should provide:

1. **Admin layout** — `resources/views/layouts/admin.blade.php` (or change `admin.layout` in config)
2. **Authentication** — for admin middleware and policy checks
3. **Spatie Permission** (recommended) — for `{module}.*` permission keys
4. **API auth** — Sanctum or adjust `api.middleware` in config

## Architecture

```
make:crud (Command)
    └── CrudGeneratorService
            ├── NameParser / FieldParser
            ├── StubRenderer
            └── Generators
                    ├── ModelGenerator
                    ├── MigrationGenerator
                    ├── RequestGenerator
                    ├── RepositoryGenerator
                    ├── PolicyGenerator
                    ├── AdminControllerGenerator
                    ├── ApiControllerGenerator
                    ├── ApiResourceGenerator
                    ├── ViewGenerator
                    ├── RouteGenerator
                    ├── MenuGenerator
                    └── SeederGenerator
```

## Backward Compatibility

- `--web` remains an alias for `--admin`
- Existing output paths are preserved
- Stubs can be published and customized without modifying the package

## License

MIT — see [LICENSE](LICENSE).

## Author

Muhammad Shehroz — [LinkedIn](https://www.linkedin.com/in/muhammadshehroz97/)
