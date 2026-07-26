# Shape Style Guide

This is a **human reader's guide**. The **canonical, machine-usable source** for Shape's
design guidance lives in the `component-design` skill so AI agents load only the parts they
need, when they need them:

```
.agents/skills/component-design/
├── SKILL.md                 # workflow + component checklist + Tailwind cheat sheet
└── references/
    ├── foundations.md       # starting a component, personality-as-theme
    ├── hierarchy.md         # emphasis via weight/color/size, labels, semantics
    ├── spacing-layout.md    # spacing scale, grouping, columns
    ├── typography.md        # type scale, line-height, alignment
    ├── color.md             # semantic tokens, contrast (AA), dark mode
    ├── elevation.md         # shadow → elevation mapping
    ├── media.md             # avatar/image/media mechanics
    └── finishing-touches.md # empty states, fewer borders, accent borders
```

Edit the files above — not this page — when guidance changes.

## Background

Principles are condensed from *Refactoring UI* (Adam Wathan & Steve Schoger) and restated for
a **Tailwind-based** component library. Since *Refactoring UI* and Tailwind share an author,
Tailwind's defaults already **are** the book's recommended systems (spacing, type, color
ramps, shadows) — so the guidance keeps the **design judgment** and drops the scale-building
the framework already does for us.

## The one rule to remember

Style with Tailwind **theme tokens** (`p-4`, `text-lg`, `bg-primary-600`, `shadow-md`) —
never arbitrary values (`p-[13px]`, `text-[#3b82f6]`). If a value you need isn't a token, the
fix is to extend the theme so the whole library benefits, not to hardcode it.

## Read next

- Building or reviewing a component? Start at
  [`.agents/skills/component-design/SKILL.md`](../.agents/skills/component-design/SKILL.md)
  and run the **Component Checklist**.
- Working on one dimension (color, spacing, hierarchy…)? Open the matching file in
  [`references/`](../.agents/skills/component-design/references/).
