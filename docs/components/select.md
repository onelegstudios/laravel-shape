# Select

The [input](input.md)'s box around a native `<select>`, with a chevron drawn from the icon set you
installed:

```blade
<shape:select label="Plan" name="plan">
    <option value="free">Free</option>
    <option value="pro">Pro</option>
</shape:select>
```

Everything the input does, this does: the same four `size` rungs, the same padding, the same
[`invalid` reading](input.md#invalid), the same shorthand, the same
[class-on-the-box rule](input.md#attributes). The options are the slot. Names, ids and the composed
form work as [Field](field.md) describes.

The chevron resolves through the `select-chevron` alias — `chevrons-up-down` in Lucide,
`chevron-up-down` in Heroicons — so it has to have been published. `shape:install` does it for you;
`php artisan shape:icon:add select-chevron` is the manual form. Point the alias somewhere else in
[Configuration](../configuration.md) if you would rather have a single downward chevron, and every
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

---

[← Field](field.md) · [Index](../README.md) · [Textarea →](textarea.md)
