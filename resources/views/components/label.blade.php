{{-- No `@blaze`: see the note at the top of field.blade.php. Every component in
     this family stays on Blade's own pipeline so that `@aware` resolves the same
     way on both sides of the boundary. --}}

@props(['size' => null])

{{-- After `@props`, for the reason input.blade.php spells out: `@props` ends by
     unsetting every variable whose name matches an attribute it did not claim,
     and neither of these is a prop here. --}}

@aware([
    'name' => null,
    'for' => null,
])

@php
    // The label's own `for` wins, then the field's, then the field's name.
    //
    // Read off the bag rather than trusted to `@aware`, and that is not belt and
    // braces. `@aware` assigns unconditionally while `@props` only fills a null,
    // so a component declaring the same key in both lets an enclosing field
    // override an attribute written right here -- the wrong way round. Taking the
    // caller's value from the bag directly is the only spelling that puts the
    // nearer answer first whichever way the two directives are ordered.
    $inherited = is_string($for) && $for !== ''
        ? $for
        : (is_string($name) && $name !== '' ? \Onelegstudios\Shape\Fields::id($name) : null);

    $own = $attributes->get('for');

    $target = is_string($own) && $own !== '' ? $own : $inherited;

    // A label with nothing to point at is still a label -- it just cannot be one
    // half of a pair. That happens outside a field, or inside one that was never
    // given a name, and it is the call site's own markup either way.
    //
    // Weight carries this, not size: the label is the quiet half of a label/value
    // pair, and `text-sm` beside a `text-sm` control keeps the field one block of
    // type rather than two competing ones.
    //
    // Which is why `size` exists and why it defaults to nothing. In a field the
    // label is a line of its own above the control, and it stays `text-sm` at every
    // rung -- there is no scale to follow, because the thing it is set against is
    // the field's other words rather than the control's box. A checkbox is the
    // exception: its label sits *on the same line* as the box, so the pair is one
    // line of type and the row's line box is whatever the label sets. That is the
    // one caller that names a rung, and it names the input's own so the four
    // controls agree.
    //
    // Unnamed lands back on `text-sm`, so every existing field renders exactly
    // what it rendered before this prop existed.
    // Guarded with `is_string` rather than indexed straight through: PHP 8.5
    // deprecates a null array offset, and unnamed is the ordinary case here rather
    // than the exceptional one. It also lands a rung this scale does not have back
    // on the default instead of rendering unsized type.
    $scale = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    $type = is_string($size) ? ($scale[$size] ?? 'text-sm') : 'text-sm';

    $defaults = ['class' => $type.' font-medium text-ink'];

    if ($target !== null) {
        $defaults['for'] = $target;
    }

    $attributes = $attributes->except('for')->merge($defaults);
@endphp

<label {{ $attributes }}>{{ $slot }}</label>
