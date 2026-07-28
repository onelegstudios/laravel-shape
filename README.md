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

## Styling

Shape requires **Tailwind CSS v4.1** or newer. Import the package's theme in your application
stylesheet:

```css
@import "tailwindcss";
@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";
```

That one line does two things: it defines the semantic colour roles Shape's components
style themselves against, and it tells Tailwind to scan the package's Blade views so the
component classes are actually generated. Skip it and the components render unstyled.

### Theming

The theme has two layers, and you can override either.

**Colour roles** are full `50`–`950` ramps — `primary`, `success`, `warning`, `danger`,
`info`, `neutral` — and the set is open, so you can add your own. Override a role to rebrand;
everything using it follows:

```css
@theme {
    --color-primary-500: var(--color-violet-500);
    --color-primary-700: var(--color-violet-700);
}
```

**Surface tokens** name a purpose and point at a step on those ramps. Components reference
these rather than bare steps, so you can restyle one surface without changing what a ramp
step means everywhere else. To make solid primary buttons a shade darker:

```css
@theme {
    --color-primary-fill: var(--color-primary-800);
    --color-primary-fill-hover: var(--color-primary-900);
}
```

Each role exposes `fill`, `fill-hover`, `on-fill`, `tint`, `tint-hover`, `on-tint`, `border`,
and `ring`. If you would rather own the whole theme, publish it and import your copy instead:

```bash
php artisan vendor:publish --tag="shape-css"
```

### Adding a Colour Role

The colour set is open. Components build their class names from the role at render time, so a
role Shape has never heard of works as soon as its tokens exist — across every variant, in
both colour schemes. This is where a second brand colour goes, and it lives in your
application's stylesheet rather than in the package: the hue is yours, and so is the name.

Define a ramp, the eight surfaces, and — this part is not optional — the safelist:

```css
@theme {
    --color-ocean-50: var(--color-teal-50);
    /* …through 950, from a Tailwind palette or values of your own… */

    --color-ocean-fill: light-dark(var(--color-ocean-700), var(--color-ocean-600));
    --color-ocean-fill-hover: light-dark(var(--color-ocean-800), var(--color-ocean-500));
    --color-ocean-on-fill: var(--color-white);
    --color-ocean-tint: light-dark(var(--color-ocean-50), var(--color-ocean-950));
    --color-ocean-tint-hover: light-dark(var(--color-ocean-100), var(--color-ocean-900));
    --color-ocean-on-tint: light-dark(var(--color-ocean-800), var(--color-ocean-300));
    --color-ocean-border: light-dark(var(--color-ocean-300), var(--color-ocean-800));
    --color-ocean-ring: light-dark(var(--color-ocean-600), var(--color-ocean-500));
}

@source inline("bg-ocean-{fill,tint}");
@source inline("text-ocean-{on-fill,on-tint}");
@source inline("border-ocean-border");
@source inline("hover:bg-ocean-{fill-hover,tint,tint-hover}");
@source inline("focus-visible:outline-ocean-ring");
```

```blade
<shape:button variant="solid" color="ocean">Book a demo</shape:button>
```

Skip the `@source inline()` lines and nothing errors — Tailwind only compiles a utility it has
seen, and it never sees these, so the class lands in your markup and resolves to no style at
all. That block is also why roles are not free: Tailwind keeps any theme variable another kept
variable references, so a role costs its variables whether or not you use it. Shape ships six
because six get used; yours is the one you asked for.

Name it for the colour rather than its rank. `ocean` survives a redesign that reshuffles which
brand colour leads — `secondary` does not, and it collides with `variant`, which is what
already carries emphasis.

Two more things worth knowing before you plan around this. CSS cannot build an identifier out
of a variable, so there is no one-line `--shape-ocean-palette: teal` knob — you name the
palette once per step. And a `-700` fill clears AA under white text for every Tailwind hue
except amber, which is why `warning` fills light with dark text; check yours if it is bright.

### Dark Mode

Dark mode lives entirely in the theme — components carry no `dark:` classes. Surface tokens
use CSS `light-dark()`, which resolves against `color-scheme`. The theme wires that up:

```css
:root  { color-scheme: light dark; }  /* follow the operating system */
.dark  { color-scheme: dark; }        /* force dark for a subtree */
.light { color-scheme: light; }       /* force light for a subtree */
```

**Toggling `.dark` on `<html>`** — the standard Tailwind setup — works with no configuration.

**Using a different hook?** Point it at `color-scheme` yourself:

```css
[data-theme="dark"] { color-scheme: dark; }
```

**Your own `dark:` utilities are a separate mechanism.** Tailwind's `dark:` variant keys off
the OS preference, not off `color-scheme`. If you toggle dark mode with a class *and* write
your own `dark:` utilities — including ones you add to Shape components — you still need:

```css
@custom-variant dark (&:where(.dark, .dark *));
```

**Light-only application?** `color-scheme: light dark` means Shape follows an OS dark
preference, and the browser darkens its own surfaces too — canvas, scrollbars, form controls.
Opt out after the Shape import:

```css
:root { color-scheme: light; }
```

Because a surface token carries both modes in one declaration, restate the function when
overriding one if you want to keep them independent:

```css
@theme {
    --color-primary-fill: light-dark(var(--color-primary-800), var(--color-primary-500));
}
```

## Usage

Shape components are available in your Blade views through the `shape:` tag prefix:

```blade
<shape:button variant="solid" color="primary">Save changes</shape:button>

<shape:button>Cancel</shape:button>
```

Components take three styling props. `variant` sets emphasis — `solid`, `soft`, `ghost`, or
`outline` — and `color` names a semantic role. Both default to the quiet option
(`outline` / `neutral`), so the prominent button on a screen is an explicit choice rather
than the one you get by accident.

The axes are independent, and every combination is valid. `solid` usually carries a
screen's one primary action, but a solid neutral or a soft danger is a perfectly ordinary
thing to want, and nothing here stops you. `color` accepts any role defined in your theme,
not only the ones Shape ships — see [Adding a Colour Role](#adding-a-colour-role).

`size` sets density — `xs`, `sm`, `md`, or `lg`, defaulting to `md`:

```blade
<shape:button size="sm" variant="soft" color="neutral">Filter</shape:button>
```

Use `md` in a form, `sm` or `xs` where a toolbar or table row is tight, and `lg` for a screen
whose single action is the point. Padding, text size, and icon gap change; weight and radius
do not, so a small button is denser without being quieter and every rung answers to the same
`--radius-md`. `xs` stands 24px tall — the smallest target
[WCAG 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html) allows —
and `lg` stands 44px. Anything larger is a landing page rather than an interface; reach for
your own classes there.

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
