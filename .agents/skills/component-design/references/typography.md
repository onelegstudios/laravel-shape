# Typography

Tailwind's `text-xs … text-9xl` **is** the hand-picked type scale, and each step **already
ships a paired line-height** — use the steps, don't invent sizes. The judgment that remains:

- **Constrain the sizes you use.** Don't scatter font sizes; pick from the scale and reuse.
- **Line-height pairs with size and measure.** The `text-*` defaults handle size pairing;
  override with `leading-*` when the measure is unusual — wider columns need taller
  line-height, large headings need tighter (`leading-tight`), small body text needs looser
  (`leading-relaxed`).
- **Keep line length in check.** ~45–75 characters for body copy — `max-w-prose` or a tuned
  `max-w-*`, even inside a wide container.
- **Baseline, not center.** When mixing sizes on one line (a big number beside a small unit),
  align to the baseline (`items-baseline`), not center.
- **Not every link needs color.** In link-dense UI use weight or a subtler treatment; reserve
  strong link color for where it aids discovery.
- **Align for readability.** Left-align long text; don't center more than a couple of lines.
  **Right-align numbers** in tables and use `tabular-nums` so digits line up by place value.
- **Letter-spacing, sparingly.** Tighten large headlines (`tracking-tight`); add positive
  tracking to **ALL-CAPS** labels (`uppercase tracking-wide`) for legibility.

**For Shape:** `text`/`heading` components map to named scale steps (`sm`, `base`, `lg`,
`xl`…), never raw sizes, with the paired line-height coming along for free.
