@blaze(fold: true)

{{-- `@blaze`, for the family's reason: see the top of header.blade.php.

     `fold: true`, and the translated landmark name below is the one thing that had
     to move before it could go on. `__()` resolves against the request's locale, so
     a folded call would serve whichever locale compiled the view to everyone after
     it -- no invalidation repairs that, because nothing on disk changed. The button
     has the same problem with its spinner's label and the same answer: an island,
     which Blaze sets aside before folding and re-injects uncompiled, so the lookup
     happens on render as it always did. --}}

{{-- No `@props`, because there is nothing here to decide. Everything this component
     has to say it says through `merge`, which is also what leaves `aria-label`
     overridable -- `merge` sets a non-class attribute as a default and steps aside
     when the call site named one, so a page with two navs in its bar can tell them
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

    // What `merge` used to decide, decided here instead, because the default and the
    // caller's value no longer reach the element by the same route: theirs is baked
    // with the rest of the bag, the package's is looked up per render. Asking
    // whether they named one is the same question `merge` asked, and it is settled
    // when the view compiles either way.
    $own = $attributes->get('aria-label');

    $labelled = is_string($own) && $own !== '';

    $defaults = ['class' => $classes];

    // Their own value, put back at the head of the defaults so `merge` renders it
    // where it has always rendered it. `merge` keeps a key in the position its
    // default holds, and ours is no longer among them -- so without this line the
    // attribute would move to the end of the tag for everyone who named one, which
    // is a diff in every consuming application's markup and buys nothing.
    if ($labelled) {
        $defaults = ['aria-label' => $own] + $defaults;
    }
@endphp

{{-- Translated rather than hardcoded, because a landmark's name is read aloud. It
     is the second string in `lang/en/messages.php` and it earns its place there for
     the reason the first one does: the alternative is an English word baked into a
     package that a Swedish application cannot reach.

     The island is entered unconditionally and decides inside, which is a
     restriction rather than a preference. Blade matches a directive with a leading
     `\B`, so an `@endif` written straight after `@endunblaze` is preceded by a word
     character, never compiles, and reaches the browser as four literal characters
     -- and only on the renders where the fold declined, since a successful fold
     evaluates the branch away. Nothing between the two directives is safe unless it
     is also acceptable inside the tag, so the branch moves into the echo instead
     and `$labelled` rides the scope to reach it.

     The trailing space belongs to the attribute rather than to the tag, so a nav
     that named its own landmark renders `<nav class=` with one space before it, the
     way it always has. --}}
<nav @unblaze(['labelled' => $labelled]){!! $scope['labelled'] ? '' : 'aria-label="'.e(__('shape::messages.header.nav')).'" ' !!}@endunblaze{{ $attributes->merge($defaults) }}>
    {{ $slot }}
</nav>
