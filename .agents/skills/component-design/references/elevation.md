# Depth & elevation

Tailwind ships a ready shadow scale (`shadow-sm … shadow-2xl`), so you don't hand-build
two-part box-shadows — map **elevation** to those utilities:

| Level | Tailwind | Use |
|------:|----------|-----|
| 0 | `shadow-none` | flush / flat |
| 1 | `shadow-sm` / `shadow` | buttons, cards |
| 2 | `shadow-md` | dropdowns, popovers |
| 3 | `shadow-lg` | navigation, sticky bars |
| 4 | `shadow-xl` / `shadow-2xl` | modals, dialogs |

The judgment that remains:

- **Light comes from above.** Higher elements cast a softer, larger shadow; inset elements
  (wells, pressed buttons, inputs) read as recessed. Keep it subtle.
- **Interaction changes elevation.** Raise on hover (`hover:shadow-lg`), lower on press —
  reinforce with shadow, not just color.
- **Flat designs can still have depth.** Without shadows, layer with **background color**
  (lighter surface = closer) or solid/offset shadows.
- **Overlap to create layers.** Let a card cross a section edge, or an image break its
  container, for depth.

**For Shape:** components take an `elevation` level that maps to a shadow utility — never an
ad-hoc `shadow-[...]`.
