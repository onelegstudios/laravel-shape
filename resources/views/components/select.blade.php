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
    'multiple' => false,
    'invalid' => null,
])

{{-- There is no `iconTrailing`, and leaving it undeclared is the decision rather
     than an omission. The chevron owns that side of the box: a select has one
     trailing mark and it is the one that says the control is a select. Declared
     and then ignored, an `icon-trailing="x"` would vanish without a word;
     undeclared, it lands on the `<select>` as an attribute HTML does not have,
     where a reader or a validator will point at it. A loud no beats a silent one.

     A consumer who wants a different glyph there repoints `select-chevron` in
     `icons.aliases`, which changes it everywhere at once -- which is the right
     grain for a mark that means "this is a select". --}}

@aware(['name' => null])

@php
    $defaults = array_filter((array) config('shape.components.select'), 'is_string');

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

    // A `multiple` select is a list box rather than a dropdown, and almost every
    // decision below forks on it: there is no chevron because nothing opens, no
    // room to leave for one, and its height comes from its options rather than
    // from one line box.
    //
    // Declared as a prop so it can be read, and merged back onto the element below
    // so it still reaches the browser -- the one attribute in this file that has to
    // travel in both directions.
    //
    // `filter_var` alone would not do. A bare `<shape:select multiple>` arrives as
    // `true` and reads as true, but a written-out `multiple="multiple"` is not a
    // boolean string and would read as *false* -- the attribute would be present
    // and the component would think it absent. So anything filter_var cannot parse
    // counts as present, and only a value it parses as false counts as absent.
    $list = $multiple !== false && $multiple !== null
        && filter_var($multiple, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) !== false;
@endphp

@if ($chrome)
    <x-shape::field
        :name="$resolved->field"
        :for="$resolved->id"
        :label="$label"
        :description="$description"
        :description-trailing="$descriptionTrailing"
    >
        {{-- Long-form rather than `<shape:select>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::select
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :icon="$icon"
            :icon-set="$iconSet"
            :multiple="$multiple"
            :invalid="$bad"
        >{{ $slot }}</x-shape::select>
    </x-shape::field>
@else
    @php
        // A one-cell grid rather than the input's flex row, and this is the one
        // place in the family where those two shapes are not interchangeable.
        //
        // A mark that is a flex *sibling* occupies a column of its own. Give it
        // `pointer-events-none` and the click does not reach the mark -- it reaches
        // the box behind it, which is a `<div>` and does nothing. So the last 20
        // pixels of a select would be dead, which is exactly where everybody clicks
        // to open one.
        //
        // In a single cell with both children at `col-start-1 row-start-1`, the
        // control fills the box and the marks float over it. `pointer-events-none`
        // then hands every click in the cell straight through to the `<select>`
        // underneath, chevron included. That is the whole reason for the fork.
        //
        // It is also why there is no `gap` in the rung below: one cell has nothing
        // to hold apart. The room a mark needs is padding on the control instead.
        $frame = 'grid w-full grid-cols-1 rounded-md border bg-surface transition-colors focus-within:outline-2 focus-within:outline-offset-2 has-disabled:cursor-not-allowed has-disabled:bg-surface-muted';

        // `focus-within` for the input's reason: the ring belongs to the box, the
        // thing that takes focus is the control inside it, and a pointer click on a
        // control you are operating should show it.
        $box = $bad
            ? 'border-danger-border focus-within:outline-danger-ring'
            : 'border-neutral-border focus-within:outline-neutral-ring';

        // The input's padding, so a select and the field above it line up down both
        // edges and the `py` still matches the button rung it stands beside.
        $rungs = [
            'xs' => 'px-2 py-1',
            'sm' => 'px-3 py-1.5',
            'md' => 'px-4 py-2',
            'lg' => 'px-5 py-2.5',
        ];

        $type = [
            'xs' => 'text-xs',
            'sm' => 'text-sm',
            'md' => 'text-sm',
            'lg' => 'text-base',
        ];

        $rung = isset($rungs[$size]) ? $size : 'md';

        // Where the marks go, and the numbers are not arbitrary: each is the mark's
        // own width plus the gap the input's flex rung would have held it off by --
        // 14+4, 16+6, 20+8, 24+10. So a long option stops exactly where a long value
        // in an input stops, and the two controls read as one family.
        //
        // Written out as tokens rather than derived, because Tailwind's scanner has
        // to find them literally in this file. Logical (`pe`/`ps`) rather than
        // physical, so an RTL page moves the chevron to the other side and the room
        // left for it follows without a second table.
        $clear = [
            'xs' => 'pe-4.5',
            'sm' => 'pe-5.5',
            'md' => 'pe-7',
            'lg' => 'pe-8.5',
        ];

        $lead = [
            'xs' => 'ps-4.5',
            'sm' => 'ps-5.5',
            'md' => 'ps-7',
            'lg' => 'ps-8.5',
        ];

        // Guarded the way the input guards its own: a bare `<shape:select icon>`
        // arrives as `true` and would go looking for an icon named "1". A name with
        // no artwork behind it stays the icon component's exception to throw.
        $mark = is_string($icon) && $icon !== '' ? $icon : null;
        $set = is_string($iconSet) && $iconSet !== '' ? $iconSet : 'default';

        $control = 'col-start-1 row-start-1 w-full min-w-0 border-0 bg-transparent text-ink focus:outline-none disabled:cursor-not-allowed disabled:text-ink-muted';

        if ($list) {
            // Nothing opens, so nothing needs room left for it, and the OS arrow it
            // does not draw does not need removing.
            $control .= ' p-0';
        } else {
            // `appearance-none` takes the OS arrow away so ours is the only one.
            //
            // `bg-none` is the defensive half, and it is aimed at one thing:
            // @tailwindcss/forms in its `base` mode paints its own chevron onto
            // every `select` as a `background-image` data URI, with a hardcoded grey
            // that follows no theme and no colour scheme. A consumer who has that
            // plugin installed for the rest of their application would otherwise get
            // two chevrons here, one of them the wrong colour. `bg-none` removes the
            // image, `bg-transparent` above removes the white it paints behind it,
            // `border-0` removes its border, and the `pe-*` below beats its
            // `padding-right` because a utility outranks a base-layer rule.
            //
            // Four explicit padding sides rather than `p-0` plus `pe-*`: both would
            // apply and Tailwind's own sort would resolve it correctly, but a
            // padding calculation resting on utility sort order is a thing to
            // discover later rather than read now.
            $control .= ' appearance-none bg-none py-0 '.$clear[$rung];

            $control .= $mark !== null ? ' '.$lead[$rung] : ' ps-0';
        }

        $frame = $attributes->only('class')->merge(['class' => $frame.' '.$rungs[$rung].' '.$box]);

        $control = $attributes->except('class')->merge(array_merge(
            ['class' => $control.' '.$type[$rung]],
            $resolved->attributes(),
            $list ? ['multiple' => 'multiple'] : [],
        ));
    @endphp

    <div {{ $frame }}>
        {{-- Both marks are hidden from assistive tech by the icon component's own
             default, which is right twice over: a leading mark decorates a control
             its label already named, and the chevron is a picture of what the
             `<select>` element already announces itself to be.

             `self-center` rather than the box centring them, because the box cannot:
             a list box is taller than one line, and `items-center` on the frame
             would drag the control's own text to the middle of it. --}}
        @if ($mark !== null)
            <x-shape::icon
                :name="$mark"
                :set="$set"
                :size="$rung"
                class="pointer-events-none col-start-1 row-start-1 self-center justify-self-start text-ink-muted"
            />
        @endif

        <select {{ $control }}>{{ $slot }}</select>

        {{-- `select-chevron` rather than a glyph name, because the two libraries
             Shape can install do not agree on it: `chevrons-up-down` in Lucide,
             `chevron-up-down` in Heroicons. Which is what the alias table is for.

             Two arrows rather than one pointing down: a pair says the value moves
             through a list, where a single chevron says a panel opens beneath --
             which is what a disclosure does, not what a select does.

             **No `set`**, and deliberately not `$set`. This is Shape's own mark
             rather than the call site's, and it resolves the way the button's
             spinner and the message's mark resolve: through `default`, which is the
             only set `shape:install` publishes the required names into. Handing it
             `icon-set` would send it looking in a directory the installer never put
             it in -- so `<shape:select icon="globe" icon-set="solid">` would draw
             the consumer's globe from Heroicons and then throw on the chevron.

             `icon-set` therefore names the set for the leading mark alone, which is
             the only mark on this control the call site chose.

             Long-form rather than `<shape:icon>`: the short tag is a convenience
             the package compiles for applications. --}}
        @if (! $list)
            <x-shape::icon
                name="select-chevron"
                :size="$rung"
                class="pointer-events-none col-start-1 row-start-1 self-center justify-self-end text-ink-muted"
            />
        @endif
    </div>
@endif
