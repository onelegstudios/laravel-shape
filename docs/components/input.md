# Input

The text field, and the component every other control in the family is measured against.

```blade
<shape:input label="Email" description="We never share it." wire:model="email" />
```

That renders a label, the control, the help text, and — when the validator has something to say
about `email` — the message, with the label pointed at the control and `aria-describedby`
pointing at the parts that exist. Everything the shorthand cannot say goes back to the parts
themselves, and those are [Field](field.md)'s subject: `<shape:field>`, `<shape:label>`,
`<shape:legend>`, `<shape:description>` and `<shape:error>`, held together by naming the field
once.

`size` sets density — `xs`, `sm`, `md`, or `lg`, defaulting to `md`, and configurable in
[Configuration](../configuration.md). The rungs are the [button](button.md)'s own, so a field and
the button that submits it stand level in the same row:

```blade
<shape:input size="sm" placeholder="Search orders" />
<shape:button size="sm" variant="solid" color="primary">Search</shape:button>
```

There is no `variant` and no `color`. An input is not competing for attention the way a button
is, so there is no emphasis ladder to put it on — and the only thing an input's colour ever
says is whether the value is wrong, which is read rather than named.

## Invalid

The input looks itself up in the validation error bag and styles itself accordingly, setting
`aria-invalid` at the same time. It finds its own name in the `name` attribute, in whatever
`wire:model` is bound to — modifiers and all, so `wire:model.live.debounce.300ms` resolves —
or in the field around it, in that order.

```blade
{{-- Nothing here says "invalid". A failed request is enough. --}}
<shape:input label="Email" wire:model="email" />
```

`invalid` overrides that in both directions: `:invalid="true"` marks a field the validator has
not seen yet, and `:invalid="false"` clears one it has.

A **bare** input — no label, no description — takes the styling and the `aria-invalid` but
prints no message. There is no field around it to put a sentence in, and an application writing
its own label is almost certainly writing its own error too. Add one with `<shape:error>`, or
let the shorthand do it.

The message carries a mark as well as its colour, so a long form shows where it failed without
being read end to end. It resolves through the `error` alias — `circle-alert` in Lucide,
`exclamation-circle` in Heroicons — so the artwork is yours to change in
[Configuration](../configuration.md), and it is hidden from assistive tech because the sentence
beside it already says the same thing. That sentence, not the mark and not the colour, is what
carries the meaning for a reader who cannot see either.

Like the button's spinner, it has to have been published. `shape:install` does it for you;
`php artisan shape:icon:add error` is the manual form, `shape:icon:check` reports it missing —
and fails `--strict`, so CI can catch it — and `shape:icon:remove` will not take it away without
`--force`.

## Icons

`icon` puts a mark at the start of the field and `icon-trailing` puts one at the end, sized to
the field's own rung and resolved exactly the way [`<shape:icon>`](icon.md) resolves them — so the
name has to have been published (`php artisan shape:icon:add search`). `icon-set` names another set
for both at once.

```blade
<shape:input icon="search" placeholder="Search" />

<shape:input icon="at-sign" icon-trailing="chevron-down" type="email" />
```

Both marks stay hidden from assistive tech: they decorate a control its label already named.

## Prefix and suffix

`prefix` puts words at the start of the field and `suffix` puts them at the end, muted and on the
field's own type scale, sharing the box with the control rather than sitting beside it:

```blade
<shape:input prefix="$" suffix="USD" type="number" />

<shape:input prefix="https://" suffix=".com" placeholder="your-site" />
```

For markup a string cannot carry — a button, a mark of your own, anything with tags in it — nest
`<shape:input.prefix>` or `<shape:input.suffix>` instead. They are the same two components the props
render, so there is one recipe rather than two, and they take their rung and their treatment from the
field they are standing in:

```blade
<shape:input value="{{ $key }}" readonly>
    <shape:input.suffix>
        <shape:button variant="ghost" size="xs">Copy</shape:button>
    </shape:input.suffix>
</shape:input>
```

Write a prop *and* nest a component and you get **two** affixes on that side, not the nearer one
winning. An anonymous component cannot see which of its children drew something — the same limit as
[One gap, in the composed form](field.md#one-gap-in-the-composed-form) — so the input has no way to
stand its own down. In `segmented` the two plates overlap visibly.

An affix is not a chrome prop, so a field with a prefix and no label stays a bare input rather than
expanding — and `type="hidden"` drops both, the way it drops everything else.

`affix="segmented"` puts the affix on a plate of its own instead: a fill, a divider in the field's
own border colour, and edges flush with the frame at every rung. It is the same three props, said
a louder way, and it is configurable in [Configuration](../configuration.md) so an application can
put every currency field on a plate at once:

```blade
<shape:input prefix="$" suffix="USD" affix="segmented" type="number" />
```

The plate is padded and sized for text. Put a control in the `inline` treatment instead, where it
sits in the field's flow with `variant="ghost"` — the same position [`<shape:file>`](file.md) takes,
that a control inside the field gives up its chrome rather than becoming a second framed control. Two
things follow from a focusable affix that are worth knowing before you reach for one: the box
brightens its focus ring when anything inside it takes focus, and a `disabled` control anywhere in
the box greys the whole field.

Every affix orders itself out to its own edge, so a nested one sits outside a leading or trailing
mark rather than beside the value, and it renders where it does regardless of where you wrote it.
A nested **suffix** is placed so it takes focus straight after the value; a nested prefix reads
first but tabs after, so write the field out with `<shape:field>` by hand if you need a focusable
control at the leading edge.

The divider takes the field's border colour by inheriting it, which means a border of your own on
the box reaches the plate too. It also means *parent*, not "the field": wrap an affix in a `<div>`
of your own and it inherits that div's border colour instead.

A disabled segmented field reads as one flat material — the box's disabled surface is the plate's
own fill, so only the divider survives. That is the honest rendering of a field that cannot be
typed into, and there is no token between the two surfaces worth inventing for it.

`<shape:input.prefix>` and `<shape:input.suffix>` render outside a field too, as a plain muted word.
`inline` is the floor there, which is what keeps that harmless — a segmented plate's bleed is not
inert outside the box it was measured against.

**An affix is decorative to assistive tech.** A screen reader announces a control's name and its
value; it does not read the text sitting beside it inside the wrapper. So a `$` or a `kg` is not
announced whether it is hidden or not, and hiding it would buy nothing. Where the unit carries
meaning the label does not, say it in the label or the description:

```blade
<shape:input label="Weight" description="In kilograms." suffix="kg" type="number" name="weight" />
```

## Attributes

`class` goes on the box; everything else goes on the control. That is the one rule this
component's shape costs, and it is the right way round — `max-w-sm`, `rounded-none` and a
border of your own are things you are saying about the box you can see, while `wire:model`,
`type`, `required`, `placeholder` and `readonly` are things only the `<input>` can act on.

```blade
<shape:input class="max-w-sm" type="email" required placeholder="you@example.com" />
```

The field fills the width it is given, so constrain it with `max-w-*` rather than `w-*`. Shape
merges classes without resolving Tailwind conflicts, so a `w-64` lands beside the component's
own `w-full` and the stylesheet's order decides which wins — as it does on the button.

`type` defaults to `text` and a call site's own wins. `id` is derived from the field name, and
an explicit one overrides it — which is what you want when a name collides with something else
on the page.

One type is not a control at all, and Shape treats it that way. `type="hidden"` renders the
bare `<input>` and nothing else — no box, no derived id, no `aria-invalid`, and no label or
help text even if you pass one, because there is nothing there to see or point at:

```blade
<shape:input type="hidden" name="token" value="{{ $token }}" />
```

```html
<input type="hidden" name="token" value="…" />
```

So a hidden field can sit among the visible ones without opening a gap in the form.

## Fields the browser adds a control to

Three input types come with a small control the browser draws itself: `date` and its relatives get a
calendar button, `number` gets a pair of spin buttons, and `search` gets a cancel button. All three
are ordinary inputs, with one addition each.

```blade
<shape:input label="Starts" type="datetime-local" name="starts_at" />
<shape:input label="Quantity" type="number" name="quantity" />
<shape:input label="Search" type="search" name="q" />
```

Each of those controls gets the pointer cursor a button should have. Only the calendar button is
dimmed, and the difference is worth knowing about if you are styling one of these yourself.
Chromium draws the calendar glyph at full contrast the whole time, in a colour Shape does not
control, so it reads louder than a trailing mark in the field beside it — the component knocks it
back to the same visual weight. The spin buttons and the cancel button need no such help: Chromium
already hides them until you hover the field. Dimming those would *override* that and leave a
stepper sitting in every number field, so Shape leaves their visibility to the browser.

The calendar button follows dark mode for free — Chromium draws that glyph from the element's
`color-scheme`, which Shape's theme already sets — so there is no inverted filter and no `dark:`
class involved. Firefox draws no such control and Safari exposes no such pseudo-element, so that
styling is inert there rather than wrong. Safari does draw its own steppers and cancel button and
shows them all the time rather than on hover.

Nothing short of a JavaScript date picker makes these fields look the same across browsers, and
Shape does not pretend otherwise.

---

[← Button](button.md) · [Index](../README.md) · [Field →](field.md)
