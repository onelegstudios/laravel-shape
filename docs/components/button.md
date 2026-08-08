# Button

The one component in Shape that is not a form control, and the one with an emphasis ladder.

The button takes three styling props. `variant` sets emphasis — `solid`, `soft`, `ghost`, or
`outline` — and `color` names a semantic role. Both default to the quiet option
(`outline` / `neutral`), so the prominent button on a screen is an explicit choice rather
than the one you get by accident. If those are the wrong defaults for your application, they
are configurable — see [Configuration](../configuration.md).

The axes are independent, and every combination is valid. `solid` usually carries a
screen's one primary action, but a solid neutral or a soft danger is a perfectly ordinary
thing to want, and nothing here stops you. `color` accepts any role defined in your theme,
not only the ones Shape ships — see [Adding a Colour Role](../theming.md#adding-a-colour-role).

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

## Icons

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
[`<shape:icon>`](icon.md).

## Icon-only

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

## Loading

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
another name in [Configuration](../configuration.md) and the button follows.

---

[← Components](../components.md) · [Index](../README.md) · [Input →](input.md)
