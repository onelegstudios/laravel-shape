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
which still skips Blade's component pipeline — but it is a much smaller win than folding, not
a near-equal one. A folded icon costs almost nothing; a dynamic one is resolved through
`<x-dynamic-component>` on every render, and in a rough measure of 2,000 icons that was the
difference between about 11ms and about 860ms.

**That is what the button's `icon` prop pays.** `icon="check"` is a literal where you write it,
but the name crosses a component boundary and is a variable by the time the icon sees it, so it
cannot fold. A button with the prop costs roughly nine times a plain button; the same button
with the icon written into the slot costs a few percent, because there the icon is compiled in
your template and folds as usual:

```blade
{{-- Folds. --}}
<shape:button><shape:icon name="check" size="md" />Save</shape:button>

{{-- Shorter, and does not fold. --}}
<shape:button icon="check">Save</shape:button>
```

Write whichever you like — on a page with a handful of buttons the difference is not worth
thinking about, and the prop is the one that cannot get the icon's size out of step with the
button's. On a page rendering hundreds of them in a loop, reach for the slot. The `loading`
state resolves its spinner the same way, so a busy button carries the same cost — and there the
slot is not an escape, because the spinner is Shape's to draw rather than yours.

## What does not fold, and why

The [button](components/button.md) does not fold, and shouldn't: it reads its `variant`, `color`,
and `size` defaults from `config('shape')` on every render. Folding would bake whatever those were
when the view was first compiled, so two identical tags either side of a config change would share
one result — which is the promise the config file exists to make.

`memo`, Blaze's third strategy, is unused for the same reason: it keys on the call site alone,
so it cannot see a config change either.

**The field components opt out of Blaze entirely** — every control (the input, select, textarea,
checkbox, radio and file input) along with the [field](components/field.md), the label, the
description and the error all
stay on Blade's own pipeline. They share a field name through
`@aware`, and Blaze compiles that directive against a data stack of its own that does not agree
with Blade's: it walks only a component's ancestors where Blade checks the component's own data
first, and it strips the key from the attribute bag on the way past. A field compiled by one
and a label by the other would wire themselves to different names, which is the single thing
these components exist to get right. Blaze declines to fold an `@aware` component regardless,
so the strategy that would have paid best was never available to them.

That costs a form the difference between one pipeline and the other, on a component you
render a handful of times per page rather than hundreds. If you are rendering hundreds of
fields in a loop, the shorthand is the thing to drop first — it is five components where the
bare `<shape:input>` is two.

Nothing stops you enabling either for your own components, and Blaze can be turned off entirely
with `BLAZE_ENABLED=false` if you want to compare.

## The benchmark

The repository carries a benchmark that measures all three strategies against the gallery's own
component markup, on a page compiled once and rendered many times:

```bash
composer bench -- --repeat=10 --renders=60
```

It compares four strategies: Blade's own pipeline, an icon resolved at render time
rather than published, the components as shipped, and a foldable button.

---

[← Configuration](configuration.md) · [Index](README.md) · [Style Guide →](STYLE_GUIDE.md)
