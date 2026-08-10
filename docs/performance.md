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

## The validation message folds, and reads the bag anyway

The [error](components/field.md) is the one field component that folds, and it is the only one
whose correctness depends on that being done carefully. What the validator said is the most
per-request thing in the package, and so is whether it said anything at all — a folded copy
evaluated once at compile time, when no error bag exists, would report every field clean for
ever, with markup that looks perfectly correct.

So the whole element, not just the sentence inside it, is held back to render time in an island.
The name and the id are settled when the view is compiled; the lookup happens on every render, as
it always did. Blaze enforces the split rather than trusting it: a component that reads the error
bag outside an island is refused the fold outright.

Three shapes of call site decline the fold instead, and all three still render exactly what they
rendered before:

```blade
<shape:error name="email">That address is taken.</shape:error>  {{-- words of its own --}}
<shape:error :name="$field" />                                  {{-- a bound name --}}
<shape:error name="email"></shape:error>                        {{-- anything in the slot --}}
```

One limitation comes with it. A message with no name of its own takes one from the field around
it, and that inheritance is resolved when the view is compiled — so the enclosing
`<shape:field>` has to be visible in the same template:

```blade
{{-- Folds, and finds `email`. --}}
<shape:field name="email">
    <shape:error />
</shape:field>

{{-- Folds against no name at all, and renders nothing. --}}
<shape:field name="email">
    @include('partials.control')  {{-- a bare <shape:error /> in here --}}
</shape:field>
```

Give the message a name of its own if you need to split a field across templates — a bound
`:name` declines the fold, and a literal one folds correctly.

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

## A field folds when you write it out

The [field](components/field.md) folds too, and folding it takes the label, the description and
the legend with it — none of which carry the directive themselves. A fold *renders* the
component, so everything the field draws is executed at compile time and written into your
template as plain HTML:

```blade
<shape:field name="email" label="Email">
    <shape:input />
</shape:field>
```

compiles the `<div>` and the `<label for="email">` to literal markup. The control in the slot is
left as a call — it reads the error bag and may need an id of its own — and so is the message,
which keeps its island.

**The shorthand does not fold**, and that is the ceiling on this. `<shape:input label="Email">`
builds its field by passing values `Control::resolve()` settles at render, so the call is bound
and declines. On a page of hand-written fields folding the wrapper is worth about a fifth of the
render; on a page of shorthand it is worth nothing.

That sharpens the advice below rather than changing it: writing a field out was already the
cheaper of the two, and it now folds its wrapper as well.

## The header folds too

All four parts — the [header](components/header.md), the brand, the nav and the item — fold, so a
bar written with literal attributes leaves no components behind at all. The rung still reaches the
items: Blaze wraps a folded call site whose children read `@aware` in a push of the attributes
written on the tag, so `<shape:header size="lg">` sizes the items inside it as it always did. Bind
the rung at either end and the items decline instead of folding against a value nobody knows yet.

The nav's landmark name is translated, and a translation resolved at compile time would serve one
locale to everybody — so that lookup is held back to render time in an island, exactly as the
button's spinner label is.

## What does not fold, and why

**The controls do not fold** — the input, select, textarea, checkbox, radio, file input, switch,
range and colour input, along with the label and the description. They are compiled by Blaze, but
with `@blaze` alone.

For all of them the obstacle is `@aware`, and it takes two forms.

The label and the description inherit the field's name, and inheritance is resolved from the
enclosing tags the compiler can see rather than from the render stack — so a `<shape:label>` that
folded in a template of its own would lose the `for` it never looked up. Left alone they are
executed inside a folded field instead, where the stack does exist, which is how they end up
baked without the hazard.

The controls hit something sharper. Blaze folds an `@aware` component by merging the inherited
value into the component's own *attribute bag*, and a control's one unanswerable question is
whether its name was written on the tag or came from the field around it — which is exactly what
that merge erases. A checkbox in a group would start printing the field's message again under
every box, and a `wire:model` control would pick up a `name` attribute its binding meant to
replace. Both are well-formed markup, which is what makes them worth refusing rather than
shipping.

Inheriting through `@aware` is not itself the problem, which the header above is the proof of: a
nav item inherits its rung the same way and folds anyway. What separates them is what the
component does with the answer. An item only needs the *value* — a rung written on the tag and one
handed down from the bar mean the same thing — while a control branches on where the value came
from.

Two things that *used* to keep the controls out no longer do, and both changed to get them
closer:

- The error bag is read from an island, the way the message reads it, rather than from a `@php`
  block. `aria-invalid` and the message's place in `aria-describedby` are settled per render;
  the colour follows `aria-invalid` through the `invalid:` and `has-invalid:` variants described
  in [theming](theming.md) instead of being chosen in PHP.
- The counter behind a generated id is gone. A labelled control that nothing named used to take
  the next number off a process-wide sequence, which made compiled output depend on the order an
  application compiled its templates in. It derives from the label now — `label="Email address"`
  gives `shape-field-email-address` — so the same tag always renders the same id. The cost is
  that two such controls sharing a label share an id, exactly as two controls sharing a *name*
  always have; a labelled control with no name, no binding and no id of its own submits nothing
  either way.

These components share a field name through `@aware`, and the two pipelines compile that
directive differently: Blaze walks only a component's ancestors where Blade checks the
component's own data first, and Blaze strips the consumed key from the attribute bag where
Blade leaves it. Neither difference is visible from a call site — the components move onto
Blaze as one family so no `@aware` boundary is ever mixed, and each of them saves the attribute
bag and puts it back around the directive. But it is why the family moves together, and why
adding `@blaze` to one of them on its own is not a safe edit.

If you are rendering hundreds of fields in a loop, the shorthand is still the thing to drop
first — it is five components where the bare `<shape:input>` is two, and writing the field out
folds the wrapper on top of that.

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
