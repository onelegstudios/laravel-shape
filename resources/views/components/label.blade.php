{{-- No `@blaze`: see the note at the top of field.blade.php. Every component in
     this family stays on Blade's own pipeline so that `@aware` resolves the same
     way on both sides of the boundary. --}}

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
    $defaults = ['class' => 'text-sm font-medium text-ink'];

    if ($target !== null) {
        $defaults['for'] = $target;
    }

    $attributes = $attributes->except('for')->merge($defaults);
@endphp

<label {{ $attributes }}>{{ $slot }}</label>
