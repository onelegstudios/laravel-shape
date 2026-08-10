# Configuration

Every styling prop falls back to a value in `config/shape.php`, so an application states its
house style once instead of repeating it at each call site. Publish the file:

```bash
php artisan vendor:publish --tag="shape-config"
```

## Component Defaults

```php
'components' => [

    'button' => [
        'variant' => 'solid',
        'color' => 'primary',
        'size' => 'md',
    ],

    'input' => [
        'size' => 'md',
        'affix' => 'inline',
    ],

    'select' => ['size' => 'md'],
    'textarea' => ['size' => 'md'],
    'file' => ['size' => 'md'],
    'checkbox' => ['size' => 'md'],
    'radio' => ['size' => 'md'],
    'switch' => ['size' => 'md'],

],
```

Neither of the input's two keys is emphasis. There is no `variant`: an input is not competing for
attention the way a button is, so there is no emphasis ladder to put it on. There is no `color`
either — the only thing an input's colour ever says is whether the value is wrong, and that is
read from the validator rather than named at a call site. `affix` is which of two shapes the ends
of the field have when a `prefix` or a `suffix` is given, `inline` or `segmented`, and it is here
because it is a house style: an application that sets its currency fields on a plate wants all of
them on a plate. It buys nothing on a field with no affix. See
[Components](components/input.md#prefix-and-suffix).

Every other control is on the one size axis, listed separately rather than sharing the input's
key. That is so an application can put its checkboxes on a different rung from its text fields —
a real thing to want in a dense form, where the boxes read fine small and the fields do not — and
its settings-page switches on a different rung again.

The icon takes no defaults from here, because there is no house style to state: an icon's size
follows whatever component it sits in, and the components that take one hand it down already.
Its scale lives in the component with `md` as a literal default, and a call site standing on its
own names the rung it wants. See [Icons](icons.md).

With that, `<shape:button>Save</shape:button>` renders a solid primary button, and a call site
that names a prop still wins — config moves the starting point rather than taking the choice
away.

**These defaults are read when a view is compiled, not when it renders.** Editing this file
works: Shape records it as a dependency of the views that baked it, so they recompile. Setting a
default at runtime — `Config::set('shape.components.button.variant', …)` from a service provider,
or per-tenant config — does not. See [Performance](performance.md#the-config-file-is-read-when-a-view-is-compiled).

Laravel merges package config one level deep, which means a published copy of this file
replaces the `components` block wholesale rather than being topped up key by key. Deleting a
key is therefore safe but not neutral: the prop falls back to Shape's own default
(`outline` / `neutral` / `md`), not to whatever a later version of the package ships.

## Icons

The `icons` block — where published icons live, which library a set name points at, and the
semantic aliases Shape's own components render through — is read by `shape:icon:add` and
`shape:icon:update` when you publish an icon rather than on every render. It is documented with the component:
[Icons](icons.md).

---

[← Theming](theming.md) · [Index](README.md) · [Performance →](performance.md)
