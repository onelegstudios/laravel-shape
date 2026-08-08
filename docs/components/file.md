# File

The [input](input.md)'s box around the one control that arrives with a button already inside it:

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

---

[← Switch](switch.md) · [Index](../README.md) · [Range →](range.md)
