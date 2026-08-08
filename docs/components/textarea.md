# Textarea

The [input](input.md)'s box around a control that stretches instead of sitting on a line:

```blade
<shape:textarea label="Bio" description="A sentence or two." wire:model="bio" />
```

Two things differ from the input, and nothing else does. The box does not centre a line it does not
have, and each rung takes the next step up in `leading` — 14px type on a 20px line is comfortable
for one line of a form and tight for five lines of prose. Names, ids, the
[`invalid` reading](input.md#invalid) and the composed form are the input's and
[Field](field.md)'s, unchanged.

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

---

[← Select](select.md) · [Index](../README.md) · [Checkbox →](checkbox.md)
