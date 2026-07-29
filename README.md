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

The set is a separate package on purpose rather than a dependency of Shape's, so it stays yours
to swap or remove. See [Icons](#icons) for what else fits there.

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

The button takes three styling props. `variant` sets emphasis — `solid`, `soft`, `ghost`, or
`outline` — and `color` names a semantic role. Both default to the quiet option
(`outline` / `neutral`), so the prominent button on a screen is an explicit choice rather
than the one you get by accident. If those are the wrong defaults for your application, they
are configurable — see [Component Defaults](#component-defaults).

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

### Icons

Shape's config points at [Lucide](https://lucide.dev) out of the box, so if you installed it
alongside the package there's nothing to wire up:

```blade
<shape:icon name="check" />

<shape:button variant="solid" color="primary">
    <shape:icon name="check" size="sm" />
    Save changes
</shape:button>
```

Shape ships no icons and doesn't read SVGs.
[Blade Icons](https://github.com/blade-ui-kit/blade-icons) does that, and it brings an ecosystem
of sets with it; Shape adds the layer above — one name for an icon across sets, the button's
size scale, and an accessibility default. Lucide is a default, not a dependency: Shape doesn't
require it, so the set stays a package you own and can swap with plain Composer. Without one
installed, an `<shape:icon>` raises Blade Icons' `SvgNotFound`.

`size` uses the same four rungs as the button — `xs`, `sm`, `md`, `lg`, defaulting to `md` —
so a `sm` icon in a `sm` button is the obvious thing to write rather than a lookup.

**Colour is inherited, not set.** An icon carries no colour class, so it takes the colour of
whatever it sits inside: put one in a solid danger button and it comes out white, with nothing
to configure. `color` is for the icon that stands alone and carries meaning by itself, and it
names the same semantic roles the button does, including your own:

```blade
<shape:icon name="circle-check" color="success" label="Passed" />
```

#### Icon Sets

The `set` prop names a set; `config/shape.php` decides which library that is:

```php
'icons' => [

    'set' => 'lucide',

    'sets' => [
        'lucide' => 'lucide',
    ],

],
```

That indirection is the point. Views say `set="solid"`; config says what `solid` means. Moving
an application from one icon library to another is an edit in one file rather than a
find-and-replace across every view. Swapping Lucide for Heroicons, start to finish:

```bash
composer remove mallardduck/blade-lucide-icons
composer require blade-ui-kit/blade-heroicons
php artisan vendor:publish --tag="shape-config"
```

```php
// config/shape.php
'set' => 'outline',

'sets' => [
    'outline' => 'heroicon-o',
    'solid' => 'heroicon-s',
    'brand' => 'app',        // a directory set of your own, registered with Blade Icons
],
```

No views change, and nothing of Lucide's is left behind — Shape never required it, so it's
yours to remove.

```blade
<shape:icon name="check" />                    {{-- heroicon-o-check --}}
<shape:icon name="check" set="solid" />        {{-- heroicon-s-check --}}
<shape:icon name="logomark" set="brand" />     {{-- app-logomark --}}
```

A value in `sets` is a Blade Icons **name prefix**, which is not always the same as a Blade
Icons *set*: `blade-heroicons` registers one set, `heroicon`, and keeps the weight in the
filename — so `heroicon-o` and `heroicon-s` are two entries pointing into it. A name that
isn't listed is used as a prefix as it stands, so `set="heroicon-o"` works without being
registered first; a typo raises Blade Icons' `SvgNotFound` naming the prefix it tried, rather
than quietly serving an icon from the default set.

To register a directory of your own SVGs, use Blade Icons' own config — Shape maps onto sets,
it doesn't replace how they're declared:

```bash
php artisan vendor:publish --tag=blade-icons
```

```php
// config/blade-icons.php
'sets' => [
    'app' => [
        'path' => 'resources/svg',
        'prefix' => 'app',
    ],
],
```

#### Semantic Names

Shape's own components can't name `x` or `x-mark` directly — the package has no idea which
library you installed. They ask for `close`, and `config/shape.php` maps it:

```php
'aliases' => [
    'check' => 'check',
    'chevron-down' => 'chevron-down',
    'close' => 'x',            // Heroicons calls this 'x-mark'
    'spinner' => 'loader-circle',
],
```

Swapping sets means remapping these few names rather than forking a view. Aliases resolve
before the prefix is applied, and an unaliased name passes straight through — so your own call
sites keep using real icon names and this doesn't become a second vocabulary to learn. One
table serves every set; Shape's components render in the default one, which is the case it
exists for.

Those four are examples for now — no Shape component renders an icon yet, so nothing depends
on them. They show the shape of the table and name what the first components will reach for;
prune what you don't use, and expect the list to fill in as components arrive.

#### Accessibility

Icons are decorative by default and get `aria-hidden="true"`, because most of them repeat a
label that's already beside them. Pass `label` for the icon that *is* the content — an
icon-only button — and it renders as `role="img"` with an `aria-label` instead:

```blade
<shape:button aria-label="Dismiss"><shape:icon name="close" /></shape:button>

<shape:icon name="circle-x" color="danger" label="Failed" />
```

#### Missing Icons

An icon Blade Icons can't find throws `SvgNotFound`, which is what you want in development. If
you'd rather a production page degraded than 500'd, that's Blade Icons' `fallback` config —
Shape doesn't add a second mechanism for it.

#### A Word on Set Size

Icon sets are large — Lucide is around 2,000 SVGs, Heroicons about 1,300 — and Blade Icons
registers a Blade component for every icon in every set so that `<x-lucide-check />` works.
That means a directory scan and a couple of thousand component registrations per request. This
is a property of the set rather than anything Shape adds, but it's the kind of thing that's
easier to know up front than to find in a profiler.

None of that work is Shape's: `<shape:icon>` asks Blade Icons for one file by name and never
touches the manifest. The scan and the registrations are for `<x-lucide-check />`-style
components, and Blade Icons sets them up whenever a view renders, whether you use them or not.

So if you don't write those yourself, switch them off and both costs disappear:

```php
// config/blade-icons.php
'components' => [
    'disabled' => true,
],
```

The name oversells it: that flag disables the generated per-icon tags, not icon components in
general. `@svg('lucide-check')` and `<x-icon name="lucide-check" />` go through the same
by-name lookup Shape uses and keep working — only the `<x-lucide-check />` shorthand goes. Shape
doesn't set it for you regardless, because it's another package's config and Shape can't see
which markup you've written.

What it doesn't reclaim is disk: the set is still in `vendor/`, which matters for image sizes
and not for requests. Removing the package is the only thing that fixes that, and swapping sets
is [three commands](#icon-sets).

If you do use them, cache the manifest in production instead:

```bash
php artisan icons:cache
```

That removes the directory scan, which is the expensive half. The per-icon registrations still
happen on every request — the manifest tells Blade Icons what to register, not whether to.

### Component Defaults

Every styling prop falls back to a value in `config/shape.php`, so an application states its
house style once instead of repeating it at each call site. Publish the file:

```bash
php artisan vendor:publish --tag="shape-config"
```

```php
'components' => [

    'button' => [
        'variant' => 'solid',
        'color' => 'primary',
        'size' => 'md',
    ],

    'icon' => [
        'size' => 'md',
    ],

],
```

The icon has no `color` default on purpose. Every other styling prop names a value; this one's
default is to name nothing, so the icon inherits. See [Icons](#icons).

With that, `<shape:button>Save</shape:button>` renders a solid primary button, and a call site
that names a prop still wins — config moves the starting point rather than taking the choice
away.

Laravel merges package config one level deep, which means a published copy of this file
replaces the `components` block wholesale rather than being topped up key by key. Deleting a
key is therefore safe but not neutral: the prop falls back to Shape's own default
(`outline` / `neutral` / `md`), not to whatever a later version of the package ships.

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
