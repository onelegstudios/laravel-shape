---
name: component-design
description: "Use this skill when building, styling, or reviewing a Shape UI component or Blade/Livewire view — decisions about visual hierarchy, spacing, typography, color, elevation, media, empty/loading/error states, or dark mode. Enforces Tailwind theme tokens over arbitrary values. This skill is the canonical source for Shape's design guidance."
license: MIT
metadata:
  author: onelegstudios
---

# Component Design

Canonical design guidance for the Shape Livewire component library. Principles are
condensed from *Refactoring UI* (Adam Wathan & Steve Schoger) and restated for a
**Tailwind-based** library. Because Tailwind's defaults already encode the book's scales
(spacing, type, color ramps, shadows), this skill keeps the **judgment** and drops the
scale-building the framework already does.

## Primary Goal

Ship Shape components that are visually consistent, themeable, and accessible by applying
Tailwind theme tokens with sound design judgment — never arbitrary values.

## Golden Rule

If you're typing a bracketed arbitrary value (`p-[13px]`, `text-[#3b82f6]`), stop: it's
either already a token, or it belongs in the theme so the whole library benefits.

## Core mindset

- **The system already exists — use it.** Tailwind's spacing, type, color, radius, and
  shadow scales are the constrained systems the book tells you to build. Don't fight them.
- **Systems over guesses.** Pick the nearest token. If none fits, extend the theme once.
- **Start simple.** Get structure and hierarchy right in greys before color/shadow.
- **Design components, not screens.** Each component must look right in isolation *and*
  compose cleanly. Bake in good defaults; expose variants, not free-form styling.

## Workflow

1. Identify which design dimensions the component touches (hierarchy, spacing, type, color,
   elevation, media, states).
2. Draft in greys first: structure + hierarchy before color/shadow.
3. Open only the matching reference(s) below and apply them.
4. Run the Component Checklist before calling it done.
5. Verify AA contrast and that consumers can restyle via the theme.

## Component Checklist

- [ ] **Tokens only** — no arbitrary values (`[...]`), no hardcoded hex/px. Missing values go in the theme.
- [ ] **Hierarchy** via weight + color (+ size), not flat styling.
- [ ] **Spacing** from the scale; related parts grouped by proximity; no ambiguous spacing.
- [ ] **Type** from `text-*`; line-height sane for size & measure; tabular numbers right-aligned.
- [ ] **Color** — semantic tokens only; AA contrast; never meaning by color alone; dark mode by role-swap.
- [ ] **Depth** — shadows reference an elevation level; light from above; hover/press change elevation.
- [ ] **Media** — sized container + `object-cover`; text-over-image contrast; no upscaled icons.
- [ ] **States** — empty (real), loading, error, long-content, and tiny/huge content all designed.
- [ ] **Themeable** — consumers restyle via the theme (colors, radius, font) without editing markup.
- [ ] **A11y** — semantic HTML/ARIA independent of visual hierarchy; visible focus; keyboard-operable.

## References

Load only the file for the dimension you're working on:

- `references/foundations.md` — starting a component, personality-as-theme, pessimistic states
- `references/hierarchy.md` — emphasis via weight/color/size, labels, semantics *(highest-value)*
- `references/spacing-layout.md` — spacing scale, grouping, columns, ambiguous spacing
- `references/typography.md` — type scale, line-height, measure, number alignment
- `references/color.md` — semantic tokens, contrast (AA), dark mode
- `references/elevation.md` — shadow → elevation mapping, interaction, flat depth
- `references/media.md` — avatar/image/media mechanics (object-fit, contrast)
- `references/finishing-touches.md` — empty states, fewer borders, accent borders

Human reader's guide (points back here): `docs/STYLE_GUIDE.md`.

## Principle → Tailwind cheat sheet

| Principle | Reach for |
|---|---|
| Spacing / grouping | `p-* m-* gap-* space-y-* max-w-*` |
| Type scale & rhythm | `text-* leading-* tracking-* max-w-prose tabular-nums` |
| Emphasis / hierarchy | `font-normal/medium/semibold/bold`, `text-gray-900/600/500` |
| Semantic color | `bg-primary-* text-danger-* border-success-*` (theme roles) |
| Elevation | `shadow-none/sm/md/lg/xl/2xl`, `hover:shadow-*` |
| Radius / personality | `rounded-none/sm/md/lg/full` (theme default) |
| Dark mode | `dark:` variants swapping ramp steps by role |
| Contrast (AA) | ≥ 4.5:1 text · ≥ 3:1 large text & UI |

## Anti-Patterns

- Arbitrary Tailwind values (`[...]`) instead of theme tokens.
- One-off component styles instead of shared variant recipes.
- Meaning conveyed by color alone.
- Shipping a stateful component with no empty/loading/error state.
- Hardcoding colors/fonts/radii that ignore the consumer's theme.
