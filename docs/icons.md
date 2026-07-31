# Icons

Shape resolves an icon once, when you publish it, rather than on every render. Install a set,
publish the icons you use, and write them the same way you always would:

```bash
composer require mallardduck/blade-lucide-icons
php artisan shape:icon check close chevron-down
```

```blade
<shape:icon name="check" />

<shape:button variant="solid" color="primary">
    <shape:icon name="check" />
    Save changes
</shape:button>
```

Publishing writes each icon into your application as a small Blade component holding the SVG.
Nothing is looked up at render time — no set lookup, no alias table, no file read — which is
what lets [Blaze](https://github.com/livewire/blaze) fold an icon away entirely, leaving the
`<svg>` inline in the compiled view. On a page dense with icons that is the single largest
saving available, and it is why the indirection moved to publish time instead of being dropped.

[Blade Icons](https://github.com/blade-ui-kit/blade-icons) still does the reading, and its
ecosystem of sets is still what you install — but only `shape:icon` talks to it. Once an icon
is published, the set package is no longer on the render path at all.

## Publishing Icons

```bash
php artisan shape:icon check                    # one icon from the default set
php artisan shape:icon check close settings     # several at once
php artisan shape:icon check --set=solid        # from a set you configured
php artisan shape:icon --all                    # everything the set contains
php artisan shape:icon check --force            # overwrite what is already there
php artisan shape:icon check --no-clear         # leave compiled views alone
```

| Option | What it does |
| --- | --- |
| `--set=` | Which configured set to take the icons from. Defaults to `icons.set`. |
| `--all` | Publish every icon the set contains, instead of naming them. |
| `--force` | Overwrite icons that are already published. Without it they are skipped. |
| `--no-clear` | Skip the compiled-view clear, for scripting many publishes before one clear. |

An icon that is already published is skipped rather than overwritten, so re-running the command
to add one more is safe and will not undo an edit you made by hand.

Publishing clears your compiled views, and it has to: folding copies an icon's markup into every
compiled view that renders it, and editing the published file does not invalidate those. Without
the clear a re-published icon keeps serving the old artwork. `--no-clear` is there for the script
that publishes fifty icons and would rather clear once at the end.

## Where They Land

Icons are published into a directory per set:

```
resources/views/vendor/shape-icons/
    default/
        check.blade.php
        close.blade.php
    lucide/
        check.blade.php
        close.blade.php
    solid/
        check.blade.php
```

A directory per set, rather than one flat pile, because two sets sharing a name is the normal
case and not an edge one: Heroicons outline and solid have the same names for nearly every
icon, and you would reasonably want both. Nesting them makes a collision impossible rather than
something the command has to notice and warn you about.

`default/` holds a one-line component forwarding to whichever set `icons.set` names. That is
what lets `<shape:icon name="check" />` find an icon without the component reading config —
a config read would be frozen into every compiled view the first time it rendered, which is
exactly what folding cannot survive. The forward folds away too, so it costs nothing.

Change the directory with `icons.path` in `config/shape.php` if `resources/views/vendor` is not
where you want them.

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

That indirection is still the point, it just resolves when you publish rather than when you
render. Views say `set="solid"`; config says what `solid` means. Swapping Lucide for Heroicons,
start to finish:

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

```bash
php artisan shape:icon --all --force
```

No views change. The re-publish is the step that replaces the old find-and-replace, and it is
the one thing this design asks of you that the runtime lookup did not.

```blade
<shape:icon name="check" />                    {{-- heroicon-o-check --}}
<shape:icon name="check" set="solid" />        {{-- heroicon-s-check --}}
<shape:icon name="logomark" set="brand" />     {{-- app-logomark --}}
```

A value in `sets` is a Blade Icons **name prefix**, which is not always the same as a Blade
Icons *set*: `blade-heroicons` registers one set, `heroicon`, and keeps the weight in the
filename — so `heroicon-o` and `heroicon-s` are two entries pointing into it. A name that isn't
listed is used as a prefix as it stands, so `--set=heroicon-o` works without being registered
first; a typo fails at the command naming the prefix it tried, rather than quietly publishing
from the default set.

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

Aliases resolve when an icon is published, and the file is named for what your views ask for
rather than what the set calls it: `php artisan shape:icon close` writes `close.blade.php`
holding Lucide's `x`. Swapping libraries is remapping these few names and re-publishing; no call
site moves.

An unaliased name passes straight through, so your own call sites keep using real icon names and
this doesn't become a second vocabulary to learn.

## Size and Colour

`size` uses the same four rungs as the button — `xs`, `sm`, `md`, `lg`, defaulting to `md` — so
a `sm` icon in a `sm` button is the obvious thing to write rather than a lookup.

The default is a literal in the component rather than a config value, for the same reason the
set is resolved at publish time: a `config()` read would cost folding. A call site that wants
another rung names it.

**Colour is inherited, not set.** An icon carries no colour class, so it takes the colour of
whatever it sits inside: put one in a solid danger button and it comes out white, with nothing
to configure. `color` is for the icon that stands alone and carries meaning by itself, and it
names the same semantic roles the button does, including your own:

```blade
<shape:icon name="circle-check" color="success" label="Passed" />
```

## Accessibility

Icons are decorative by default and get `aria-hidden="true"`, because most of them repeat a
label that's already beside them. Pass `label` for the icon that *is* the content — an
icon-only button — and it renders as `role="img"` with an `aria-label` instead:

```blade
<shape:button aria-label="Dismiss"><shape:icon name="close" /></shape:button>

<shape:icon name="circle-x" color="danger" label="Failed" />
```

The published files deliberately carry no accessibility attributes of their own. Blade's
`merge` can add an attribute but never take one away, so an icon that hid itself could never be
unhidden by a `label` above it.

## Missing Icons

An icon you never published fails the way any other typo'd Blade component does, naming what it
looked for:

```
Unable to locate a class or view for component [shape-icon::default.chek].
```

That is the trade this design makes, and it is the better half of it: the failure lands the
first time you load the page in development, rather than at runtime in production or silently
from the wrong set. A name built at runtime — `<shape:icon :name="$status->icon" />` — is the
one case that can still surprise you, since nothing can know at publish time which branch you
will hit. Publish those explicitly.

## Writing Icons Directly

`<shape:icon>` is the ergonomic form: it applies the size scale, the colour role, and the
accessibility default. A published icon can also be written straight out, which skips all three
and folds unconditionally — even with a dynamic class, because `class` is passed through rather
than used in logic:

```blade
<x-shape-icon::lucide.check class="size-4" />
```

Reach for it where you are rendering an icon thousands of times and have already settled its
size in a class. Everywhere else `<shape:icon>` folds too, as long as the name is written at the
call site.

## Overriding a Packaged Icon

Shape ships the icons its own components render. Your published directory is searched first, so
publishing an icon under the same name replaces the packaged one — no registry, no precedence
setting, just the filename:

```bash
php artisan vendor:publish --tag="shape-icons"    # take copies of Shape's own
```

They are deliberately not part of the `shape` bundle: publishing every resource at once should
not quietly make you the owner of icons the package would otherwise keep current for you.

## Keeping Icons Current

A published icon stops tracking the set it came from, which is the ordinary bargain for
published assets and the one real cost here. After upgrading an icon package, re-publish to pick
up redrawn artwork:

```bash
php artisan shape:icon --all --force
```

Each published file records which set and which name it came from, so you can always tell what a
given icon is:

```blade
{{-- lucide-check -- published from set "lucide" by `php artisan shape:icon`.
     Re-run with --force to pick up redrawn artwork after a set upgrade. --}}
```

## A Word on Set Size

Icon sets are large — Lucide is around 2,000 SVGs, Heroicons about 1,300 — and Blade Icons
registers a Blade component for every icon in every set so that `<x-lucide-check />` works. That
means a directory scan and a couple of thousand component registrations per request.

None of that is on Shape's render path any more: published icons are ordinary Blade files, and
the set package is only consulted by `shape:icon`. If you don't write `<x-lucide-check />`-style
tags yourself, switch them off and both costs disappear:

```php
// config/blade-icons.php
'components' => [
    'disabled' => true,
],
```

The name oversells it: that flag disables the generated per-icon tags, not icon components in
general. `@svg('lucide-check')` and `<x-icon name="lucide-check" />` keep working — only the
`<x-lucide-check />` shorthand goes.

Because nothing reads the set at runtime, you can also move it to `require-dev` and keep it out
of production entirely. Weigh that against the fact that it becomes a build-time dependency:
anyone who runs `shape:icon` needs it installed, and `composer install --no-dev` on a machine
that publishes icons will not have it.
