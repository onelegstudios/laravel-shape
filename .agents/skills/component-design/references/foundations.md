# Foundations — starting a component

## Starting a component

- **Solve a job, not a layout.** Start from the concrete thing the component does ("confirm
  a destructive action"), not a blank canvas. Design the smallest useful version first.
- **Don't over-invest early.** Low-fidelity first: real content, sensible spacing, one
  neutral color. Polish (shadow, accent, illustration) comes last.
- **Be a pessimist.** Design the awkward states up front — long labels, empty data, errors,
  tiny and huge content. A component that only looks good with perfect content is broken.
- **Work in short cycles.** Ship a rough version, iterate. Don't design ten variants before
  one is real.

## Encode personality in the theme, not per-component

Shape's visual "voice" is a few deliberate decisions, set **once in the Tailwind theme** and
inherited everywhere:

- **Font family** — a legible UI sans (system stack or one typeface with ≥5 weights) →
  theme `fontFamily`.
- **Border radius** — one default sets the tone: `rounded-none` = technical,
  `rounded`/`rounded-md` = neutral, `rounded-full`/pill = friendly. Pick a default and
  expose `none / sm / md / lg / full`.
- **Primary color** — a restrained brand color → theme `colors.primary` ramp.

Components reference these theme tokens; they never hardcode a font, radius, or hex. This is
what lets a consumer restyle the entire library by editing the theme.
