# Color

Tailwind ships full **50–950 ramps** and generates shades for you, so the book's ramp-building
chapters (HSL vs hex, defining shades up front, rotating hue, saturation at the edges) are
**handled by the framework** — don't hand-mix colors. What still requires judgment:

- **You need greys + a primary + a few accents.** Configure them as **semantic theme colors**
  so components stay themeable:
  - **Neutrals** — most of the UI (text, borders, backgrounds, controls) is grey. Use the
    ramp; start dark grey, not pure black.
  - **Primary** — one brand color for primary actions and active states; light shades tint
    backgrounds, dark shades work for text.
  - **Accents** — `success / warning / danger / info` (+ any highlight), used sparingly.
- **Use tokens, never arbitrary hex.** `bg-primary-600`, `text-danger-700` — not
  `bg-[#3b82f6]`. This is what makes a consumer's theme override actually work.
- **Don't use grey text on colored backgrounds** (see `hierarchy.md`) — tint from the same hue.
- **Meet contrast targets.** WCAG **AA**: ≥ **4.5:1** normal text, ≥ **3:1** large text and
  meaningful UI/graphics. Pick ramp steps that clear this; don't eyeball it.
- **Never rely on color alone.** Pair every color-coded meaning (error, success, status) with
  an icon, label, or shape for colorblind users.
- **Dark mode swaps roles, not values.** Support `dark:` by reassigning semantic roles to
  different ramp steps (e.g. surface `gray-50` → `gray-900`), not by inverting colors.

**For Shape:** define semantic color tokens (`primary`, `success`, `warning`, `danger`,
`info`, plus surface/text/border roles) in the theme; components reference roles, so consumers
restyle the whole library by editing the theme.
