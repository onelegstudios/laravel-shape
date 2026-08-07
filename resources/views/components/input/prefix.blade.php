{{-- No `@blaze`, and this file has its own reason rather than field.blade.php's.

     The input renders this component itself for the `prefix` prop, from inside its
     own template -- and at the top level of a page that is a component whose stack
     has already been popped. Blade copes because
     `ManagesComponents::getConsumableComponentData()` checks `currentComponentData`
     before it walks the ancestors, and the input's data is sitting there.
     `BlazeRuntime::getConsumableData` only walks the ancestors. So a Blaze-compiled
     affix would find nothing on that path and quietly render every prefix at the
     configured default size rather than the field's own. --}}

{{-- No `@props` for either of these, which is the decision rather than an omission.
     `@aware` assigns unconditionally, so a prop of the same name would let the
     enclosing input beat a value written right here -- the wrong way round, and the
     hazard label.blade.php spells out at length for its `for`. Undeclared, they stay
     on the bag where the read below can put the nearer answer first.

     Both are the input's to set rather than yours. The prop path passes them
     resolved, because the input has already read config and floored an unknown rung;
     a nested call site inherits them from the field it is standing in. --}}

@aware(['size' => null, 'affix' => null])

@php
    // Own attribute first, then what the input around it said, then config. The bag
    // read is what settles the precedence -- `@aware` above has already assigned, so
    // this is the only spelling that puts the nearer answer first.
    $own = $attributes->get('size');
    $size = is_string($own) && $own !== '' ? $own : $size;

    $own = $attributes->get('affix');
    $affix = is_string($own) && $own !== '' ? $own : $affix;

    // The same floor-plus-config idiom the input uses, for the same reason: config is
    // merged one level deep, so a consumer who publishes the file and drops a key
    // gets nothing back from the package.
    //
    // Reached only when nothing above named a rung -- which outside an input is
    // always. A stray affix is the call site's own markup and renders as a plain
    // muted word; `inline` being the floor is what keeps that harmless, because a
    // segmented plate's bleed is not inert outside a flex box and would pull itself
    // out of whatever it landed in.
    $defaults = array_filter((array) config('shape.components.input'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $affix ??= $defaults['affix'] ?? 'inline';

    $affix = $affix === 'segmented' ? 'segmented' : 'inline';

    // The input's own type scale, and the one number in this file that has to agree
    // with a table in another. A test reads `text-*` out of the control and out of
    // this span in one render and compares them, which is what keeps the pair from
    // drifting -- the same shape as the test holding the input's padding to the
    // button's.
    $type = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    // What a segmented plate needs to reach the frame's border, per rung. Four values,
    // and every one of them is the rung's own:
    //
    //   `-my-*`  cancels the frame's `py`, so the plate's top and bottom edges land on
    //            the inside of its border. It works with `self-stretch` below:
    //            flexbox sizes a stretched item so that its *margin* box fills the
    //            line, so a negative margin makes the border box overhang by exactly
    //            that much. Only while the plate has no height of its own -- an `h-*`
    //            here would settle the cross size first and this would quietly stop
    //            working.
    //   `-ms-*`  cancels the frame's leading padding, so the plate reaches the border
    //            rather than floating a rung's worth inside it.
    //   `px-*`   is the plate's own padding, the frame's number again, so an affix
    //            stands off its edges the way the value stands off the field's.
    //   `me-*`   is the one worth writing down. The frame's `gap` already holds the
    //            control off the plate, but a gap is smaller than a padding, so the
    //            value would start closer to the divider than it starts from the
    //            border in a plain field. Closing that takes exactly one more `gap`,
    //            because `px` is twice `gap` at every rung: 8/4, 12/6, 16/8, 20/10.
    //
    // `py` is the same number as `gap` at every rung as well, which is why the
    // vertical cancel and the horizontal top-up read as the same token on each line.
    // Both scales step by the same amount, so it is arithmetic rather than
    // coincidence -- written down so a later reader does not take the repetition for
    // a copy-paste slip and "fix" one of them.
    //
    // These numbers are the input's `$rungs` read backwards, and they live in a
    // different file from it now. A test reads `px-*` off the frame and `-ms-*` off
    // this plate in one render and asserts they match, at every rung; it is the only
    // thing holding the two tables together.
    //
    // Logical rather than physical, so an RTL page moves the plate and the room it
    // takes out of the same table.
    $plate = [
        'xs' => '-my-1 -ms-2 px-2 me-1',
        'sm' => '-my-1.5 -ms-3 px-3 me-1.5',
        'md' => '-my-2 -ms-4 px-4 me-2',
        'lg' => '-my-2.5 -ms-5 px-5 me-2.5',
    ];

    // A closed set, so an unknown rung falls back rather than rendering unsized type
    // against a plate that bled by a different amount.
    $rung = isset($type[$size]) ? $size : 'md';

    // `order-first` unconditionally, and it is forced rather than chosen. The input
    // renders this component itself for the `prefix` prop, and a call site nests the
    // same component in the input's slot -- two different places in the markup, one
    // set of classes, so the class has to put the affix in the right place from
    // either. Ordering it out to the edge is the only answer that works from both.
    //
    // What that costs: an affix sits outside a leading icon rather than beside the
    // value. One rule for both treatments, which is the trade.
    //
    // `select-none` because dragging across a `$` on the way to the value is never
    // what anyone meant -- and on the nested path it wraps a call site's own markup,
    // which is right for a Copy button too. `shrink-0` for the reason an icon merges
    // its own: the frame hands none out, so a long value would otherwise squeeze the
    // affix instead of scrolling under it. Muted, because an affix is not the value.
    $classes = 'order-first shrink-0 select-none whitespace-nowrap text-ink-muted '.$type[$rung];

    if ($affix === 'segmented') {
        // `flex items-center` is load-bearing rather than tidiness: `self-stretch`
        // blockifies the span, and its text would otherwise sit at the top of a box a
        // whole `py` taller than the line it belongs on.
        //
        // `border-inherit` is what makes this component possible at all. The divider
        // has to be the frame's border colour, which turns `border-danger-border` when
        // the validator objects -- and a nested affix cannot work that out. The input
        // often resolves it from `wire:model`, and `@aware` cannot read a key that is
        // not a legal PHP variable name. Inheriting the computed colour costs nothing
        // and is right in both states.
        //
        // It is also not optional. Preflight sets `border: 0 solid` on everything, and
        // that shorthand resets `border-color` to `currentColor` -- so leaving this
        // off draws the divider in the affix's own muted ink rather than leaving it
        // undrawn, which is the kind of wrong that looks plausible. It earns one more
        // thing on the way past: a call site that puts its own border colour on the
        // field now gets a divider that matches it.
        //
        // `rounded-s-md` rather than `overflow-hidden` on the frame, which would nest
        // the corners exactly and is still the wrong trade: an outline is painted
        // outside the border box, so clipping the frame would eat the focus ring of
        // any control a call site puts in here. What not clipping costs is a 1px
        // crescent of the frame's own surface at the corner, where this 6px radius
        // sits inside the frame's 5px inner one.
        //
        // `transition-colors` so the plate crosses with the frame when the page
        // changes theme rather than snapping while the border around it fades.
        $classes .= ' flex items-center self-stretch rounded-s-md border-e border-inherit bg-surface-muted transition-colors '.$plate[$rung];
    }

    // `except` is mandatory rather than hygiene. Neither key was declared a prop, so
    // nothing has taken it off the bag, and a `size` written on this tag would work
    // *and* render as a stray attribute on the span.
    $attributes = $attributes->except(['size', 'affix'])->merge(['class' => $classes]);
@endphp

{{-- A `<span>`, and the element is part of the contract: the input's own test file
     narrows to `<span>` when it asserts anything about an affix, so a `<div>` here
     would leave a drawer of tests passing against nothing. --}}
<span {{ $attributes }}>{{ $slot }}</span>
