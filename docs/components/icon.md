# Icon

Icons have a guide of their own: [Icons](../icons.md) covers publishing, sets, semantic names,
accessibility and the five commands. This page is the component.

Most of the time you want one inside a button, and the button's
[`icon` prop](button.md#icons) is the shorter
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

Writing the icon into the slot is also the form that folds away at compile time, which is worth
knowing on a page that renders hundreds of them — see [Performance](../performance.md).

---

[← Color](color.md) · [Index](../README.md) · [Header →](header.md)
