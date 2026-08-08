# Color

The native picker as a swatch, at the height of the field beside it:

```blade
<shape:color label="Brand" description="Used for buttons and links." name="brand" />
```

Square rather than stretched, and that is the component's opinion rather than the call site's. A
field stretches because what it holds has no length you can predict; this one holds a colour, which
has no length at all — stretched, it is a band of saturated colour carrying more weight on the page
than the question it answers. `size` gives 26, 34, 38 and 46px squares, the
[input](input.md)'s own heights again, so a swatch and a field of the same rung sit level. A row of
them is a palette.

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

---

[← Range](range.md) · [Index](../README.md) · [Icon →](icon.md)
