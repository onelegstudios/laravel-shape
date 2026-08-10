@blaze

{{-- The prefix's shape, mirrored, and every reason for it is written down next door
     rather than repeated here -- what `@blaze` costs this pair, why neither value is a prop,
     why the bag is read after `@aware`, what each number in the plate table cancels,
     and why `border-inherit` is what makes a nested affix possible at all.

     Four things differ, and that is the whole of it: the plate table mirrors, the
     order class is `order-last`, the divider is `border-s`, and the corner it rounds
     is `rounded-e-md`. --}}

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['size' => null, 'affix' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    $own = $attributes->get('size');
    $size = is_string($own) && $own !== '' ? $own : $size;

    $own = $attributes->get('affix');
    $affix = is_string($own) && $own !== '' ? $own : $affix;

    $defaults = array_filter((array) config('shape.components.input'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $affix ??= $defaults['affix'] ?? 'inline';

    $affix = $affix === 'segmented' ? 'segmented' : 'inline';

    $type = [
        'xs' => 'text-xs',
        'sm' => 'text-sm',
        'md' => 'text-sm',
        'lg' => 'text-base',
    ];

    // The prefix's table with the inline axis reversed: `-me-*` cancels the frame's
    // trailing padding, and `ms-*` tops the gap up to a full padding on the side the
    // value is coming from.
    $plate = [
        'xs' => '-my-1 -me-2 px-2 ms-1',
        'sm' => '-my-1.5 -me-3 px-3 ms-1.5',
        'md' => '-my-2 -me-4 px-4 ms-2',
        'lg' => '-my-2.5 -me-5 px-5 ms-2.5',
    ];

    $rung = isset($type[$size]) ? $size : 'md';

    $classes = 'order-last shrink-0 select-none whitespace-nowrap text-ink-muted '.$type[$rung];

    if ($affix === 'segmented') {
        $classes .= ' flex items-center self-stretch rounded-e-md border-s border-inherit bg-surface-muted transition-colors '.$plate[$rung];
    }

    $attributes = $attributes->except(['size', 'affix'])->merge(['class' => $classes]);
@endphp

<span {{ $attributes }}>{{ $slot }}</span>
