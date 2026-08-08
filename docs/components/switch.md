# Switch

The [checkbox](checkbox.md)'s row and states, stretched into a pill:

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
Heroicons does not ship — the [radio](radio.md)'s problem, arrived at from the other direction.

`value` is *not* a discriminator here, and this is the one place a switch departs from the box it is
built on:

```blade
<shape:switch name="notify" value="1" />   {{-- id="notify", not id="notify-1" --}}
```

The discriminator exists for controls that share a name — three boxes on one `tags` field, three
radios called `plan` — and a switch is never one of a set. Nothing groups them, so there is nothing
to keep apart.

Standing on its own a switch *is* the whole field, so it prints its own message, exactly as a
checkbox does. Inside a [`<shape:field>`](field.md) the sentence belongs to the field.

---

[← Radio](radio.md) · [Index](../README.md) · [File →](file.md)
