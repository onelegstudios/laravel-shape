{{-- No `@blaze`, for the reason field.blade.php spells out: this component
     renders that one, and a family split across two `@aware` implementations
     reads two different names. It would not have folded regardless -- the
     `config()` read below is the same thing that disqualifies the button. --}}

@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'invalid' => null,
])

{{-- The checkbox's props exactly, and the checkbox's shape below, minus two
     things: there is no indeterminate state -- a radio is on or it is not -- and
     there is no message. A radio is always one option of one field, so the
     sentence belongs to the group, and three copies of it is not a message.
     `<shape:field>` is what prints it. --}}

@aware(['name' => null])

@php
    $defaults = array_filter((array) config('shape.components.radio'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $chrome = $label !== null || $description !== null;

    // A radio group is the case the discriminator was built for: three controls
    // called `plan` are three ids -- `plan-free`, `plan-pro`, `plan-team` -- and
    // three labels that each click through to their own rather than all to the
    // first. Read off the bag and left in it, because `value` is the whole point of
    // a radio: it is what the field submits.
    $resolved = \Onelegstudios\Shape\Control::resolve(
        attributes: $attributes,
        name: $name,
        invalid: $invalid,
        errors: $errors ?? null,
        option: $attributes->get('value'),
        chrome: $chrome,
        description: $description !== null,
    );

    $bad = $resolved->invalid;

    // The checkbox's tables, unchanged, for the reason they should be unchanged: a
    // radio and a checkbox in one form are the same control with two selection
    // rules, and a reader comparing them down a column should find them the same
    // size. See checkbox.blade.php for why each number is what it is.
    $boxes = [
        'xs' => 'size-4',
        'sm' => 'size-4.5',
        'md' => 'size-5',
        'lg' => 'size-6',
    ];

    $cells = [
        'xs' => 'h-4',
        'sm' => 'h-5',
        'md' => 'h-5',
        'lg' => 'h-6',
    ];

    // The dot, which is where this component stops following the checkbox. Sized
    // against the box rather than against the icon scale, because it is not an icon:
    // 6-in-16, 6-in-18, 8-in-20 and 10-in-24 leave 5, 6, 6 and 7 pixels of ring on
    // each side, which is the range where a dot reads as filled rather than as a
    // small square with rounded corners.
    //
    // Even numbers on purpose. An odd dot centred in an even box lands on a half
    // pixel and renders soft on a 1x display, so `size-1.75` -- which would smooth
    // the ramp between `xs` and `md` -- is deliberately not here.
    $dots = [
        'xs' => 'size-1.5',
        'sm' => 'size-1.5',
        'md' => 'size-2',
        'lg' => 'size-2.5',
    ];

    $gaps = [
        'xs' => 'gap-1.5',
        'sm' => 'gap-2',
        'md' => 'gap-2',
        'lg' => 'gap-2.5',
    ];

    $rung = isset($boxes[$size]) ? $size : 'md';
@endphp

@if ($chrome)
    {{-- The checkbox's row, for the checkbox's reason: the label names the option
         beside it rather than the field above it. --}}
    <div class="flex items-start {{ $gaps[$rung] }}">
        <x-shape::radio
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$bad"
        />

        <div class="flex flex-col gap-1">
            {{-- `for` and the description's `id` come off the scope, so three
                 options in one group do not all claim `plan-description`. --}}
            <x-shape::label :for="$resolved->id" :size="$rung">{{ $label }}</x-shape::label>

            @if ($description !== null)
                <x-shape::description :id="$resolved->scope.'-description'" :size="$rung">{{ $description }}</x-shape::description>
            @endif

            {{-- No message here, and this is the one place a radio differs from a
                 checkbox in the chrome branch. A standalone checkbox is a whole field
                 -- one box, one question, "do you agree" -- so it owes an answer when
                 the validator has one. A standalone radio is not a field: one option
                 of a set the user cannot choose from is a bug in the markup rather
                 than a state to style. So the sentence always belongs to the group,
                 and `<shape:field>` prints it once. --}}
        </div>
    </div>
@else
    @php
        // The checkbox's cell, unchanged.
        $cell = 'grid grid-cols-1 shrink-0 items-center '.$cells[$rung];

        // The checkbox's control with `rounded-full` for `rounded-sm` and no
        // `indeterminate:` pair. Round rather than square is the only thing telling
        // a reader that this set is one-of-many rather than any-of-many, which is
        // why the shape is not configurable.
        $control = 'peer col-start-1 row-start-1 appearance-none rounded-full border bg-surface transition-colors checked:border-primary-fill checked:bg-primary-fill focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

        $ring = $bad
            ? 'border-danger-border focus-visible:outline-danger-ring'
            : 'border-neutral-border focus-visible:outline-neutral-ring';

        $cell = $attributes->only('class')->merge(['class' => $cell]);

        $control = $attributes->except('class')->merge(array_merge(
            ['type' => 'radio', 'class' => $control.' '.$boxes[$rung].' '.$ring],
            $resolved->attributes(),
        ));
    @endphp

    <span {{ $cell }}>
        <input {{ $control }} />

        {{-- CSS rather than an icon, and it is not a shortcut: Heroicons ships no
             `circle` and no `dot`, so an alias would point at a glyph one of the two
             libraries Shape can install does not have. A filled circle is two tokens
             here, where it would be a broken promise there.

             `bg-primary-on-fill` rather than `bg-current` plus a text colour: the
             token exists and says exactly what this is -- the mark drawn on top of a
             filled surface -- in one class instead of two.

             A later sibling of the peer, in the same cell, so the control's checked
             state decides whether it draws and `pointer-events-none` hands a click on
             it through to the control underneath. --}}
        <span class="pointer-events-none col-start-1 row-start-1 hidden self-center justify-self-center rounded-full bg-primary-on-fill peer-checked:block {{ $dots[$rung] }}"></span>
    </span>
@endif
