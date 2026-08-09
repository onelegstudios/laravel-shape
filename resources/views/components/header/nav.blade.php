{{-- No `@blaze`, for the family's reason: see the top of header.blade.php. --}}

{{-- No `@props`, because there is nothing here to decide. Everything this component
     has to say it says through `merge`, which is also what leaves `aria-label`
     overridable -- `merge` sets a non-class attribute as a default and steps aside
     when the call site names one, so a page with two navs in its bar can tell them
     apart without the component needing a prop for it. --}}
@php
    // `flex items-center gap-1` and nothing else. The nav holds items apart; the
    // header holds the nav off the brand. Keeping the two gaps in two components is
    // what lets an application put something other than items in here -- a
    // dropdown, a search field -- without inheriting a rhythm measured for links.
    //
    // The gap is deliberately tight. Items carry their own padding and paint a
    // background on hover, so the space between two of them is that padding twice
    // over already; a wider gap here would read as two groups rather than one list.
    $classes = 'flex items-center gap-1';
@endphp

{{-- Translated rather than hardcoded, because a landmark's name is read aloud. It
     is the second string in `lang/en/messages.php` and it earns its place there for
     the reason the first one does: the alternative is an English word baked into a
     package that a Swedish application cannot reach. --}}
<nav {{ $attributes->merge(['aria-label' => __('shape::messages.header.nav'), 'class' => $classes]) }}>
    {{ $slot }}
</nav>
