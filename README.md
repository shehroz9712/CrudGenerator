# Shehroz CRUD Generator

A powerful Laravel package to generate complete CRUD operations with roles, permissions, policies, and optional API support. Compatible with Laravel 9, 10, and 11.

## Features
- Generates controllers, models, requests, repositories, interfaces, policies, migrations, seeders, and views.
- Supports nested paths (e.g., `admin/location`).
- Optional API CRUD generation with `--api` or `--both` flags.
- Modern Tailwind-based Blade templates for web interfaces.
- Role-based permissions using Laravel's authorization system.
- Compatible with PHP 8.1+ and Laravel 9, 10, and 11.

## Installation

Install the package via Composer:

```bash
composer require shehroz/crud-generator
```

Publish the stubs (optional, to customize templates):

```bash
php artisan vendor:publish --tag=crud-generator-stubs
```

## Usage

Run the `make:crud` command to generate CRUD components:

```bash
php artisan make:crud ModelName
```

### Options
- `--api`: Generates only API CRUD (API controller and routes).
- `--both`: Generates both web and API CRUDs.

### Examples

Generate a CRUD for `Location` in the `admin` namespace:

```bash
php artisan make:crud admin/location
```

Generate an API-only CRUD for `Product`:

```bash
php artisan make:crud Product --api
```

Generate both web and API CRUDs for `Category`:

```bash
php artisan make:crud Category --both
```

## Generated Files
- **Controller**: `App\Http\Controllers\[Namespace]\ModelNameController.php`
- **API Controller**: `App\Http\Controllers\Api\[Namespace]\ModelNameController.php` (with `--api` or `--both`)
- **Model**: `App\Models\[Namespace]\ModelName.php`
- **Request**: `App\Http\Requests\[Namespace]\ModelNameRequest.php`
- **Repository**: `App\Repositories\[Namespace]\ModelNameRepository.php`
- **Interface**: `App\Repositories\Interfaces\[Namespace]\ModelNameRepositoryInterface.php`
- **Policy**: `App\Policies\[Namespace]\ModelNamePolicy.php`
- **Migration**: `database/migrations/YYYY_MM_DD_HHMMSS_create_model_table.php`
- **Seeder**: `database/seeders/ModelNameSeeder.php`
- **Views**: `resources/views/[namespace]/modelname/*.blade.php` (for web CRUD)

## Requirements
- PHP: ^8.1|^8.2|^8.3
- Laravel: ^9.0|^10.0|^11.0

## License
This package is open-sourced under the [MIT license](LICENSE).

## Author
Muhammad Shehroz - [LinkedIn](https://www.linkedin.com/in/muhammadshehroz97/)