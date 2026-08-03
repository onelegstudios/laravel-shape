# Components

Shape components are available in your Blade views through the `shape:` tag prefix:

```blade
<shape:button variant="solid" color="primary">Save changes</shape:button>

<shape:button>Cancel</shape:button>
```

Attributes are forwarded to the underlying component, so you can style and extend components
as you would any Blade component. The same components are also reachable through Laravel's
standard namespaced syntax if you prefer it:

```blade
<x-shape::button>Save</x-shape::button>
```

## Button

The button takes three styling props. `variant` sets emphasis — `solid`, `soft`, `ghost`, or
`outline` — and `color` names a semantic role. Both default to the quiet option
(`outline` / `neutral`), so the prominent button on a screen is an explicit choice rather
than the one you get by accident. If those are the wrong defaults for your application, they
are configurable — see [Configuration](configuration.md).

The axes are independent, and every combination is valid. `solid` usually carries a
screen's one primary action, but a solid neutral or a soft danger is a perfectly ordinary
thing to want, and nothing here stops you. `color` accepts any role defined in your theme,
not only the ones Shape ships — see [Adding a Colour Role](theming.md#adding-a-colour-role).

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

`loading` is the state a submitting form puts the button in:

```blade
<shape:button variant="solid" color="primary" type="submit" :loading="$saving">
    Save changes
</shape:button>
```

The label goes invisible where it stands and a spinner takes the centre, so the button keeps
the width it had and the row around it doesn't reflow at the moment the form is submitted. It
also disables itself — a second click on a button that is already working is the bug this
state exists to prevent — and sets `aria-busy="true"`, announcing as "Loading" while the
hidden label is out of the accessibility tree. The usual disabled fade is dropped for the
duration: the spinner is the signal, and a faded one reads as a button that has given up.

The spinner is a published icon rather than one Shape ships, so
`php artisan shape:icon:add spinner` has to have run — `shape:install` does it for you. It
resolves through the `spinner` alias, which means the artwork is yours to choose: point it at
another name in [Configuration](configuration.md) and the button follows.

## Icon

Icons have their own page: [Icons](icons.md).

```blade
<shape:button variant="solid" color="primary">
    <shape:icon name="check" />
    Save changes
</shape:button>
```

## Performance

Shape's components carry Blaze's `@blaze` directive, which compiles them into plain PHP
functions and calls those directly, skipping Blade's component pipeline. It is a drop-in
optimisation: identical HTML, no change to how you write a call site. Blaze arrives as a
dependency of Shape, so there is nothing to install or configure.

If you publish the views, the directive is published with them and keeps working. Blade caches
compiled templates, though, so clear them after publishing — or after any upgrade that changes
a component:

```bash
php artisan view:clear
```

The icon goes further and **folds**: a call site that names its icon leaves no component behind
at all, just the `<svg>` inline in the compiled view. That is only safe because the component
reads nothing global — its set and alias table were resolved when the icon was
[published](icons.md#publishing-icons), so there is no `config()` call left to freeze. A
dynamic `:name` cannot be resolved at compile time and falls back to the function compiler,
which is a smaller win rather than a failure.

The button does not fold, and shouldn't: it reads its `variant`, `color`, and `size` defaults
from `config('shape')` on every render. Folding would bake whatever those were when the view was
first compiled, so two identical tags either side of a config change would share one result —
which is the promise the config file exists to make.

`memo`, Blaze's third strategy, is unused for the same reason: it keys on the call site alone,
so it cannot see a config change either.

Nothing stops you enabling either for your own components, and Blaze can be turned off entirely
with `BLAZE_ENABLED=false` if you want to compare.

The repository carries a benchmark that measures all three strategies against the gallery's own
component markup, on a page compiled once and rendered many times:

```bash
composer bench -- --repeat=10 --renders=60
```

It compares four strategies: Blade's own pipeline, the icon as it was before
icons were published, the components as shipped, and a foldable button.
