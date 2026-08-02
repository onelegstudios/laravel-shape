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

],
```

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
semantic aliases Shape's own components render through — is read by `shape:icon:add` when you
publish an icon rather than on every render. It is documented with the component:
[Icons](icons.md).
