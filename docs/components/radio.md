# Radio

The [checkbox](checkbox.md)'s row and box, made round:

```blade
<shape:field name="plan" legend="Plan">
    <shape:radio value="free" label="Free" description="One project." />
    <shape:radio value="pro" label="Pro" description="Best for a small team." />
</shape:field>
```

`legend` rather than `label` for the reason
[Checkbox](checkbox.md#legend-rather-than-label) gives, and it
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

---

[← Checkbox](checkbox.md) · [Index](../README.md) · [Switch →](switch.md)
