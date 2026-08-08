# Range

A slider, drawn from the same tokens as everything else:

```blade
<shape:range label="Volume" description="Applies to previews only." name="volume" max="100" />
```

`min`, `max`, `step` and `list` are the control's own and pass straight through — nothing here is a
Shape prop. `size` gives 4, 6, 8 and 8px tracks under 12, 14, 16 and 20px thumbs, and the thumbs are
the [switch](switch.md)'s exactly, so two controls you drag or flip carry the same mark. The outer
heights are the [input](input.md)'s own 26, 34, 38 and 46px, which is the point of stating them:
**a slider and a text field of the same rung stand level in a row** without either one being told
about the other.

This is the one control in the family with no box around it. The track is the only thing there is to
see, so a border would be a second horizontal line saying nothing — which means `class` lands on the
control itself rather than on a wrapper, and the rule the
[input's shape costs](input.md#attributes) does not apply.

The focus ring goes on the thumb rather than around the control. An outline around the full 38px box
of something whose visible part is a 2px bar reads as a stray rectangle.

Invalid tints the track `danger` rather than changing a border, because there is no border here to
change. Everything else about names, ids, `@aware` and how `invalid` is resolved holds unchanged —
see [Field](field.md) and [Invalid](input.md#invalid).

**There is no filled portion, in any browser.** CSS cannot read a slider's value, so the part left of
the thumb can only be painted through `::-moz-range-progress`, which is Firefox's alone — and taking
it would make this control look deliberately different in one browser. That is the same call
[the date fields](input.md#fields-the-browser-adds-a-control-to) make. A filled track is what
JavaScript is for.

---

[← File](file.md) · [Index](../README.md) · [Color →](color.md)
