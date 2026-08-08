# Field

A form field is five components — `<shape:field>`, `<shape:label>`, `<shape:legend>`,
`<shape:description>` and `<shape:error>` — and most of the time you want one of them to assemble
the other four:

```blade
<shape:input label="Email" description="We never share it." wire:model="email" />
```

That renders a label, the control, the help text, and — when the validator has something to say
about `email` — the message, with the label pointed at the control and `aria-describedby`
pointing at the parts that exist. Everything the shorthand cannot say goes back to the parts
themselves:

```blade
<shape:field name="email">
    <shape:label>Email</shape:label>
    <shape:description>We never share it.</shape:description>
    <shape:input wire:model="email" />
    <shape:error />
</shape:field>
```

Every control in Shape takes the shorthand and sits in the composed form: the
[input](input.md), the [select](select.md), the [textarea](textarea.md), the
[checkbox](checkbox.md), the [radio](radio.md), the [switch](switch.md), the [file](file.md)
input, the [range](range.md) and the [color](color.md) picker.

## Naming the field once

Naming the field once is what holds that together. The label points `for` at an id derived from
the name, the control answers to it, the description takes an id the control can name, and the
message knows which field it belongs to — four components that cannot see each other, agreeing
because they all derive from the same string. A name spelled the way the validator spells it
works unchanged: `user.email` and `items[0].qty` become `user-email` and `items-0-qty`.

A field that names itself names its control too, so the composed form above submits `email`
without the `<shape:input>` repeating it. A control carrying its own `name` or a `wire:model`
is left alone — it has already said which field it is.

## One gap, in the composed form

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

## Groups and `legend`

A field takes `legend` where a single control takes `label`, and naming it opens a real
`<fieldset>` so a set of [checkboxes](checkbox.md#groups) or [radios](radio.md) is announced as
one. The two props are one decision — see
[`legend` rather than `label`](checkbox.md#legend-rather-than-label) for why a group cannot use
the other.

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

For a Shape field, use the `legend` prop instead and let the field write both. Either way the
fieldset's `aria-describedby` is yours in the composed form, for the reason above.

---

[← Input](input.md) · [Index](../README.md) · [Select →](select.md)
