@blaze(fold: true)

{{-- An icon is the component a dense page repeats most, so it is the one worth
     making foldable: with `fold: true` a static call site leaves no component
     behind at all, just the SVG inline in the calling template.

     That is only safe because nothing below touches global state. The set, the
     alias table, and the artwork itself were all resolved by `shape:icon:add` when
     the icon was published, so what is left here is arithmetic on the props: a
     size class, a colour class, and an accessibility default. A `config()` call
     anywhere in this file would freeze that config into every compiled view the
     first time it rendered, which is why the size default below is a literal
     rather than a lookup.

     A dynamic `:name` is not an error, it just declines to fold: Blaze falls back
     to the function compiler, which still skips Blade's component pipeline. --}}

@props([
    'name' => '',
    'set' => 'default',
    'size' => 'md',
    'color' => null,
    'label' => null,
])

@php
    // The same four rungs as the button, so `<shape:icon size="sm">` inside a
    // `size="sm"` button is the obvious thing rather than a lookup table. The
    // values are optical, not proportional: 20px is the icon that sits beside
    // `text-sm` without crowding it, and 14px is what survives a table row.
    $sizes = [
        'xs' => 'size-3.5',
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
    ];

    // `none` is not a rung. It stands the scale down so a caller can carry the
    // size itself, on `class`, and the difference that makes is the difference
    // between an icon that folds and one that does not.
    //
    // `size` is declared above, so Blaze treats a bound one as a reason to decline
    // the fold -- correctly, since the value decides which class comes out of the
    // table. `class` is not declared, so a bound one is pass-through: it is
    // swapped for a placeholder, the component is folded, and the placeholder is
    // put back. A component sizing a mark from a variable therefore has a choice
    // about which attribute carries it, and the two are not close. Measured on 200
    // call sites: `:size="$rung"` costs 68.4us each and folds nothing;
    // `:class="$scale"` costs 2.5us and folds. The checkbox's tick and the
    // select's chevron are the marks that take it.
    //
    // Nothing else changes: the class the caller writes lands where this one would
    // have, and an icon that names a rung is untouched.
    $scale = $size === 'none' ? '' : ($sizes[$size] ?? $sizes['md']);

    // No colour means no colour class, which leaves the SVG on `currentColor` and
    // lets it take the colour of whatever it sits inside. `color` is for the
    // standalone icon that carries meaning by itself -- a status tick in a table.
    //
    // A value that is not shaped like a CSS identifier is dropped rather than
    // substituted into a class name, which lands back on inherit -- the default.
    $tint = is_string($color) && preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1
        ? ' text-'.$color.'-on-tint'
        : '';

    // Most icons repeat a label that is already next to them, so hiding them from
    // assistive tech is the default that is right more often. `label` is the
    // escape hatch for the icon that is the only content -- an icon-only button --
    // and it has to announce as an image to be read at all.
    //
    // These are merged as defaults, so an explicit aria-hidden or aria-label at
    // the call site still wins. The published icon deliberately sets no a11y of
    // its own: `merge` can add an attribute but never take one away, so an icon
    // that hid itself would leave `label` unable to unhide it.
    //
    // The label is decoded before it is merged, which looks backwards and is not.
    // `merge` escapes what it is given, so a `&amp;` written at the call site
    // would come out `&amp;amp;` and announce as "amp". Decoding first and
    // letting the merge do the one escape is `e($label, false)` spelled for the
    // path the value takes: a bare `&` and a written-out `&amp;` both come out as
    // one escaped ampersand, which is what the markup around this component would
    // have done with the same text.
    $a11y = $label === null
        ? ['aria-hidden' => 'true']
        : ['role' => 'img', 'aria-label' => html_entity_decode($label, ENT_QUOTES, 'UTF-8')];

    // Escaped here rather than on the way past, which is the half of the dispatch
    // below that is not about speed.
    //
    // `ComponentAttributeBag` does not escape when it renders -- it only backslashes
    // a quote, which is not an escape at all in an attribute -- so a bag reaches the
    // page exactly as safe as it was when it was filled. A component tag fills it
    // safely: Blade sanitises every value on the way in. `@include` shares this
    // scope instead, so there is no way in and nothing sanitises anything, and a
    // caller's `data-x="a & b"` would land raw on the element.
    //
    // So the component does it. Bound values arrive sanitised already -- Blaze does
    // that when it builds the bag -- which is why this cannot double-encode: a
    // second pass over `&amp;` with encoding on gives `&amp;amp;`. Without it, a
    // literal is escaped once and an already-escaped bound value is left alone,
    // which is the same output the component tag used to produce.
    $attributes->setAttributes(array_map(
        fn (mixed $value): mixed => is_string($value) ? e($value, false) : $value,
        $attributes->getAttributes(),
    ));

    // Reassigned rather than kept in a second variable: the published icon reads
    // `$attributes` by that name, and an include shares this scope rather than
    // being handed one.
    // Trimmed because `size="none"` leaves the scale empty and the tint carries a
    // leading space of its own, which would otherwise reach the element.
    $attributes = $attributes->merge(array_merge(['class' => trim($scale.$tint)], $a11y));

    // `shape-icons::` addresses the published icons as views, application copies
    // first, and `art` is the half of a published icon that holds the SVG -- the
    // component beside it carries `@blaze`, and a Blaze component is compiled to a
    // function definition, so including one renders nothing at all.
    //
    // `default` is a real directory rather than a config lookup: `shape:icon:add`
    // writes one alongside the named set, forwarding to it, which is what keeps the
    // configured default set out of this file.
    $icon = 'shape-icons::'.$set.'.art.'.$name;
@endphp

{{-- An include rather than <x-dynamic-component>, which is the whole of what a
     dynamic icon costs. The dispatch is the same either way when this folds -- a
     static name is resolved at compile time and the artwork written inline, and
     neither form leaves anything behind. It is the call site that cannot fold, a
     `:name` bound to a variable, that pays: the component form resolves a class
     or view for the name, builds a component instance and renders it through
     Blade's pipeline on every render, and the published default-set icon forwards
     to a second component that does it again.

     An include skips all of that and renders a view. What it costs is the
     escaping the component boundary used to do, which the block above now does
     for itself. --}}
@include($icon)
