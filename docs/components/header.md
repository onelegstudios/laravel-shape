# Header

The bar across the top of a page: a wordmark on the left, some links, whatever an application keeps
on the right. It is the first component here that is not part of a form, and the first that draws a
surface no colour role has an answer for — a header is not `primary` or `danger`, it is furniture.

```blade
<shape:header>
    <shape:header.brand href="/">Acme</shape:header.brand>

    <shape:header.nav>
        <shape:header.item href="/docs" current>Docs</shape:header.item>
        <shape:header.item href="/blog">Blog</shape:header.item>
    </shape:header.nav>
</shape:header>
```

Three parts, and none of them is required: the header is a flex row and the slot takes anything.
Use the parts for the things they are, and ordinary markup for everything else.

## Two Elements, One Component

The header renders a `<header>` that runs edge to edge and a track inside it that stops where your
content stops. That split is what lets the chrome reach the window while the links line up with the
page below — painting both on one element gives you either a centred bar with page showing either
side of it, or content pressed against the viewport.

`class` lands on the outer bar, so restating the chrome is a one-liner:

```blade
<shape:header class="border-none bg-transparent">
```

## Width

`container` is where the track stops centring. It takes `3xl` through `7xl`, or `full`:

```blade
<shape:header container="5xl">
```

`full` is for an application shell that has a sidebar and no centred column for the bar to agree
with. Anything unrecognised falls back to the default, which is `7xl` until you
[set another one](../configuration.md).

## Density

`size` is the same four rungs as everything else, and it sets the bar's height, how far its contents
stand off the edge of a narrow viewport, and how far the brand stands off the nav:

```blade
<shape:header size="sm">
```

The gaps are wider than a button's at the same rung on purpose. A button's `gap` holds an icon off a
label; this one holds whole regions apart, and a nav sitting a button's gap away from a wordmark
reads as part of it.

Items take the rung from the header they stand in, so a `sm` bar has `sm` items without being told
twice. An item can still name its own, and it wins:

```blade
<shape:header size="sm">
    <shape:header.item href="/docs" size="md">Docs</shape:header.item>
</shape:header>
```

## Sticky

`sticky` pins the bar to the top of the window as the page scrolls:

```blade
<shape:header sticky>
```

It sits at `z-40`, which leaves room above for the dialogs and toasts an application puts over it.
There is no config default for it, and that is deliberate: whether a bar follows the scroll belongs
to the page rather than to the application, and a marketing page and an admin panel disagree about it
in the same codebase.

## The Parts

`header.brand` is the wordmark. Give it an `href` and it renders an `<a>` with a focus ring; leave it
off and it renders a `<div>`, which is what you want in a shell that is already home. It refuses to
compress, so a long nav wraps or scrolls before the logo squeezes.

```blade
<shape:header.brand href="/">
    <shape:icon name="sparkles" />
    Acme
</shape:header.brand>
```

`header.nav` is a `<nav>` with an `aria-label` on it, because a landmark is only worth having if a
screen reader can tell it from the one in your footer. It is labelled `Main` unless you say
otherwise, which is what a bar with a second nav in it should do:

```blade
<shape:header.nav aria-label="Account">
```

`header.item` is a link in that nav. `current` marks the page you are on — in colour, and in
`aria-current="page"`, because a page you cannot tell you are on is exactly the failure this state
exists to avoid, and colour alone reproduces it for anyone not looking at the colour.

```blade
<shape:header.item href="/docs" current>Docs</shape:header.item>
```

Items are muted at rest and take full ink on hover and when current, so the bar tells you where you
are without any item changing shape.

## Layout Around the Parts

The header holds no opinion about where its contents sit. Push the nav and everything after it to the
right in the usual way:

```blade
<shape:header>
    <shape:header.brand href="/">Acme</shape:header.brand>

    <shape:header.nav class="ms-auto">
        <shape:header.item href="/docs">Docs</shape:header.item>
    </shape:header.nav>

    <shape:button size="sm">Sign in</shape:button>
</shape:header>
```

## Accessibility

The component renders a `<header>` and does **not** write `role="banner"`. A `<header>` that is not
inside an `<article>`, `<aside>`, `<main>`, `<nav>` or `<section>` already is the banner landmark, so
the role would restate it there — and this component is just as usable at the top of a `<main>`,
where a banner is the one thing it must not claim to be. If you have put the bar somewhere the
implicit role does not apply and you want it anyway, write it: `<shape:header role="banner">`.

## Mobile

Shape ships no JavaScript, and a disclosure is JavaScript. The header gives you the bar; hiding the
nav on a narrow viewport and opening it from a button is yours, and it is a few lines in Alpine or
Livewire:

```blade
<shape:header x-data="{ open: false }">
    <shape:header.brand href="/">Acme</shape:header.brand>

    <shape:header.nav class="ms-auto hidden md:flex">
        <shape:header.item href="/docs">Docs</shape:header.item>
    </shape:header.nav>

    <shape:button size="sm" icon="menu" class="ms-auto md:hidden" x-on:click="open = ! open" />
</shape:header>
```

## Theming

The bar is painted with two tokens of its own, `--color-chrome` and `--color-hairline`. Both ship the
same values the page already uses, and they are separate tokens so that tinting your header does not
tint every text field on the page:

```css
@theme {
    --color-chrome: light-dark(var(--color-neutral-50), var(--color-neutral-950));
}
```

[Theming](../theming.md) has the rest.

---

[← Icon](icon.md) · [Index](../README.md) · [Heading →](heading.md)
