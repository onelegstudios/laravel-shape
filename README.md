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

Install the package with Composer, then run the installer:

```bash
composer require onelegstudios/laravel-shape
php artisan shape:install
```

`shape:install` imports the theme into your application stylesheet, offers to install an icon
set, and publishes the icons Shape's own components render. It changes nothing it did not
write — a stylesheet that already imports the theme is left alone, and so is a published icon —
so running it again is how you check the first run worked.

Shape requires **Tailwind CSS v4.1** or newer, and Composer pulls in
[Blaze](https://github.com/livewire/blaze) as a real dependency. For the theme import in full, the
icon sets on offer, the installer's flags, and the publish tags, see
[Installation](docs/installation.md).

## Usage

Shape components are available in your Blade views through the `shape:` tag prefix:

```blade
<shape:button variant="solid" color="primary">Save changes</shape:button>

<shape:button size="sm" variant="soft" color="neutral" icon="check">Filter</shape:button>
```

`variant` sets emphasis, `color` names a semantic role, and `size` sets density. Both button
defaults are the quiet option (`outline` / `neutral`), so the prominent button on a screen is
an explicit choice rather than the one you get by accident — and the defaults are configurable
if that's the wrong starting point for your application.

For all components, see [components](docs/components.md); it lists them all, each with its own page.

## Documentation

The [documentation](docs/README.md) reads end to end, and every page links to the next:

- [Installation](docs/installation.md) — the installer, the theme import, an icon set, publish tags
- [Components](docs/components.md) — the `shape:` prefix and a page for each component
- [Icons](docs/icons.md) — publishing icons, sets, semantic names, accessibility, set size
- [Theming](docs/theming.md) — colour roles, surface tokens, page surfaces, adding a role, dark mode
- [Configuration](docs/configuration.md) — component defaults in `config/shape.php`
- [Performance](docs/performance.md) — what Blaze does with these components, and what folds
- [Style Guide](docs/STYLE_GUIDE.md) — the design guidance the components are built to

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
