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

Each component has a page of its own. A component that exists only to help another one — the
input's affixes, the field's parts — is documented on its parent's page rather than a page of its
own.

## The components

| Component | What it is |
| --- | --- |
| [Button](components/button.md) | The one component with an emphasis ladder: `variant`, `color`, `size`, icons either side, and a loading state that keeps its width |
| [Input](components/input.md) | The text field the rest of the family is measured against — invalid states read from the error bag, icons, prefixes and suffixes, and the types the browser adds a control to |
| [Field](components/field.md) | The composition primitive, and the four parts it assembles: `label`, `legend`, `description` and `error`. Naming the field once is what wires them together |
| [Select](components/select.md) | The input's box around a native `<select>`, with a chevron from your icon set |
| [Textarea](components/textarea.md) | The same box around a control that stretches, with `rows` that defaults to something readable and opt-in `autosize` |
| [Checkbox](components/checkbox.md) | A box Shape draws itself, its label beside it rather than above, and groups built from a field |
| [Radio](components/radio.md) | The checkbox's row and box, made round, and never a message of its own |
| [Switch](components/switch.md) | The checkbox stretched into a pill, for a setting that applies when you flip it |
| [File](components/file.md) | The input's box around the one control that arrives with a button already inside it |
| [Range](components/range.md) | A slider on the input's heights, and the one control in the family with no box around it |
| [Color](components/color.md) | The native picker as a square swatch at the height of the field beside it |
| [Icon](components/icon.md) | A published icon, for the places a prop cannot say it. The full story is in [Icons](icons.md) |

## Where to go next

- [Icons](icons.md) — publishing icons, sets, semantic names, and the five commands
- [Theming](theming.md) — the colour roles and surface tokens these props consume
- [Configuration](configuration.md) — setting the defaults once, in `config/shape.php`
- [Performance](performance.md) — what Blaze does with these components, and what folds

---

[← Installation](installation.md) · [Index](README.md) · [Button →](components/button.md)
