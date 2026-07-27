# Hierarchy is everything

**Highest-value dimension — Tailwind gives you the tools but none of the judgment.** Most
"ugly" UI is really *flat* UI, where everything competes for attention.

- **Not all elements are equal.** Deliberately decide primary / secondary / tertiary in every
  component, and make the difference obvious.
- **Weight and color before size.** Reach for `font-*` and text-color utilities more than raw
  size:
  - **Weight** — `font-semibold`/`font-bold` for primary text, `font-normal` for secondary.
  - **Color** — a dark grey (`text-gray-900`) for primary, softer (`text-gray-600`) for
    secondary, lighter (`text-gray-500/400`) for tertiary. Use 2–3 text colors, not one flat
    black.
  - **Size** — `text-*` steps, sparingly, usually combined with the above.
- **Emphasize by de-emphasizing.** Quiet everything around the important thing instead of
  shouting it louder.
- **Balance weight and contrast.** Faint text → raise contrast; heavy dark text that's too
  loud → soften its color.
- **Don't use grey text on colored backgrounds.** To soften text on a colored panel, use a
  shade from that same hue (e.g. `text-primary-200` on `bg-primary-600`), not a flat grey.
- **Labels are a last resort.** "Label: value" pairs waste hierarchy. Prefer: omit the label
  when obvious; fold it into the value ("12 left in stock"); or de-emphasize the label
  (`text-sm text-gray-500`) and emphasize the value (`font-semibold text-gray-900`).
  Emphasize labels only for scannable spec/comparison data.
- **Separate visual hierarchy from document hierarchy.** Choose the semantic tag (`h1`,
  `h2`…) for meaning; choose the utilities for visual weight. A big heading needn't be an
  `<h1>`.
- **Semantics are secondary.** A button's role (primary / secondary / danger) reads from
  *prominence*, not just color: primary = most prominent, secondary = ghost/soft, destructive
  isn't red until the user is near the point of no return.

**For Shape:** emphasis and meaning are **two separate props**, because `primary` as an
emphasis level and `primary` as a colour role are different questions and collide if merged:

- **`variant`** — the emphasis ladder, named for its treatment:
  `solid` (primary) · `soft` (secondary) · `ghost` (tertiary) · `outline`.
  Emphasis is built from weight + color, per the rules above.
- **`color`** — a semantic role from the theme (`primary / success / warning / danger /
  info / neutral`), never a raw palette hue. See `color.md`.

**Defaults are `variant="outline" color="neutral"` — the quiet button, on purpose.** A screen
should carry one primary action, so the loud one is opt-in (`variant="solid"`) instead of what
you get for free. This costs two props on the hero button and none on the other five, which is
the right way round: the emphasis decision should be deliberate.

`outline` beats `soft` for that default despite `finishing-touches.md` ranking borders last.
That rule assumes you control the surface; a packaged component doesn't. A tinted fill
disappears against a similarly tinted page, a border never does. **When you *do* control the
surface — composing a screen, not shipping a component — `soft` still outranks `outline`.**

`button`, `badge`, and `alert` ship this pair as a fixed set of utility recipes, not one-off
styles. Size, radius, and elevation stay on their own props — folding them into `variant`
brings back the combinatorial naming this split exists to avoid.
