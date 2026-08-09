{{-- No `@blaze`, and this is the file that decides it for the family: the `@aware`
     below is the thing Blaze and Blade compile differently. See the top of
     header.blade.php. --}}

{{-- No `@props` for `size`, which is the decision rather than an omission, and the
     hazard is the one input/prefix.blade.php spells out: `@aware` assigns
     unconditionally, so a prop of the same name would let the enclosing header beat
     a value written right here -- the wrong way round. Undeclared, it stays on the
     bag where the read below can put the nearer answer first.

     `href` is not declared either, and for a plainer reason: an item is always a
     link, so its `href` has nothing to decide and rides the bag to the element. The
     brand declares its own because it switches element on it. --}}
@props(['current' => false])

@aware(['size' => null])

@php
    // Own attribute first, then the header around it, then config. The bag read is
    // what settles the precedence -- `@aware` above has already assigned, so this is
    // the only spelling that puts the nearer answer first.
    $own = $attributes->get('size');
    $size = is_string($own) && $own !== '' ? $own : $size;

    // Reached when nothing above named a rung, which outside a header is always: an
    // item is usable on its own, and a stray one should look like the items in a bar
    // rather than like nothing at all.
    $defaults = array_filter((array) config('shape.components.header'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $here = filter_var($current, FILTER_VALIDATE_BOOLEAN);

    // The button's rungs, one step tighter. A nav item is not a control competing
    // for a press -- it is a word you read and sometimes click -- so it sits inside
    // the bar rather than filling it, and `py` is what buys that.
    $rungs = [
        'xs' => 'px-2 py-1 text-xs',
        'sm' => 'px-2.5 py-1 text-sm',
        'md' => 'px-3 py-1.5 text-sm',
        'lg' => 'px-3.5 py-2 text-base',
    ];

    $rung = isset($rungs[$size]) ? $size : 'md';

    $classes = 'inline-flex items-center gap-2 rounded-md font-medium transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 focus-visible:outline-neutral-ring '.$rungs[$rung];

    // The current page is louder than the rest on ink and on a tint behind it, and
    // it is the `aria-current` below that carries the same fact to a screen reader.
    // Two channels for one meaning, which is the rule: a page you cannot tell you
    // are on is the failure this state exists to avoid, and colour alone would
    // reproduce it for anyone not looking at the colour.
    //
    // The resting item is muted rather than full ink, which is what makes the
    // current one legible without needing a heavier weight than its neighbours --
    // hierarchy on colour, so every item in the bar stays the same shape.
    $classes .= $here
        ? ' bg-neutral-tint text-ink'
        : ' text-ink-muted hover:bg-neutral-tint hover:text-ink';

    // `except` is mandatory rather than hygiene: `size` was never declared a prop,
    // so nothing has taken it off the bag, and a `size` written on this tag would
    // work *and* render as a stray attribute on the anchor.
    $attributes = $attributes->except('size')->merge(array_merge(
        ['class' => $classes],
        $here ? ['aria-current' => 'page'] : [],
    ));
@endphp

<a {{ $attributes }}>{{ $slot }}</a>
