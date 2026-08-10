@blaze(fold: true)

{{-- `@blaze`, and the reason is the family's rather than this file's own.
     `header/item.blade.php` reads the `size` below through `@aware`, and the two
     `@aware` implementations differ -- the disagreement field.blade.php sets out
     at length. A header compiled by one and an item by the other would size from
     two different places, and the markup would render either way, so the four
     files move together. Nothing to save and restore here: no `@aware` in this
     file.

     `fold: true` is this file's own, and what it turns on is that `size` has to
     keep reaching the items after this component stops existing. Blaze arranges
     it: a folded call site whose children read `@aware` is wrapped in a
     `pushData()` of the attributes written on the tag, so an item inside still
     finds the rung. Where the tag named none there is nothing to push and nothing
     to find -- which is the answer this file already gave, since `@aware` reads
     the call site's attributes rather than the `$size` resolved below.

     The `config()` read is a compile-time input now, the trade the button
     documented: ShapeServiceProvider stamps config/shape.php as a fold dependency,
     so editing a published default invalidates the views that baked it, but a
     default set at runtime no longer reaches a folded header. --}}

@props([
    'size' => null,
    'container' => null,
    'sticky' => false,
])

@php
    // The floor-plus-config idiom every styling prop in this package uses:
    // `mergeConfigFrom` merges one level deep, so a consumer who publishes the
    // config file and drops a key must not get a bar with no chrome on it.
    $defaults = array_filter((array) config('shape.components.header'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';
    $container ??= $defaults['container'] ?? '7xl';

    // Not read from config, and `array_filter(..., 'is_string')` above is half the
    // reason -- a boolean would not survive it. The other half is that whether a
    // bar follows the scroll belongs to the page rather than to the application.
    //
    // `filter_var` rather than a truth test because the interesting call site
    // passes a variable, and a `sticky="false"` from a template that stringified
    // one should not pin the bar to the top of the page.
    $pinned = filter_var($sticky, FILTER_VALIDATE_BOOLEAN);

    // Two elements, and the split is what makes the component work at any width:
    // the chrome belongs to the bar, which runs edge to edge, and the layout
    // belongs to a track inside it, which stops where the page's content stops.
    // Painting both on one element would either leave a centred bar floating with
    // page showing either side of it, or run the content to the viewport edge.
    $bar = 'w-full border-b border-hairline bg-chrome';

    // `z-40` leaves room above: a header is furniture, and a dialog or a toast the
    // application puts over it should not have to out-number a package's guess.
    $bar .= $pinned ? ' sticky top-0 z-40' : '';

    // Density, on the same four rungs as everything else. Three things change and
    // none of them is derived from the others: `py` is the bar's height, `px` how
    // far its contents stand off the page's edge on a viewport too narrow for the
    // track, and `gap` how far the brand stands off the nav.
    //
    // The gaps are wider than a button's at the same rung, and deliberately: these
    // hold whole regions apart rather than an icon off a label, and a nav that
    // sits a `gap-2` away from a wordmark reads as part of it.
    $rungs = [
        'xs' => 'gap-4 px-3 py-1.5',
        'sm' => 'gap-5 px-4 py-2',
        'md' => 'gap-6 px-4 py-3',
        'lg' => 'gap-8 px-6 py-4',
    ];

    // Where the bar stops centring its contents. A closed set rather than a free
    // measurement, for the reason the whole library is: `container="5xl"` is a
    // decision an application can make once in config, `max-w-[68rem]` is a number
    // somebody has to keep in step with the page it sits above.
    //
    // `full` is here because a bar is the one piece of furniture that sometimes
    // wants the whole viewport -- an application shell with a sidebar has no
    // centred column for the header to agree with.
    $widths = [
        '3xl' => 'max-w-3xl',
        '4xl' => 'max-w-4xl',
        '5xl' => 'max-w-5xl',
        '6xl' => 'max-w-6xl',
        '7xl' => 'max-w-7xl',
        'full' => 'max-w-full',
    ];

    // Both closed, so an unknown value falls back rather than throwing. The rung is
    // resolved to its name rather than its classes because `size` stays on the
    // component's data for `header.item` to read through `@aware`.
    $rung = isset($rungs[$size]) ? $size : 'md';

    $width = $widths[$container] ?? $widths['7xl'];
@endphp

{{-- A `<header>` and no `role="banner"`, which is the one accessibility decision in
     this file worth writing down. A `<header>` that is not inside an `<article>`,
     `<aside>`, `<main>`, `<nav>` or `<section>` already *is* the banner landmark,
     so the role would be a restatement here and a lie one level down -- and this
     component is just as usable at the top of a `<main>`, where a banner is exactly
     what it must not claim to be. A call site that has put the bar somewhere the
     implicit role does not apply, and wants it anyway, can merge the attribute.

     `class` lands here rather than on the track inside, so a call site can restate
     the chrome -- a transparent bar over a hero image, a heavier border -- without
     reaching past the component for the thing it most often wants to change. --}}
<header {{ $attributes->merge(['class' => $bar]) }}>
    <div class="mx-auto flex w-full items-center {{ $width }} {{ $rungs[$rung] }}">
        {{ $slot }}
    </div>
</header>
