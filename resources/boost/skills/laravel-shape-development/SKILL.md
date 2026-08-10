---
name: laravel-shape-development
description: >
  Configure and apply the Shape package in Laravel applications: install the theme and an icon
  set, then build forms, buttons and page chrome from the shape: Blade components.
license: MIT
metadata:
  author: Henrik Persson
---

# Shape

Use this skill when a Laravel application needs to integrate `onelegstudios/laravel-shape` — a set
of Blade UI components for buttons, form controls, icons and page chrome, styled with Tailwind CSS
v4 theme tokens.

## Primary Goal

- apply the `onelegstudios/laravel-shape` package's public API in the smallest correct way

## Workflow

### 1. Inspect the Laravel app context

- confirm the app is a Laravel project on PHP 8.3+ with Laravel 12 or 13
- confirm Tailwind CSS **v4.1+** is in use; Shape's theme requires it and will not work on v3
- find the app's entry stylesheet (usually `resources/css/app.css`)

### 2. Install

```bash
composer require onelegstudios/laravel-shape
php artisan shape:install
```

`shape:install` adds the theme import to the stylesheet, offers Lucide and Heroicons, and publishes
the icons Shape's own components render. It can run unattended:

```bash
php artisan shape:install --no-interaction --icons
php artisan shape:install --set=lucide --set=solid --default=solid
php artisan shape:install --no-css --no-icons --config
```

If the stylesheet is edited by hand instead, the import must be present or every component renders
unstyled:

```css
@import "tailwindcss";
@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";
```

That line defines the colour roles and tells Tailwind to scan the package's Blade views.

### 3. Verify the icons are published

Shape ships no icons; the app publishes the ones its components render. After any Shape upgrade:

```bash
php artisan shape:icon:check --strict
```

A non-zero exit with `not published` under `default/` means a component has no artwork and the view
will throw. Fix it by re-running `shape:install`, or directly:

```bash
php artisan shape:icon:add spinner error select-chevron checkbox-check checkbox-indeterminate
```

Add this to CI. It is the only way to catch an upgrade that starts drawing a new mark.

### 4. Use the components

Two syntaxes, same components: `<shape:button>` and `<x-shape::button>`.

**Button** — `variant` (`solid`/`soft`/`ghost`/`outline`), `color` (any theme role), `size`
(`xs`/`sm`/`md`/`lg`), `loading`, `icon`, `icon-trailing`, `icon-set`, `square`:

```blade
<shape:button variant="solid" color="primary" type="submit" :loading="$saving">Save</shape:button>
<shape:button icon="trash-2" color="danger" aria-label="Delete" />
```

An icon-only button needs an `aria-label`; Shape will not invent one. Its square padding is
inferred from an empty slot — add `square` when the tag is written across two lines.

**Form controls** — `input`, `select`, `textarea`, `checkbox`, `radio`, `switch`, `file`, `range`,
`color`. All take `size`, `invalid`, and the `label`/`description`/`description-trailing` shorthand
that assembles a whole field:

```blade
<shape:input label="Email" description="We never share it." type="email" wire:model="email" />

<shape:select label="Plan" wire:model="plan">
    <option value="free">Free</option>
    <option value="pro">Pro</option>
</shape:select>

<shape:textarea label="Bio" rows="4" autosize wire:model="bio" />

<shape:file label="Avatar" accept="image/*" wire:model="avatar" />

<shape:checkbox label="Email me about releases" wire:model="notify" value="1" />

<shape:switch label="Enable two-factor authentication" wire:model.live="twoFactor" />

<shape:range label="Volume" min="0" max="100" wire:model="volume" />

<shape:color label="Brand colour" wire:model="brand" />
```

A `switch` is `<input type="checkbox" role="switch">` underneath. Use one where flipping it applies
the setting, a checkbox where the value is submitted with the form. Unlike a checkbox, its `value`
is not folded into the id — a switch is never one of a group.

Groups of checkboxes or radios go inside a field, which carries the name and prints one message:

```blade
<shape:field name="plan" legend="Plan">
    <shape:radio value="free" label="Free" description="One project." />
    <shape:radio value="pro" label="Pro" />
</shape:field>
```

**A group takes `legend`, not `label`.** Naming `legend` renders a real `<fieldset>`/`<legend>`, which
is what makes the set announce as a group; a `label` on a group is a `for` pointing at an id no option
carries, because the options are `plan-free` and `plan-pro`. Naming both draws the legend only. The
group's description is named on the fieldset; the message is not, because every option already carries
it. `class` on a group lands on the fieldset, and the column spacing lives on a wrapper inside it.

Controls find their own field name in `name`, in `wire:model` (modifiers included), or in the
enclosing `<shape:field>`, then look themselves up in the validation error bag — so a failed request
styles them with nothing at the call site saying "invalid". Override with `:invalid="true"` or
`:invalid="false"`.

**One rule to remember:** on the box-wrapped controls (`input`, `select`, `textarea`, `file`), `class`
lands on the wrapper and every other attribute lands on the control. Use `max-w-*`, not `w-*`.

`<shape:input type="hidden">` is the exception: it renders the bare `<input>` with no box, no derived
id and no chrome, so a hidden field opens no gap in the form.

**Only `input` takes `prefix` and `suffix`** — words at the ends of the field, muted and inside the
box. Use the string attribute for words, or nest `<shape:input.prefix>` / `<shape:input.suffix>` for
markup a string cannot carry; both render the same component, and a nested one takes its rung and
treatment from the field it stands in. `affix="segmented"` puts the affix on a plate with a divider
instead of in the flow; the default is `inline` and is configurable at `shape.components.input.affix`:

```blade
<shape:input prefix="$" suffix="USD" type="number" wire:model="amount" />

<shape:input prefix="$" suffix="USD" affix="segmented" type="number" wire:model="amount" />

<shape:input value="{{ $key }}" readonly>
    <shape:input.suffix><shape:button variant="ghost" size="xs">Copy</shape:button></shape:input.suffix>
</shape:input>
```

Do not write a prop and nest a component on the same side — you get two affixes, because the input
cannot see what its children rendered. An affix is not a chrome prop, so it does not expand a bare
input into a field, and `type="hidden"` drops it. Put a control in the `inline` treatment, not on the
plate — the plate is padded for text, and anything focusable in the box rings the field's own focus
outline while anything `disabled` in it greys the whole field. An affix is decorative to assistive
tech: a screen reader reads the control's name and value, not the text beside it, so put a unit that
carries meaning in the `label` or `description` as well.

`range` and `color` have no wrapper — the control is the box, so `class` lands on it directly. Both
take the input's four heights, so a slider or a swatch stands level with the field beside it. A
`color` is square and shows no hex; bind a `<shape:input>` to the same model to show the value. A
`range` has no filled portion — CSS cannot read a slider's value, and Shape ships no JavaScript.

**Compose by hand** when the shorthand cannot say it. Add `aria-describedby` yourself here — the
parts cannot see which of them rendered:

```blade
<shape:field name="email">
    <shape:label>Email</shape:label>
    <shape:description>We never share it.</shape:description>
    <shape:input wire:model="email" aria-describedby="email-description" />
    <shape:error />
</shape:field>
```

The parts are `<shape:field>`, `<shape:label>`, `<shape:legend>`, `<shape:description>` and
`<shape:error>`. `<shape:legend>` is for a `<fieldset>` you wrote yourself; for a Shape field, use the
`legend` prop.

**Icon** — `name`, `set`, `size`, `color`, `label`. Names must be published first:

```blade
<shape:icon name="check" size="sm" />
```

**Page furniture** — `header` and `heading`, the two components that are not part of a form.

`<shape:header>` is the bar across the top of a page: `size` (the usual four rungs), `container`
(`3xl`…`7xl` or `full`, where the contents stop centring) and `sticky`. It renders a full-width
`<header>` with a track inside it, so `class` restates the chrome and the contents still line up with
the page. Its three parts are `header.brand` (an `<a>` with `href`, a `<div>` without),
`header.nav` (a labelled `<nav>`) and `header.item` (a link, with `current` for the page you are on):

```blade
<shape:header sticky>
    <shape:header.brand href="/">Acme</shape:header.brand>

    <shape:header.nav class="ms-auto">
        <shape:header.item href="/docs" current>Docs</shape:header.item>
        <shape:header.item href="/blog">Blog</shape:header.item>
    </shape:header.nav>

    <shape:button size="sm" variant="solid" color="primary">Sign in</shape:button>
</shape:header>
```

Items take the header's rung unless they name their own. `current` writes `aria-current="page"` as
well as the tint. The nav is labelled `Main`; give a second nav in the same bar its own `aria-label`.
The component writes **no** `role="banner"` — a `<header>` outside an article, aside, main, nav or
section already is that landmark, and the component is equally usable where it would be wrong. Shape
ships no JavaScript, so hiding the nav on a narrow viewport and opening it from a button is the
application's (`class="hidden md:flex"` plus your own Alpine or Livewire toggle).

`<shape:heading>` is a title with its description and actions. `level` is the outline (`<h1>`…`<h6>`,
default `2`) and `size` is the type — two props because they are two facts, so a deep section can read
large without claiming to be the page's first heading:

```blade
<shape:heading level="1" size="lg" description="Everyone with access to this workspace.">
    <x-slot:actions>
        <shape:button size="sm" variant="solid" color="primary">Invite</shape:button>
    </x-slot:actions>

    Team members
</shape:heading>
```

A title on its own renders a bare `<h*>` with no wrapper; a `description` or an `actions` slot wraps
it in a `<header>` and stacks or rows accordingly. `class` lands on whichever came out outermost.

### 5. Configure defaults, if asked

```bash
php artisan vendor:publish --tag="shape-config"
```

`config/shape.php` has
`components.{button,input,select,textarea,checkbox,radio,switch,file,range,color,header,heading}`
(a `size` each, plus `variant` and `color` on the button, `affix` on the input and `container` on the
header) and `icons.{path,set,sets,aliases}`. The header's `sticky` and the heading's `level` are not
config keys and are named per call site. Config is merged
one level deep, so a published file replaces each block wholesale — do not delete keys from it.

Rebrand through the theme rather than the config or the views:

```css
@theme {
    --color-primary-700: oklch(0.5 0.2 264);
    --color-surface: light-dark(var(--color-stone-50), var(--color-stone-900));
}
```

Dark mode is handled in the theme with `light-dark()`, so components carry no `dark:` classes. It
follows the OS; add the `dark` or `light` class to force a subtree.

Page surfaces are named for the material rather than a role: `--color-surface` and
`--color-surface-muted` for a control's face, `--color-ink` and `--color-ink-muted` for text, and
`--color-chrome` and `--color-hairline` for a page's furniture. `chrome` ships the same value as
`surface` and is separate so that a tinted header does not tint every field on the page.

## Rules, References, and Templates

Read before executing:

- `docs/README.md` — the index, listing every page in reading order
- `docs/components.md` — the catalogue; `docs/components/<name>.md` for one component's props and
  states, with helper components documented on their parent's page
- `docs/icons.md` — sets, semantic names, and the five icon commands
- `docs/theming.md` and `docs/configuration.md` — theme tokens and config keys
- `docs/installation.md` and `docs/performance.md` — the installer's steps, and what Blaze folds

Commands: `shape:install`, `shape:icon` (index), `shape:icon:add`, `shape:icon:remove`,
`shape:icon:update`, `shape:icon:check`.

Publish tags: `shape` (config, views, lang and css together), `shape-config`, `shape-views`,
`shape-lang`, `shape-css`, `shape-icons`.

## Examples

- Adding a settings form: reach for the `label`/`description` shorthand on each control, one
  `<shape:button variant="solid" color="primary" type="submit">` to save, and let the error bag drive
  the invalid states rather than passing `:invalid` anywhere.
- Adding a role the package does not ship: define its ramp and eight surface tokens in `@theme`, then
  repeat the five `@source inline()` lines from `shape.css` with the new role name. Every component
  accepts it immediately via `color="ocean"`.
- Testing a consuming app's form: assert on rendered behaviour — that the control carries the right
  `name`, that a seeded error bag produces `aria-invalid="true"`, that a label's `for` matches the
  control's `id`. Publish the required icons in the test setup, or any component drawing a mark will
  throw.

## Anti-patterns

- do not install `@tailwindcss/forms` for Shape's sake; the controls style themselves and clear that
  plugin's own select chevron if the app uses it for other reasons
- do not publish the views (`shape-views`) to restyle something — that forks the component and gives
  up package updates to it; override theme tokens instead
- do not put a packaged semantic name in `icons.aliases` unless deliberately repointing its artwork
- do not use `Onelegstudios\Shape\Control` or `Onelegstudios\Shape\Fields`; both are `@internal`
- do not skip `php artisan view:clear` after publishing icons or upgrading Shape
- do not document package internals here; keep the skill focused on adoption in Laravel apps
