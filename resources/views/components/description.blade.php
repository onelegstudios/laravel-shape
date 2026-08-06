{{-- No `@blaze`: see the note at the top of field.blade.php. --}}

@props(['size' => null])

{{-- After `@props`, for the reason input.blade.php spells out: `@props` ends by
     unsetting every variable whose name matches an attribute it did not claim,
     and `name` is not a prop here. --}}

@aware(['name' => null])

@php
    // An id, so the control can name this element in its `aria-describedby`. It is
    // derived from the field's name rather than generated, because the input
    // deriving the same string in its own file is what lets the two agree without
    // a component class between them to introduce them.
    //
    // Merged as a default, so a call site with two descriptions in one field can
    // give the second one an id of its own -- which is exactly what the field does
    // for `description-trailing`.
    //
    // `size` follows the label's, for the label's reason: help text under a
    // checkbox belongs to the words beside the box rather than to a field's own
    // column, so it scales with them. Unnamed lands back on `text-sm`, which is
    // what every field renders.
    // Guarded like the label's, and for the same two reasons: PHP 8.5 deprecates a
    // null array offset, and a rung this scale does not have should cost a default
    // rather than unsized type.
    $scale = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    $type = is_string($size) ? ($scale[$size] ?? 'text-sm') : 'text-sm';

    $defaults = ['class' => $type.' text-ink-muted'];

    if (is_string($name) && $name !== '') {
        $defaults['id'] = \Onelegstudios\Shape\Fields::id($name).'-description';
    }
@endphp

<p {{ $attributes->merge($defaults) }}>{{ $slot }}</p>
