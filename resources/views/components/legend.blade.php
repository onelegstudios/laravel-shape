{{-- No `@blaze`, and here alone in this family that is a choice rather than a
     constraint. The legend has no `@aware` -- a `<legend>` names the fieldset it
     opens by sitting in it, so there is nothing to inherit and nothing to point
     at -- which means none of the trouble field.blade.php describes applies here.

     It stays on Blade's pipeline anyway. The field renders this component, the
     two are read together, and one member of a family compiled differently is a
     safety argument somebody has to make again every time the field moves. --}}

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
