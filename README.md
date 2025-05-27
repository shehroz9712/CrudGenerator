# Shehroz CRUD Generator

A powerful Laravel package to generate complete CRUD operations with roles, permissions, policies, and optional API support. Compatible with Laravel 9, 10, and 11.

## Features
- Generates controllers, models, requests, repositories, interfaces, policies, migrations, seeders, and views.
- Supports nested paths (e.g., `admin/location`).
- Optional API CRUD generation with `--api` or `--both` flags.
- Modern Tailwind-based Blade templates.
- Role-based permissions using Laravel's authorization.
- Compatible with PHP 8.1+ and Laravel 9+.

## Installation
Install the package via Composer:

```bash
composer require shehroz/crud-generator
Publish the stubs (optional):

```bash
php artisan vendor:publish --tag=crud-generator-stubs
Usage
Run the make:crud command to generate CRUD components:

```bash
php artisan make:crud ModelName
Options
--api: Generate only API CRUD (creates API controller and routes).
--both: Generate both web and API CRUDs.
Example
Generate a CRUD for Location in the admin namespace:

```bash
php artisan make:crud admin/location
Generate API-only CRUD:

```bash
php artisan make:crud Product --api
Generate both web and API CRUDs:

```bash
php artisan make:crud Category --both