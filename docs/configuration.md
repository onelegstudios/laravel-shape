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
    ],

    'select' => ['size' => 'md'],
    'textarea' => ['size' => 'md'],
    'file' => ['size' => 'md'],
    'checkbox' => ['size' => 'md'],
    'radio' => ['size' => 'md'],
    'switch' => ['size' => 'md'],

],
```

The input has one key because it has one styling axis. There is no `variant`: an input is not
competing for attention the way a button is, so there is no emphasis ladder to put it on. There
is no `color` either — the only thing an input's colour ever says is whether the value is wrong,
and that is read from the validator rather than named at a call site. See
[Components](components.md#input).

Every other control is on that same one axis, listed separately rather than sharing the input's
key. That is so an application can put its checkboxes on a different rung from its text fields —
a real thing to want in a dense form, where the boxes read fine small and the fields do not — and
its settings-page switches on a different rung again.

The icon takes no defaults from here. It renders published components, and folding those away
at compile time is only safe while the component reads nothing global — a `size` read from
config would be frozen into every compiled view the first time it rendered. Its size scale lives
in the component with `md` as a literal default, and a call site that wants another rung names
it. See [Icons](icons.md).

With that, `<shape:button>Save</shape:button>` renders a solid primary button, and a call site
that names a prop still wins — config moves the starting point rather than taking the choice
away.

Laravel merges package config one level deep, which means a published copy of this file
replaces the `components` block wholesale rather than being topped up key by key. Deleting a
key is therefore safe but not neutral: the prop falls back to Shape's own default
(`outline` / `neutral` / `md`), not to whatever a later version of the package ships.

## Icons

The `icons` block — where published icons live, which library a set name points at, and the
semantic aliases Shape's own components render through — is read by `shape:icon:add` and
`shape:icon:update` when you publish an icon rather than on every render. It is documented with the component:
[Icons](icons.md).
