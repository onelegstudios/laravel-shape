@blaze

{{-- `@blaze`, for the family's reason: see the top of header.blade.php. --}}

{{-- `href` is a prop here and is not one on `header.item`, which looks like an
     inconsistency and is not. This component switches element on it -- a wordmark
     that links home is an `<a>`, one in an application shell that is already home
     is not -- so the value has to be read before anything renders. An item is
     always a link, so its `href` has nothing to decide and rides the attribute bag
     to the element the way any other attribute would. --}}
@props(['href' => null])

@php
    // Guarded like every other interpolated prop in the package: a bare
    // `<shape:header.brand href>` arrives as `true` and would render `href="1"`,
    // which is a working link to the wrong place rather than a visible mistake.
    $link = is_string($href) && $href !== '' ? $href : null;

    // `shrink-0` because the brand is the one thing in the bar that must not
    // compress -- a long nav should scroll or wrap before a wordmark squeezes.
    //
    // `font-semibold text-ink` is the whole of the hierarchy: the brand is the
    // loudest thing in a bar whose items are all muted, and it gets there on weight
    // and colour rather than on size, so a wordmark and a logo sit on one line.
    //
    // `me-auto` is not here on purpose. Where the brand sits relative to everything
    // else is the bar's layout rather than the brand's, and a call site that wants
    // the nav pushed right says so once on the thing it wants pushed.
    $classes = 'inline-flex shrink-0 items-center gap-2 font-semibold text-ink';

    // Only on the link path, because only the link path is focusable. Radius so the
    // ring has something to follow, and no hover treatment at all: a logo that
    // fades or tints under the pointer reads as a control rather than as the way
    // home, and the cursor already says it is clickable.
    if ($link !== null) {
        $classes .= ' rounded-md focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-ring';
    }

    $attributes = $attributes->merge(['class' => $classes]);
@endphp

@if ($link !== null)
    <a href="{{ $link }}" {{ $attributes }}>{{ $slot }}</a>
@else
    <div {{ $attributes }}>{{ $slot }}</div>
@endif
