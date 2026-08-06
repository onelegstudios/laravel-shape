# Theming

Shape requires **Tailwind CSS v4.1** or newer, and its components style themselves against
theme tokens rather than bare palette steps. Importing the package's theme — see
[Installation](../README.md#installation) — does two things: it defines the semantic colour
roles the components use, and it tells Tailwind to scan the package's Blade views so the
component classes are actually generated. Skip it and the components render unstyled.

The theme has two layers, and you can override either.

**Colour roles** are full `50`–`950` ramps — `primary`, `success`, `warning`, `danger`,
`info`, `neutral` — and the set is open, so you can add your own. Override a role to rebrand;
everything using it follows:

```css
@theme {
    --color-primary-500: var(--color-violet-500);
    --color-primary-700: var(--color-violet-700);
}
```

**Surface tokens** name a purpose and point at a step on those ramps. Components reference
these rather than bare steps, so you can restyle one surface without changing what a ramp
step means everywhere else. To make solid primary buttons a shade darker:

```css
@theme {
    --color-primary-fill: var(--color-primary-800);
    --color-primary-fill-hover: var(--color-primary-900);
}
```

Each role exposes `fill`, `fill-hover`, `on-fill`, `tint`, `tint-hover`, `on-tint`, `border`,
and `ring`. If you would rather own the whole theme, publish it and import your copy instead:

```bash
php artisan vendor:publish --tag="shape-css"
```

**Page surfaces** are the one group that is not per role, because some things do not have one.
A text input is not `primary` or `danger` — it is made of the same material the page is made
of, and asking which role it belongs to has no answer. Four tokens cover that material:

| Token | What it is |
|---|---|
| `--color-surface` | The face of a control — a field, a panel |
| `--color-surface-muted` | The same face, disabled |
| `--color-ink` | What you typed, and text at full strength |
| `--color-ink-muted` | Placeholder, help text, a value you cannot edit |

Both muted steps clear AA against the surface beside them in either scheme, so they are safe
for text rather than only for hairlines. Override them to sit fields on a tinted page, or to
give the library a warmer ink than its neutral ramp:

```css
@theme {
    --color-surface: light-dark(var(--color-stone-50), var(--color-stone-900));
    --color-ink: light-dark(var(--color-stone-900), var(--color-stone-100));
}
```

## Adding a Colour Role

The colour set is open. Components build their class names from the role at render time, so a
role Shape has never heard of works as soon as its tokens exist — across every variant, in
both colour schemes. This is where a second brand colour goes, and it lives in your
application's stylesheet rather than in the package: the hue is yours, and so is the name.

Define a ramp, the eight surfaces, and — this part is not optional — the safelist:

```css
@theme {
    --color-ocean-50: var(--color-teal-50);
    /* …through 950, from a Tailwind palette or values of your own… */

    --color-ocean-fill: light-dark(var(--color-ocean-700), var(--color-ocean-600));
    --color-ocean-fill-hover: light-dark(var(--color-ocean-800), var(--color-ocean-500));
    --color-ocean-on-fill: var(--color-white);
    --color-ocean-tint: light-dark(var(--color-ocean-50), var(--color-ocean-950));
    --color-ocean-tint-hover: light-dark(var(--color-ocean-100), var(--color-ocean-900));
    --color-ocean-on-tint: light-dark(var(--color-ocean-800), var(--color-ocean-300));
    --color-ocean-border: light-dark(var(--color-ocean-300), var(--color-ocean-800));
    --color-ocean-ring: light-dark(var(--color-ocean-600), var(--color-ocean-500));
}

@source inline("bg-ocean-{fill,tint}");
@source inline("text-ocean-{on-fill,on-tint}");
@source inline("border-ocean-border");
@source inline("hover:bg-ocean-{fill-hover,tint,tint-hover}");
@source inline("focus-visible:outline-ocean-ring");
```

```blade
<shape:button variant="solid" color="ocean">Book a demo</shape:button>
```

Skip the `@source inline()` lines and nothing errors — Tailwind only compiles a utility it has
seen, and it never sees these, so the class lands in your markup and resolves to no style at
all. That block is also why roles are not free: Tailwind keeps any theme variable another kept
variable references, so a role costs its variables whether or not you use it. Shape ships six
because six get used; yours is the one you asked for.

Name it for the colour rather than its rank. `ocean` survives a redesign that reshuffles which
brand colour leads — `secondary` does not, and it collides with `variant`, which is what
already carries emphasis.

Two more things worth knowing before you plan around this. CSS cannot build an identifier out
of a variable, so there is no one-line `--shape-ocean-palette: teal` knob — you name the
palette once per step. And a `-700` fill clears AA under white text for every Tailwind hue
except amber, which is why `warning` fills light with dark text; check yours if it is bright.

## Dark Mode

Dark mode lives entirely in the theme — components carry no `dark:` classes. Surface tokens
use CSS `light-dark()`, which resolves against `color-scheme`. The theme wires that up:

```css
:root  { color-scheme: light dark; }  /* follow the operating system */
.dark  { color-scheme: dark; }        /* force dark for a subtree */
.light { color-scheme: light; }       /* force light for a subtree */
```

**Toggling `.dark` on `<html>`** — the standard Tailwind setup — works with no configuration.

**Using a different hook?** Point it at `color-scheme` yourself:

```css
[data-theme="dark"] { color-scheme: dark; }
```

**Your own `dark:` utilities are a separate mechanism.** Tailwind's `dark:` variant keys off
the OS preference, not off `color-scheme`. If you toggle dark mode with a class *and* write
your own `dark:` utilities — including ones you add to Shape components — you still need:

```css
@custom-variant dark (&:where(.dark, .dark *));
```

**Light-only application?** `color-scheme: light dark` means Shape follows an OS dark
preference, and the browser darkens its own surfaces too — canvas, scrollbars, form controls.
Opt out after the Shape import:

```css
:root { color-scheme: light; }
```

Because a surface token carries both modes in one declaration, restate the function when
overriding one if you want to keep them independent:

```css
@theme {
    --color-primary-fill: light-dark(var(--color-primary-800), var(--color-primary-500));
}
```

## Read next

- [Components](components.md) — the props that consume these roles
- [Configuration](configuration.md) — setting a house style once
