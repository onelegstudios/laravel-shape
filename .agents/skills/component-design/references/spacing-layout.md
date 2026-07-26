# Layout & spacing

Tailwind's spacing scale (`p-*`, `m-*`, `gap-*`, `space-*`) **is** the non-linear, 16px-based
system the book prescribes — already tuned so adjacent steps differ enough to make choosing
easy. Just use it. The judgment that remains:

- **Start with too much white space, then remove.** Generous spacing reads as clean and
  intentional; it's easier to notice space to *remove* than space to add.
- **Dense UIs are a deliberate choice.** Data-heavy tools can justify tight spacing — on
  purpose, never as the default.
- **You don't have to fill the width.** Don't stretch content edge-to-edge because the space
  exists. Constrain with `max-w-*` and think in **columns**.
- **Not everything should be fluid.** Give elements a sensible `max-w-*` and let them shrink
  only when the viewport forces it. A sidebar can be fixed-width while content flexes.
- **Relative sizing doesn't scale.** Padding, font size, and element size each have their own
  comfortable range — adjust them independently across breakpoints (`md:p-8 md:text-lg`),
  don't scale everything in lock-step.
- **Avoid ambiguous spacing.** An element should sit visibly closer to what it belongs to than
  to what it doesn't. Tighten the gap between a label and its control; widen the gap to the
  next group. Whitespace *is* grouping — lean on `space-y-*`/`gap-*` to express it.

**For Shape:** expose spacing via variant props mapped to scale tokens, not free-form values.
Ensure related sub-parts (label/input/help-text, card header/body) group correctly by default.
