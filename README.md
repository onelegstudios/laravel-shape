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

You can install the package via Composer, along with an icon set — Lucide is the one Shape's
config points at out of the box:

```bash
composer require onelegstudios/laravel-shape mallardduck/blade-lucide-icons
```

Icons are published into your application rather than resolved on every render, so publish the
ones you use:

```bash
php artisan shape:icon check close chevron-down
```

The set stays a package you own — swap or remove it with plain Composer, and re-publish. See
[Icons](docs/icons.md) for sets, semantic names, and the full command.

Composer will also pull in [Blaze](https://github.com/livewire/blaze), which compiles Shape's
components into plain PHP functions instead of routing them through Blade's component
pipeline. Unlike the icon set it is a real dependency, because the shipped views carry the
`@blaze` directive and Blade renders an unregistered directive as literal text. Nothing about
using Shape changes. See [Performance](docs/components.md#performance).

Shape requires **Tailwind CSS v4.1** or newer. Import the package's theme in your application
stylesheet:

```css
@import "tailwindcss";
@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";
```

That one line does two things: it defines the semantic colour roles Shape's components
style themselves against, and it tells Tailwind to scan the package's Blade views so the
component classes are actually generated. Skip it and the components render unstyled. See
[Theming](docs/theming.md) for what the theme defines and how to override it.

You may publish all of the package's resources at once:

```bash
php artisan vendor:publish --tag="shape"
```

Or, you may publish each resource individually:

```bash
php artisan vendor:publish --tag="shape-config"
php artisan vendor:publish --tag="shape-views"
php artisan vendor:publish --tag="shape-lang"
php artisan vendor:publish --tag="shape-css"
```

`shape-icons` is deliberately not part of the `shape` bundle — it takes copies of the icons
Shape's own components render, which you only want if you mean to override them.

```bash
php artisan vendor:publish --tag="shape-icons"
```

## Usage

Shape components are available in your Blade views through the `shape:` tag prefix:

```blade
<shape:button variant="solid" color="primary">Save changes</shape:button>

<shape:button size="sm" variant="soft" color="neutral">
    <shape:icon name="check" size="sm" />
    Filter
</shape:button>
```

`variant` sets emphasis, `color` names a semantic role, and `size` sets density. Both button
defaults are the quiet option (`outline` / `neutral`), so the prominent button on a screen is
an explicit choice rather than the one you get by accident — and the defaults are configurable
if that's the wrong starting point for your application.

## Documentation

- [Theming](docs/theming.md) — colour roles, surface tokens, adding a role, dark mode
- [Components](docs/components.md) — the `shape:` prefix and the button's props
- [Icons](docs/icons.md) — publishing icons, sets, semantic names, accessibility, set size
- [Configuration](docs/configuration.md) — component defaults in `config/shape.php`
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
