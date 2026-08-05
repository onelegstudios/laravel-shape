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

`icon` puts a mark before the label, and `icon-trailing` puts one after:

```blade
<shape:button icon="check">Save changes</shape:button>

<shape:button icon-trailing="chevron-down">More</shape:button>
```

The button sizes the icon to its own rung, so a `size="xs"` button gets the `xs` icon
without the call site saying so twice — which is the point of the prop, since the pair
getting out of step is otherwise nobody's job to catch. It leaves the icon on the button's
colour, so the mark takes the variant it landed in. Both props are independent and a button
can carry both at once.

Names resolve the same way `<shape:icon>` resolves them, through the `default` set, so the
icon has to have been published — `php artisan shape:icon:add check`. `icon-set` names
another set for both icons at once:

```blade
<shape:button icon="check" icon-set="solid">Save changes</shape:button>
```

Everything a prop cannot say is still the slot's job: an icon with its own colour, its own
classes, or from a different set than the one beside it goes back to a nested
`<shape:icon>`, and nothing about that changed.

An icon and no label is an icon-only button:

```blade
<shape:button icon="trash-2" color="danger" aria-label="Delete" />
```

It squares up to the height its labelled rung already stands at — 24, 32, 36 and 44px — so an
icon button and a text button of the same rung and variant sit level in the same toolbar row,
and `xs` holds the same WCAG 2.5.8 floor with no words to widen it.

**Give it an `aria-label`.** There is no text left to name the button, and Shape will not
invent one: the mark stays hidden from assistive tech, exactly as it is beside a label, so a
button with neither announces as nothing at all. Shape does not throw for a missing one,
because `aria-labelledby` and a `title` are legitimate ways to answer this and a package that
took a page down over an attribute it cannot see is worse than the audit finding.

`loading` is the state a submitting form puts the button in:

```blade
<shape:button variant="solid" color="primary" type="submit" :loading="$saving">
    Save changes
</shape:button>
```

The label goes invisible where it stands — along with any icons — and a spinner takes the
centre, so the button keeps the width it had and the row around it doesn't reflow at the
moment the form is submitted. It
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

Most of the time you want one inside a button, and the button's `icon` prop is the shorter
way to say so. The component is what you reach for when a prop cannot say it — an icon
carrying its own colour or classes, a set different from the one beside it, or a place in the
markup that is not before or after the label:

```blade
<shape:button variant="solid" color="primary">
    <shape:icon name="check" size="sm" />
    Save changes
</shape:button>
```

Note the repeated `size`: nested this way the icon is sized by the call site, and keeping it
in step with the button is yours to remember. The prop does it for you.

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
state resolves its spinner the same way, so a busy button has always had this cost; it is only
now that it sits on the ordinary path rather than a rare one.

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
