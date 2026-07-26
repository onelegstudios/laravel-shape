<div align="center">
    <h1>Shape</h1>
</div>

<p align="center">
    <a href="https://packagist.org/packages/onelegstudios/laravel-shape"><img src="https://img.shields.io/packagist/v/onelegstudios/laravel-shape.svg?style=flat-square" alt="Packagist"></a>
    <a href="https://packagist.org/packages/onelegstudios/laravel-shape"><img src="https://img.shields.io/packagist/php-v/onelegstudios/laravel-shape.svg?style=flat-square" alt="PHP from Packagist"></a>
    <a href="https://packagist.org/packages/onelegstudios/laravel-shape"><img src="https://badge.laravel.cloud/badge/onelegstudios/laravel-shape?style=flat" alt="Laravel versions"></a>
    <a href="https://github.com/onelegstudios/laravel-shape/actions"><img alt="GitHub Workflow Status (main)" src="https://img.shields.io/github/actions/workflow/status/onelegstudios/laravel-shape/tests.yml?branch=main&label=Tests&style=flat-square"></a>
    <a href="https://packagist.org/packages/onelegstudios/laravel-shape"><img src="https://img.shields.io/packagist/dt/onelegstudios/laravel-shape.svg?style=flat-square" alt="Total Downloads"></a>
</p>

Shape the interface. Predictable UI components for Laravel and Livewire.

## Installation

You can install the package via Composer:

```bash
composer require onelegstudios/laravel-shape
```

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="shape"
```

Or, you may publish each resource individually:

### Publishing the Configuration File

```bash
php artisan vendor:publish --tag="shape-config"
```

### Publishing the Views

```bash
php artisan vendor:publish --tag="shape-views"
```

### Publishing the Translations

```bash
php artisan vendor:publish --tag="shape-lang"
```

## Usage

Shape components are available in your Blade views through the `shape:` tag prefix:

```blade
<shape:button class="btn-primary">Save</shape:button>

<shape:button type="submit" />
```

Attributes are forwarded to the underlying component, so you can style and extend components as you would any Blade component. The same components are also reachable through Laravel's standard namespaced syntax if you prefer it:

```blade
<x-shape::button>Save</x-shape::button>
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Thank you for considering contributing to Shape! Please review our [contributing guide](.github/CONTRIBUTING.md) to get started.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [Henrik Persson](https://github.com/onelegstudios)
- [All Contributors](../../contributors)

## License

Shape is open-sourced software licensed under the [MIT license](LICENSE.md).
