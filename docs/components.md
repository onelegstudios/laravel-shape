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

## Input

A form field is five components, and most of the time you want one of them to assemble the
other four:

```blade
<shape:input label="Email" description="We never share it." wire:model="email" />
```

That renders a label, the control, the help text, and — when the validator has something to say
about `email` — the message, with the label pointed at the control and `aria-describedby`
pointing at the parts that exist. Everything the shorthand cannot say goes back to the parts
themselves, which are `<shape:field>`, `<shape:label>`, `<shape:legend>`, `<shape:description>`
and `<shape:error>`:

```blade
<shape:field name="email">
    <shape:label>Email</shape:label>
    <shape:description>We never share it.</shape:description>
    <shape:input wire:model="email" />
    <shape:error />
</shape:field>
```

Naming the field once is what holds that together. The label points `for` at an id derived from
the name, the control answers to it, the description takes an id the control can name, and the
message knows which field it belongs to — four components that cannot see each other, agreeing
because they all derive from the same string. A name spelled the way the validator spells it
works unchanged: `user.email` and `items[0].qty` become `user-email` and `items-0-qty`.

A field that names itself names its control too, so the composed form above submits `email`
without the `<shape:input>` repeating it. A control carrying its own `name` or a `wire:model`
is left alone — it has already said which field it is.

`size` sets density — `xs`, `sm`, `md`, or `lg`, defaulting to `md`, and configurable in
[Configuration](configuration.md). The rungs are the button's own, so a field and the button
that submits it stand level in the same row:

```blade
<shape:input size="sm" placeholder="Search orders" />
<shape:button size="sm" variant="solid" color="primary">Search</shape:button>
```

There is no `variant` and no `color`. An input is not competing for attention the way a button
is, so there is no emphasis ladder to put it on — and the only thing an input's colour ever
says is whether the value is wrong, which is read rather than named.

### Invalid

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
[Configuration](configuration.md), and it is hidden from assistive tech because the sentence
beside it already says the same thing. That sentence, not the mark and not the colour, is what
carries the meaning for a reader who cannot see either.

Like the button's spinner, it has to have been published. `shape:install` does it for you;
`php artisan shape:icon:add error` is the manual form, `shape:icon:check` reports it missing —
and fails `--strict`, so CI can catch it — and `shape:icon:remove` will not take it away without
`--force`.

### Icons

`icon` puts a mark at the start of the field and `icon-trailing` puts one at the end, sized to
the field's own rung and resolved exactly the way `<shape:icon>` resolves them — so the name has
to have been published (`php artisan shape:icon:add search`). `icon-set` names another set for
both at once.

```blade
<shape:input icon="search" placeholder="Search" />

<shape:input icon="at-sign" icon-trailing="chevron-down" type="email" />
```

Both marks stay hidden from assistive tech: they decorate a control its label already named.

### Attributes

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

### One gap, in the composed form

The shorthand wires `aria-describedby` because it rendered the description and the message
itself, so it knows exactly which ids exist. The composed form cannot: an anonymous component
cannot see which of its children drew something, and naming an id that was never rendered is an
audit finding rather than a courtesy. So in the composed form that one attribute is yours:

```blade
<shape:field name="email">
    <shape:label>Email</shape:label>
    <shape:description>We never share it.</shape:description>
    <shape:input wire:model="email" aria-describedby="email-description" />
    <shape:error />
</shape:field>
```

Closing that gap is the whole reason the shorthand exists. Reach for the parts when you need
markup the props cannot describe, and for the prop the rest of the time.

`<shape:legend>` is the part for a `<fieldset>` you wrote yourself — it is styled to match a label
and takes the same `size` rungs, and it resolves nothing, because a legend names its fieldset by
sitting in it:

```blade
<fieldset>
    <shape:legend>Plan</shape:legend>

    <shape:field name="plan">
        <shape:radio value="free" label="Free" />
        <shape:error />
    </shape:field>
</fieldset>
```

For a Shape field, use the `legend` prop instead and let the field write both — see
[`legend` rather than `label`](#legend-rather-than-label). Either way the fieldset's
`aria-describedby` is yours in the composed form, for the reason above.

## Select

The input's box around a native `<select>`, with a chevron drawn from the icon set you installed:

```blade
<shape:select label="Plan" name="plan">
    <option value="free">Free</option>
    <option value="pro">Pro</option>
</shape:select>
```

Everything the input does, this does: the same four `size` rungs, the same padding, the same
`invalid` reading, the same shorthand, the same class-on-the-box rule. The options are the slot.

The chevron resolves through the `select-chevron` alias — `chevrons-up-down` in Lucide,
`chevron-up-down` in Heroicons — so it has to have been published. `shape:install` does it for you;
`php artisan shape:icon:add select-chevron` is the manual form. Point the alias somewhere else in
[Configuration](configuration.md) if you would rather have a single downward chevron, and every
select follows.

**The whole box opens the select, chevron included.** That is worth stating because it is the one
place this component is built differently from the input: the control and the mark share a single
grid cell rather than sitting in a flex row, so the `<select>` fills the box and the chevron floats
over it. A mark in a column of its own would leave the last twenty pixels of the field dead —
which is exactly where everyone clicks.

`icon` puts a leading mark in the field, sized to the rung, the way the input's does:

```blade
<shape:select icon="globe" name="region">…</shape:select>
```

There is no `icon-trailing`. The chevron owns that side, and a select carrying two trailing marks
is not a thing to want. `icon-set` names the set for the **leading** mark only — the chevron is
Shape's own and always resolves through `default`, the same as the button's spinner, because that
is the only set `shape:install` publishes the semantic names into.

`multiple` is a list box rather than a dropdown, so it draws no chevron, leaves no room for one, and
keeps the browser's own rendering:

```blade
<shape:select multiple size="4" name="plans[]">…</shape:select>
```

If your application also uses `@tailwindcss/forms` in its base mode, Shape's select still renders
exactly one chevron: the component clears the background image, colour and border that plugin paints
onto every `<select>`.

## Textarea

The same box around a control that stretches instead of sitting on a line:

```blade
<shape:textarea label="Bio" description="A sentence or two." wire:model="bio" />
```

Two things differ from the input, and nothing else does. The box does not centre a line it does not
have, and each rung takes the next step up in `leading` — 14px type on a 20px line is comfortable
for one line of a form and tight for five lines of prose.

`rows` defaults to **3** rather than the browser's 2, which is a box so short it reads as broken.
It is an ordinary attribute, so a call site's own wins.

`autosize` grows the field with what is typed into it:

```blade
<shape:textarea autosize rows="3" wire:model="bio" />
```

It is opt-in rather than the default on purpose. It lands in Chromium and not everywhere else, so a
packaged control that reflowed under the cursor in one browser and sat still in another would behave
differently per engine for no reason you asked for. `rows` keeps working as the minimum where it is
supported, and as the height where it is not.

## Checkbox

A box Shape draws itself, with its tick and its indeterminate bar from your icon set:

```blade
<shape:checkbox label="Email me about releases" description="About once a month." name="notify" value="1" />
```

The label sits **beside** the box rather than above it — a checkbox's words name the option next to
them, not the field over them — so this component is a row where the others are a column. A long
label wraps under its own first line instead of under the box.

`size` gives boxes of 16, 18, 20 and 24px, each paired with a mark that leaves a 2px inset and each
sitting inside its label's own line box, so the row aligns with no margin to tune. `xs` keeps its
smallness in the type and the gap: the box floor is 16px, because 14 with a 12 inside it is a target
nobody can hit. Only `lg` clears
[WCAG 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)'s 24px on the box
alone — what covers the rest is the label, which is part of the same target.

A group is a `<shape:field>` and some boxes. There is no group component to learn:

```blade
<shape:field name="tags" legend="Tags" description="Pick any that apply.">
    <shape:checkbox value="php" label="PHP" />
    <shape:checkbox value="laravel" label="Laravel" />
</shape:field>
```

Each box derives its own id from its `value` — `tags-php`, `tags-laravel` — so each label clicks
through to its own box rather than all of them to the first, and each help text answers to an id of
its own. The **message belongs to the field**, which prints it once: a validator has one opinion per
name however many controls carry it, and three copies of one sentence is not a message.

### `legend` rather than `label`

Naming `legend` is what opens a `<fieldset>` with a `<legend>` instead of a `<div>` with a `<label>`,
and a group wants one. Boxes wired by `name` are visually a set and, without the element, unrelated
controls to anything reading the page — a screen reader announces three checkboxes and a floating
word. A `label` on a group is also a `for` pointing at `tags`, which is an id no box carries; the
options are `tags-php` and `tags-laravel`. The legend has no `for` at all, because it names the
fieldset it opens by sitting in it.

The two props are one decision, so naming both draws the legend and drops the label rather than
rendering a dangling pair.

The group's **help text is named on the fieldset** via `aria-describedby`, because the field drew it
and knows the id exists. The **message is not**, and deliberately: every box already points at
`tags-error` itself, so a fieldset naming it too would have the sentence read on entering the group
and again on the first box.

One thing measures differently from a plain field. A rendered `<legend>` is painted into its
fieldset's border box rather than laid out as a child, so no `gap` can reach it — the column lives on
a wrapper inside and the legend carries its own margin. That means `class` on a group styles the
outer box, which is what you want for `max-w-sm` or a border of your own, and what you do not want
for `gap-4`.

Standing on its own, a checkbox *is* the whole field, so it prints its own message. A consent box
that fails validation silently is the bug that covers.

Indeterminate has no HTML attribute — set the property:

```blade
<shape:checkbox label="Select all" id="all" />
<script>document.getElementById('all').indeterminate = true</script>
```

A box can be checked and indeterminate at once, and the bar wins.

The two marks resolve through `checkbox-check` and `checkbox-indeterminate`, which both libraries
happen to spell `check` and `minus`. They are separate names anyway, so repointing the mark inside a
checkbox does not repoint every `<shape:icon name="check">` you wrote yourself.

## Radio

The checkbox's row and box, made round:

```blade
<shape:field name="plan" legend="Plan">
    <shape:radio value="free" label="Free" description="One project." />
    <shape:radio value="pro" label="Pro" description="Best for a small team." />
</shape:field>
```

`legend` rather than `label` for the reason [Checkbox](#legend-rather-than-label) gives, and it
matters more here: a radio only ever exists as one option of a set, so a radio group that is not
announced as a group is every radio Shape draws.

Round rather than square is the only thing telling a reader this set is one-of-many rather than
any-of-many, so the shape is not a prop. The boxes are the checkbox's exactly, because a radio and a
checkbox in one form are the same control with two selection rules and should measure the same down
a column.

The dot is CSS rather than an icon, which means **a radio needs nothing published to render**.
Heroicons ships no `circle` and no `dot`, so an alias would have pointed at a glyph half the
libraries Shape can install do not have.

A radio never prints a message of its own, even standing alone: one option of a set the user cannot
choose from is a bug in the markup rather than a state to style, so the sentence always belongs to
the group. It still takes the invalid styling — the colour is the control's, the sentence is the
field's.

## Switch

The checkbox's row and states, stretched into a pill:

```blade
<shape:switch label="Email me about releases" description="About once a month." name="notify" />
```

Reach for one when flipping it *applies* the setting — notification preferences, feature flags,
two-factor enrolment. Reach for a checkbox when the value is collected and submitted with the rest
of the form. That distinction is the whole of the difference: underneath, a switch is
`<input type="checkbox" role="switch">`, because no other element carries a boolean into a form. The
role is what turns "checked" into "on" for a screen reader, and a call site that means something
else by it can say so.

Everything the checkbox promises about names, ids, `@aware` and `invalid` holds here unchanged, and
so do its colours — `bg-surface` inside a neutral border off, solid `primary-fill` on, border and
all. A form carrying both should read as one set of controls rather than two.

`size` gives tracks of 28×16, 32×18, 36×20 and 44×24px. The heights are the checkbox's box sizes
exactly, so a switch and a box down one column share a top and a bottom edge. Pick the 2px inset and
the rest follows: **travel is the track's width minus its height, and travel is also the thumb** — a
thumb clears its own width and stops. Only `lg` clears
[WCAG 2.5.8](https://www.w3.org/WAI/WCAG22/Understanding/target-size-minimum.html)'s 24px on the
short axis; every rung clears it across the track, and the label is part of the same target.

The thumb is CSS rather than an icon, which means **a switch needs nothing published to render**. It
is a shape rather than a glyph, so asking an icon set for one would be asking for the filled circle
Heroicons does not ship — the radio's problem, arrived at from the other direction.

`value` is *not* a discriminator here, and this is the one place a switch departs from the box it is
built on:

```blade
<shape:switch name="notify" value="1" />   {{-- id="notify", not id="notify-1" --}}
```

The discriminator exists for controls that share a name — three boxes on one `tags` field, three
radios called `plan` — and a switch is never one of a set. Nothing groups them, so there is nothing
to keep apart.

Standing on its own a switch *is* the whole field, so it prints its own message, exactly as a
checkbox does. Inside a `<shape:field>` the sentence belongs to the field.

## File

The input's box around the one control that arrives with a button already inside it:

```blade
<shape:file label="Avatar" description="PNG or JPG, up to 2 MB." name="avatar" accept="image/*" />
```

The button gives up its own border, background and padding, so the field reads as one frame with an
action in it rather than a control sitting inside a box. That is also what keeps the height right: a
button with padding of its own would make this field taller than a text field of the same rung, and
with its height reduced to its own line box the two sit level at 26, 34, 38 and 46px.

The filename is `ink-muted` rather than `ink`, because it is a report of what was picked rather than
a value anybody typed.

`icon` puts a leading mark in the field. There is no trailing one: the far end of this box belongs
to the filename, which has no fixed length and every reason to be the thing that truncates.

## Range

A slider, drawn from the same tokens as everything else:

```blade
<shape:range label="Volume" description="Applies to previews only." name="volume" max="100" />
```

`min`, `max`, `step` and `list` are the control's own and pass straight through — nothing here is a
Shape prop. `size` gives 4, 6, 8 and 8px tracks under 12, 14, 16 and 20px thumbs, and the thumbs are
the switch's exactly, so two controls you drag or flip carry the same mark. The outer heights are the
input's own 26, 34, 38 and 46px, which is the point of stating them: **a slider and a text field of
the same rung stand level in a row** without either one being told about the other.

This is the one control in the family with no box around it. The track is the only thing there is to
see, so a border would be a second horizontal line saying nothing — which means `class` lands on the
control itself rather than on a wrapper, and the rule the input's shape costs does not apply.

The focus ring goes on the thumb rather than around the control. An outline around the full 38px box
of something whose visible part is a 2px bar reads as a stray rectangle.

Invalid tints the track `danger` rather than changing a border, because there is no border here to
change. Everything else about names, ids, `@aware` and how `invalid` is resolved holds unchanged.

**There is no filled portion, in any browser.** CSS cannot read a slider's value, so the part left of
the thumb can only be painted through `::-moz-range-progress`, which is Firefox's alone — and taking
it would make this control look deliberately different in one browser. That is the same call the date
fields below make. A filled track is what JavaScript is for.

## Color

The native picker as a swatch, at the height of the field beside it:

```blade
<shape:color label="Brand" description="Used for buttons and links." name="brand" />
```

Square rather than stretched, and that is the component's opinion rather than the call site's. A
field stretches because what it holds has no length you can predict; this one holds a colour, which
has no length at all — stretched, it is a band of saturated colour carrying more weight on the page
than the question it answers. `size` gives 26, 34, 38 and 46px squares, the input's own heights
again, so a swatch and a field of the same rung sit level. A row of them is a palette.

It keeps the input's frame — `rounded-md`, a neutral border, `bg-surface` showing through before a
value is picked — because a pale colour on a pale page has no boundary of its own. The colour inside
is rounded one step tighter so the border stays visible in the corners rather than being swallowed.

**No hex is shown beside it.** Reading the value back into text takes JavaScript, and Shape ships
none. Put a `<shape:input>` bound to the same model next to it and let Livewire or Alpine keep the
two in step:

```blade
<shape:field name="brand">
    <shape:label>Brand colour</shape:label>
    <shape:color wire:model.live="brand" />
    <shape:input wire:model.live="brand" class="max-w-3xs" />
</shape:field>
```

Chromium pads both the input and the wrapper inside its swatch, and both engines draw a hairline
around the colour itself; the component clears all three so the value fills the frame it was given.
Safari draws its own colour well and honours `appearance: none` unevenly, so expect it to look a
little more like the operating system there.

## Date and time fields

`<shape:input type="date">` is an ordinary input, with one addition. Chromium draws a calendar button
inside the field at full contrast, in a colour Shape does not control, so it reads louder than a
trailing mark in the field beside it. The component knocks it back to the same visual weight and
gives it the pointer cursor a button should have:

```blade
<shape:input label="Starts" type="datetime-local" name="starts_at" />
```

It follows dark mode for free — Chromium draws that glyph from the element's `color-scheme`, which
Shape's theme already sets — so there is no inverted filter and no `dark:` class involved. Firefox
draws no such control and Safari exposes no such pseudo-element, so the styling is inert there
rather than wrong. Nothing short of a JavaScript date picker makes these fields look the same across
browsers, and Shape does not pretend otherwise.

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

**The field components opt out of Blaze entirely** — every control (the input, select, textarea,
checkbox, radio and file input) along with the field, the label, the description and the error all
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

The repository carries a benchmark that measures all three strategies against the gallery's own
component markup, on a page compiled once and rendered many times:

```bash
composer bench -- --repeat=10 --renders=60
```

It compares four strategies: Blade's own pipeline, the icon as it was before
icons were published, the components as shipped, and a foldable button.
