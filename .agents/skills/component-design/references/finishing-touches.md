# Finishing touches

Small details separate "fine" from "polished":

- **Supercharge the defaults.** Replace default bullets, checkboxes, and links with custom
  icons/colors that fit the personality.
- **Accent borders.** A short colored edge adds personality cheaply —
  `border-l-4 border-primary-500` on cards, alerts, active nav.
- **Decorate backgrounds.** Break up plain surfaces with a subtle background-color change, a
  repeating pattern, or a simple shape — kept low-contrast.
- **Never overlook empty states.** Often the first thing a new user sees. Design it:
  illustration + short message + clear primary action. Don't render a blank table.
- **Use fewer borders.** Borders add clutter. Separate with a **shadow**, **two background
  colors**, or **extra spacing** first; reach for a border last.
- **Think outside the box.** Question the "obvious" layout — dropdowns can be rich panels,
  tables can become cards on mobile.

**For Shape:** every stateful component (`table`, `list`, `select`, data views) ships a
first-class **empty state** slot, and separators default to spacing/shadow over hard borders.
