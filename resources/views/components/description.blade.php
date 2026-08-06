{{-- No `@blaze`: see the note at the top of field.blade.php. --}}

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
    $defaults = ['class' => 'text-sm text-ink-muted'];

    if (is_string($name) && $name !== '') {
        $defaults['id'] = \Onelegstudios\Shape\Fields::id($name).'-description';
    }
@endphp

<p {{ $attributes->merge($defaults) }}>{{ $slot }}</p>
