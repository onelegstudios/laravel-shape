@blaze(fold: true)

{{-- `@blaze`, and here alone in this family it never needed the save-and-restore.
     The legend has no `@aware` -- a `<legend>` names the fieldset it opens by
     sitting in it, so there is nothing to inherit and nothing to point at -- and so
     no bag to put back either. It moves with the family because the field renders
     it and the two are read together. See the top of field.blade.php.

     Having no `@aware` is also what lets it take `fold: true` on its own terms
     rather than only through a folded field. Everything it decides comes from one
     prop and a closed table, so a call site that names its rung as a literal is
     answerable at compile time wherever it stands -- which is not true of the label
     or the description beside it, and is the whole of the difference. --}}

@props(['size' => null])

@php
    // The label's type, without the half of the label that makes it one. There is
    // no `for` to resolve, no `name` to inherit and no pair that can disagree,
    // which is why this is its own file rather than a `tag` prop on the label: the
    // part the two share is the table below, and the part they do not is
    // everything the label writes above it.
    //
    // A third copy of the scale rather than a shared one, which is what
    // description.blade.php already decided for the same reason. Guarded the same
    // way too: PHP 8.5 deprecates a null array offset, and a rung this scale does
    // not have should cost a default rather than unsized type.
    $scale = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    $type = is_string($size) ? ($scale[$size] ?? 'text-sm') : 'text-sm';

    // `mb-1.5` on the part rather than a gap on the parent, which is the one place
    // this family breaks its own rule -- and the element is the reason. A rendered
    // `<legend>` is taken out of its fieldset's formatting context: it is painted
    // into the border box rather than laid out as a child, so no `gap` on any
    // ancestor can reach it. The margin has to be the legend's own.
    //
    // It is the same 6px the field puts between its other parts, so a group and a
    // plain field read the same down a column.
    $defaults = ['class' => 'mb-1.5 '.$type.' font-medium text-ink'];
@endphp

<legend {{ $attributes->merge($defaults) }}>{{ $slot }}</legend>
