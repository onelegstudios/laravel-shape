{{-- No `@blaze`, for the reason field.blade.php spells out: this component
     renders that one, and a family split across two `@aware` implementations
     reads two different names. It would not have folded regardless -- the
     `config()` read below is the same thing that disqualifies the button. --}}

@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'size' => null,
    'icon' => null,
    'iconSet' => 'default',
    'invalid' => null,
])

@aware(['name' => null])

@php
    $defaults = array_filter((array) config('shape.components.file'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $chrome = $label !== null || $description !== null || $descriptionTrailing !== null;

    $resolved = \Onelegstudios\Shape\Control::resolve(
        attributes: $attributes,
        name: $name,
        invalid: $invalid,
        errors: $errors ?? null,
        chrome: $chrome,
        description: $description !== null,
        descriptionTrailing: $descriptionTrailing !== null,
    );

    $bad = $resolved->invalid;
@endphp

@if ($chrome)
    <x-shape::field
        :name="$resolved->field"
        :for="$resolved->id"
        :label="$label"
        :description="$description"
        :description-trailing="$descriptionTrailing"
    >
        {{-- Long-form rather than `<shape:file>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::file
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :icon="$icon"
            :icon-set="$iconSet"
            :invalid="$bad"
        />
    </x-shape::field>
@else
    @php
        // The input's box, unchanged, around the one control in the family that
        // arrives with a button already inside it.
        //
        // What that button should look like is the only real decision here, and
        // there are two answers. Left with its own chrome it is a second frame
        // inside the first, which is two borders and two radii for one field. So it
        // gives its chrome up instead -- `file:border-0 file:bg-transparent
        // file:p-0` -- and reads as the *action inside* the field rather than a
        // control sitting in a box.
        //
        // That also settles the height, which is the part worth writing down. A
        // button with padding of its own would make this field taller than an input
        // of the same rung, and the rung's `py` would need a second table to undo
        // it. With the button's height reduced to its own line box, the field comes
        // out at exactly the input's 26, 34, 38 and 46px.
        //
        // The visible-button spelling is a one-line swap if two frames read better
        // to you: `file:me-2 file:rounded-md file:bg-neutral-tint file:px-2
        // file:py-1 file:text-neutral-on-tint hover:file:bg-neutral-tint-hover`.
        // Expect to reduce the rung's `py` to keep the row level.
        $frame = 'flex w-full items-center rounded-md border bg-surface transition-colors focus-within:outline-2 focus-within:outline-offset-2 has-disabled:cursor-not-allowed has-disabled:bg-surface-muted';

        // `focus-within` for the input's reason, and here it earns it twice: the
        // thing that takes focus is inside the box either way, and clicking the
        // button focuses the input, so the field rings as a whole rather than the
        // button ringing on its own.
        $box = $bad
            ? 'border-danger-border focus-within:outline-danger-ring'
            : 'border-neutral-border focus-within:outline-neutral-ring';

        // The input's own, gap included: this control does hold something off
        // something else, and `file:me-*` below takes the same number.
        $rungs = [
            'xs' => 'gap-1 px-2 py-1',
            'sm' => 'gap-1.5 px-3 py-1.5',
            'md' => 'gap-2 px-4 py-2',
            'lg' => 'gap-2.5 px-5 py-2.5',
        ];

        $type = [
            'xs' => 'text-xs',
            'sm' => 'text-sm',
            'md' => 'text-sm',
            'lg' => 'text-base',
        ];

        // The gap the rung above holds an icon off the value by, spelled as a margin
        // because the button is the control's own pseudo-element rather than a flex
        // sibling -- so the box's `gap` has nothing to apply to. Taking the number
        // from the same place is what keeps the two spacings identical.
        //
        // Logical (`me`) rather than `mr`, so an RTL page moves the button to the
        // other end and the space follows it.
        $offset = [
            'xs' => 'file:me-1',
            'sm' => 'file:me-1.5',
            'md' => 'file:me-2',
            'lg' => 'file:me-2.5',
        ];

        $rung = isset($rungs[$size]) ? $size : 'md';

        // `text-ink-muted` rather than the input's `text-ink`, which is not a
        // shortcut: the filename beside the button is not a value anybody typed, it
        // is a report of what they picked. That is exactly what shape.css's page
        // surfaces describe `ink-muted` as covering -- the placeholder, the help
        // text, and the value you cannot edit.
        $control = 'w-full min-w-0 border-0 bg-transparent p-0 text-ink-muted focus:outline-none disabled:cursor-not-allowed disabled:text-ink-muted';

        // `file:` is the variant for `::file-selector-button`. Weight and colour
        // are what make it read as the action -- `primary-on-tint` is the same token
        // a `ghost` button takes, which is the right relative: a link inside a
        // field rather than a filled control.
        //
        // `disabled:file:` and not `file:disabled:`, which is the one spelling here
        // that is easy to get backwards. Variants apply left to right, so
        // `disabled:file:` compiles to `&:disabled::file-selector-button` -- the
        // button of a disabled input, which is what is wanted. `file:disabled:`
        // would be `::file-selector-button:disabled`, and a pseudo-element never
        // matches `:disabled`.
        //
        // `file:cursor-pointer` because nothing here touches `appearance`, so the
        // button otherwise inherits the field's own cursor.
        $control .= ' file:cursor-pointer file:border-0 file:bg-transparent file:p-0 file:font-medium file:text-primary-on-tint disabled:file:cursor-not-allowed disabled:file:text-ink-muted';

        // Guarded the way the input guards its own: a bare `<shape:file icon>`
        // arrives as `true` and would go looking for an icon named "1".
        $lead = is_string($icon) && $icon !== '' ? $icon : null;
        $set = is_string($iconSet) && $iconSet !== '' ? $iconSet : 'default';

        $frame = $attributes->only('class')->merge(['class' => $frame.' '.$rungs[$rung].' '.$box]);

        $control = $attributes->except('class')->merge(array_merge(
            ['type' => 'file', 'class' => $control.' '.$offset[$rung].' '.$type[$rung]],
            $resolved->attributes(),
        ));
    @endphp

    <div {{ $frame }}>
        {{-- Leading only, and no trailing mark: the far end of this box belongs to
             the filename, which has no fixed length and every reason to be the thing
             that wraps or truncates. A mark pinned after it would be a mark that
             moves.

             Hidden from assistive tech by the icon component's own default: it
             decorates a control its label already named. --}}
        @if ($lead !== null)
            <x-shape::icon :name="$lead" :set="$set" :size="$rung" class="text-ink-muted" />
        @endif

        <input {{ $control }} />
    </div>
@endif
