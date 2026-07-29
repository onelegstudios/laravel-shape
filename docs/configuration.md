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

    'icon' => [
        'size' => 'md',
    ],

],
```

The icon has no `color` default on purpose. Every other styling prop names a value; this one's
default is to name nothing, so the icon inherits. See [Icons](icons.md).

With that, `<shape:button>Save</shape:button>` renders a solid primary button, and a call site
that names a prop still wins — config moves the starting point rather than taking the choice
away.

Laravel merges package config one level deep, which means a published copy of this file
replaces the `components` block wholesale rather than being topped up key by key. Deleting a
key is therefore safe but not neutral: the prop falls back to Shape's own default
(`outline` / `neutral` / `md`), not to whatever a later version of the package ships.

## Icons

The `icons` block — which library a set name points at, and the semantic aliases Shape's own
components render through — is documented with the component: [Icons](icons.md).
