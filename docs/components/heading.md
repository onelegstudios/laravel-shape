# Heading

The title at the top of a page, a section, or an article, with the description and the buttons that
usually come with it.

```blade
<shape:heading level="1" size="lg" description="Everyone with access to this workspace.">
    Team members
</shape:heading>
```

## Level and Size Are Not the Same Axis

`level` is what the heading **is** — which `<h1>`…`<h6>` it renders, and therefore where it sits in
the document outline. `size` is what it **looks like**. They are separate props because they are
separate facts, and tying them together is what makes people reach for the wrong element to get the
right size:

```blade
<shape:heading level="3" size="lg">Still an h3</shape:heading>

<shape:heading level="1" size="sm">Still the page title</shape:heading>
```

`level` defaults to `2`, which is the heading a section gets; a page's `<h1>` is the exception and
names itself. A level outside 1–6 falls back to `2` rather than throwing, the way an unknown rung
does everywhere else.

`size` is the usual four rungs. `md` is what a page is mostly made of, `lg` is the one at the top of
it, and `lg` is the only rung that tightens its tracking — letter-spacing measured for 16px text is
loose at 30, and the smaller rungs do not have the problem.

## Three Shapes

What the component renders depends on what you gave it, rather than on a mode you pick.

A title on its own is a heading. No wrapper, no landmark, nothing for a single child to sit inside:

```blade
<shape:heading>Team members</shape:heading>
```

```html
<h2 class="font-semibold text-ink text-balance text-xl">Team members</h2>
```

Add a `description` and it wraps in a `<header>` and stacks:

```blade
<shape:heading description="Everyone with access to this workspace.">
    Team members
</shape:heading>
```

The description is muted rather than shrunk to a whisper — the two are told apart on weight and
colour, so the description stays comfortable to read. It steps down one rung from the title, which is
what makes it read as belonging to the title instead of as a second sentence of it.

Add an `actions` slot and the same stack becomes a row:

```blade
<shape:heading description="Everyone with access to this workspace.">
    <x-slot:actions>
        <shape:button variant="solid" color="primary" size="sm">Invite</shape:button>
    </x-slot:actions>

    Team members
</shape:heading>
```

A long title wraps rather than pushing the buttons off the end of the row.

`class` lands on whichever element came out outermost, so you are always styling the thing the
heading occupies rather than one of its parts:

```blade
<shape:heading class="mb-8">Team members</shape:heading>
```

## Inside an Article

The `<header>` this component renders when it has more than a title is the right element inside an
`<article>` or a `<section>`: nested there, a `<header>` is introductory content for that region and
nothing more. It is not a landmark and does not claim to be one — that distinction is the same one
the [Header](header.md) component's page bar has to get right from the other direction.

```blade
<article>
    <shape:heading level="2" description="Published 8 August 2026">
        What we changed this month
    </shape:heading>

    <p>…</p>
</article>
```

---

[← Header](header.md) · [Index](../README.md) · [Icons →](../icons.md)
