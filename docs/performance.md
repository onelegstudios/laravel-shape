# Performance

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

## Folding

The [icon](components/icon.md) goes further and **folds**: a call site that names its icon leaves
no component behind at all, just the `<svg>` inline in the compiled view. That is only safe because
the component reads nothing global — its set and alias table were resolved when the icon was
[published](icons.md#adding-icons), so there is no `config()` call left to freeze. A
dynamic `:name` cannot be resolved at compile time and falls back to the function compiler,
which still skips Blade's component pipeline. That path used to be far more expensive than the
folded one: the icon dispatched through `<x-dynamic-component>`, which resolved a component per
render, and the published default-set forward resolved a second one behind it. It reaches its
artwork with `@include` now, which is a view render rather than two component resolutions — on
an index page of 40 rows, where every checkbox sizes its two marks from a variable and so cannot
fold, that alone was the difference between about 13ms and about 7ms per render.

Folding is still much cheaper than not folding. The gap is now worth a sentence rather than a
warning.

The [button](components/button.md) folds too, and folding it collapses the `icon` prop as a side
effect. The name used to cross a component boundary — a literal where you wrote it, a variable
by the time the icon saw it — so the icon fell back to `<x-dynamic-component>` and a button with
the prop cost roughly nine times a plain one. Folding the button removes the boundary: the icon
is resolved in the same compile pass, so both of these now cost the same nothing.

```blade
<shape:button><shape:icon name="check" size="md" />Save</shape:button>
<shape:button icon="check">Save</shape:button>
```

Prefer the prop. It is shorter, and it is the one that cannot get the icon's size out of step
with the button's.

The `loading` state is the exception. Its spinner carries a translated accessible name, and a
translation resolved at compile time would serve one locale to everybody — so that overlay is
held back to render time and its spinner does not fold. It only exists while the button is busy,
and the usual `:loading="$saving"` is a dynamic prop that declines to fold anyway.

## The config file is read when a view is compiled

This is the one thing folding the button changed about how it behaves, and it is worth being
plain about.

The button's `variant`, `color` and `size` defaults come from `config('shape')`, and a folded
component is evaluated once — when the template calling it is compiled. So those defaults are
baked into the compiled view.

Editing `config/shape.php` still works. Shape records the config file as a dependency of every
view it folded into, and Blaze recompiles a view whose dependencies are newer than its compile,
checked on every render — so it holds under `view:cache` as well.

What no longer reaches the button is a default set at **runtime**:

```php
// Was honoured before; is not now.
Config::set('shape.components.button.variant', 'solid');
```

Per-tenant or per-request styling defaults are the same story — whichever request compiles the
view first decides what every later one gets. If you need defaults that vary at runtime, pass
them at the call site rather than through config.

`memo`, Blaze's third strategy, is unused: it keys on the call site alone and only covers
components without slots, which a button is not.

## What does not fold, and why

**The field components do not fold**, and the reason is a counter rather than config. Every
control (the input, select, textarea, checkbox, radio and file input), along with the
[field](components/field.md), the label, the description and the error, is compiled by Blaze
— but with `@blaze` alone. Folding would bake the sequence Shape uses to invent an id for a
field nobody named, so a loop of unnamed fields would emit the same id and the same `for` for
every row.

These components share a field name through `@aware`, and the two pipelines compile that
directive differently: Blaze walks only a component's ancestors where Blade checks the
component's own data first, and Blaze strips the consumed key from the attribute bag where
Blade leaves it. Neither difference is visible from a call site — the components move onto
Blaze as one family so no `@aware` boundary is ever mixed, and each of them saves the attribute
bag and puts it back around the directive. But it is why the family moves together, and why
adding `@blaze` to one of them on its own is not a safe edit.

If you are rendering hundreds of fields in a loop, the shorthand is still the thing to drop
first — it is five components where the bare `<shape:input>` is two.

Nothing stops you enabling either for your own components, and Blaze can be turned off entirely
with `BLAZE_ENABLED=false` if you want to compare.

## The benchmark

The repository carries a benchmark that measures the strategies against the gallery's own
component markup, on a page compiled once and rendered many times:

```bash
composer bench -- --repeat=10 --renders=60
```

It compares three: Blade's own pipeline, an icon resolved at render time rather than published,
and the components as shipped. On a page of 1,140 components the shipped pipeline renders in
about a sixth of the time Blade's does, and every mode's HTML is checked byte for byte against
the others — a component that quietly declines to fold cannot be reported as a win.

---

[← Configuration](configuration.md) · [Index](README.md) · [Style Guide →](STYLE_GUIDE.md)
