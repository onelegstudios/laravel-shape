# Icons

Shape's config points at [Lucide](https://lucide.dev) out of the box, so if you installed it
alongside the package there's nothing to wire up:

```blade
<shape:icon name="check" />

<shape:button variant="solid" color="primary">
    <shape:icon name="check" />
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

## Icon Sets

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

## Semantic Names

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

## Accessibility

Icons are decorative by default and get `aria-hidden="true"`, because most of them repeat a
label that's already beside them. Pass `label` for the icon that *is* the content — an
icon-only button — and it renders as `role="img"` with an `aria-label` instead:

```blade
<shape:button aria-label="Dismiss"><shape:icon name="close" /></shape:button>

<shape:icon name="circle-x" color="danger" label="Failed" />
```

## Missing Icons

An icon Blade Icons can't find throws `SvgNotFound`, which is what you want in development. If
you'd rather a production page degraded than 500'd, that's Blade Icons' `fallback` config —
Shape doesn't add a second mechanism for it.

## A Word on Set Size

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
