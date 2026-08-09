@blaze

{{-- Blaze compiles this into a plain PHP function and calls it directly. It is not
     in the header family's boat: nothing here reads `@aware`, so the disagreement
     that keeps those files on Blade's pipeline has nothing to disagree about.

     `fold` is out for the reason the button's is -- the `config()` read below would
     be baked into the calling template the first time it compiled, and an
     application's published default would stop being read. `memo` is out because
     this component is mostly slot.

     The directive has to be the first thing in the file: Blaze looks for it with an
     anchored match, so a comment above it reads as no directive at all. --}}

@props([
    'level' => 2,
    'size' => null,
    'description' => null,
    'actions' => null,
])

@php
    // Only `size` comes from config, and `level` deliberately does not. A default
    // rung is a house style; a default place in the document outline is not
    // something an application can be right about, because every page's first
    // heading is an h1 and nothing else on it is.
    $defaults = array_filter((array) config('shape.components.heading'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    // The two props that look like one axis and are not, which is the whole idea of
    // this component: `level` is what the heading *is* and `size` is what it looks
    // like. A section three levels deep can be set in the largest type on the page
    // without claiming to be its first heading, and a page can carry an `<h1>` that
    // reads quietly. Tying them together is the thing that makes people reach for
    // the wrong element to get the right size.
    //
    // Validated to the six the language has, falling back rather than throwing, the
    // way an unknown rung does everywhere else. `filter_var` because `level="3"`
    // from a template and `:level="3"` from PHP should mean the same thing.
    $depth = filter_var($level, FILTER_VALIDATE_INT);
    $depth = $depth !== false && $depth >= 1 && $depth <= 6 ? $depth : 2;

    // Title and description per rung, as a pair, because the gap between the two is
    // the point: a description a step below its title reads as belonging to it, and
    // one at the same size reads as a second sentence of it. `md` is the section
    // heading a page is mostly made of; `lg` is the one at the top of it.
    //
    // `tracking-tight` on `lg` alone. Letter-spacing that suits text at 16px is too
    // loose at 30, and tightening the smaller rungs would cost legibility to fix a
    // problem they do not have.
    $rungs = [
        'xs' => ['text-sm', 'text-xs'],
        'sm' => ['text-base', 'text-sm'],
        'md' => ['text-xl', 'text-sm'],
        'lg' => ['text-3xl tracking-tight', 'text-base'],
    ];

    $rung = isset($rungs[$size]) ? $size : 'md';

    [$title, $sub] = $rungs[$rung];

    // `text-balance` so a title that wraps breaks into even lines rather than
    // leaving one word alone on the second -- which is what a heading does more
    // often than body text, being short and set large.
    $title = 'font-semibold text-ink text-balance '.$title;

    // Muted, which is the whole hierarchy here: the title is `text-ink` and the
    // description is not, so the two are told apart on colour and weight without the
    // description needing to be small enough to squint at. `text-pretty` for the
    // opposite reason to the title's `text-balance` -- a paragraph wants its last
    // line kept off a single word, not its whole block evened out.
    $sub = 'text-ink-muted text-pretty '.$sub;

    $help = is_string($description) && $description !== '' ? $description : null;

    // Trimmed, so a slot written across three lines counts as empty -- which is how
    // anyone who indents their Blade will write one they meant to leave out.
    $side = $actions !== null && trim((string) $actions) !== '';
@endphp

{{-- Three shapes, chosen by which props are set rather than by a mode flag, the way
     the field picks its chrome. A heading with nothing beside it is a heading: no
     wrapper, no landmark, no flex context that a single child would sit in for
     nothing. That is the same call `type="hidden"` makes on the input, and it is
     what keeps this component usable in the place headings mostly appear.

     `class` lands on whichever element is outermost, so a call site is always
     styling the thing the heading occupies rather than one of its parts. --}}
@if (! $side && $help === null)
    <h{{ $depth }} {{ $attributes->merge(['class' => $title]) }}>{{ $slot }}</h{{ $depth }}>
@else
    {{-- A `<header>` rather than a `<div>`, and it is the right element for exactly
         the reason header.blade.php must not claim the banner role: nested inside an
         `<article>` or a `<section>`, a `<header>` is introductory content for that
         region and nothing more. Which is what this is. --}}
    <header {{ $attributes->merge(['class' => $side ? 'flex items-start justify-between gap-4' : 'flex flex-col gap-1']) }}>
        @if ($side)
            {{-- `min-w-0` is load-bearing: a flex item's minimum size is its content,
                 so a long unbroken title would push the actions off the end of the
                 row instead of wrapping. --}}
            <div class="flex min-w-0 flex-col gap-1">
        @endif

        <h{{ $depth }} class="{{ $title }}">{{ $slot }}</h{{ $depth }}>

        @if ($help !== null)
            <p class="{{ $sub }}">{{ $help }}</p>
        @endif

        @if ($side)
            </div>

            {{-- `shrink-0` because the actions are buttons, and a button squeezed to
                 fit a long title wraps its own label. The title has `min-w-0` and
                 gives way instead, which is the right way round. --}}
            <div class="flex shrink-0 items-center gap-2">{{ $actions }}</div>
        @endif
    </header>
@endif
