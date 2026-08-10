@blaze

{{-- `@blaze` with the rest of the family: they move as a unit so no `@aware`
     boundary is ever mixed, and the bag is saved and restored around `@aware` for
     the reason input.blade.php spells out. See the top of field.blade.php. --}}

@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'invalid' => null,
])

{{-- The checkbox's props exactly, and the checkbox's shape below. What differs is
     the promise the control makes: a checkbox is collected and submitted with the
     rest of the form, where a switch is the thing you flip to *apply* a setting.
     `role="switch"` is where that lives, and it is the whole of the difference --
     underneath, this is still `<input type="checkbox">`, because there is no other
     element that carries a boolean into a form. --}}

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    $defaults = array_filter((array) config('shape.components.switch'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $chrome = $label !== null || $description !== null;

    // No `option`, and this is the one place the component departs from the
    // checkbox it is built on. The discriminator exists for controls that share a
    // name -- three boxes on one `tags` field, three radios called `plan` -- and a
    // switch is never one of a set. Nothing groups them, so there is nothing to
    // keep apart, and passing `value` anyway would make
    // `<shape:switch name="notify" value="1">` answer to `notify-1` rather than
    // `notify` in exchange for nothing. This is the input's call, not the box's.
    $resolved = \Onelegstudios\Shape\Control::resolve(
        attributes: $attributes,
        name: $name,
        invalid: $invalid,
        errors: $errors ?? null,
        chrome: $chrome,
        description: $description !== null,
    );

    $bad = $resolved->invalid;

    // The cell and the gap are the checkbox's, unchanged, and the track heights are
    // its box sizes: 16, 18, 20 and 24. A switch, a box and a radio down one column
    // are three selection rules on one control, and a reader comparing them should
    // find them the same height. See checkbox.blade.php for why those four numbers
    // are those four numbers.
    //
    // What is new is the width and the thumb, and they are one decision rather than
    // three. Pick a 2px inset -- the checkbox's, at every rung but `xs` -- and the
    // rest follows:
    //
    //     rung   track     thumb   inset   travel
    //     xs     28 x 16   12      2       12
    //     sm     32 x 18   14      2       14
    //     md     36 x 20   16      2       16
    //     lg     44 x 24   20      2       20
    //
    // Two identities hold across all four, which is what makes the tables checkable
    // rather than chosen: travel is the track's width minus its height, and travel
    // is also the thumb. The second is why `$thumbs` and `$travels` carry the same
    // numbers -- a thumb clears its own width and stops.
    //
    // Written as three tables rather than derived, because Tailwind's scanner reads
    // these files as text: a class assembled from arithmetic is a class that never
    // reaches the stylesheet.
    //
    // As with the checkbox, only `lg` clears WCAG 2.5.8's 24px on the control alone
    // -- on the short axis, at least; every rung clears it across the track. What
    // mitigates the rest is the `<label for>` beside it, which is part of the same
    // target.
    $cells = [
        'xs' => 'h-4',
        'sm' => 'h-5',
        'md' => 'h-5',
        'lg' => 'h-6',
    ];

    $tracks = [
        'xs' => 'h-4 w-7',
        'sm' => 'h-4.5 w-8',
        'md' => 'h-5 w-9',
        'lg' => 'h-6 w-11',
    ];

    $thumbs = [
        'xs' => 'size-3',
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ];

    $travels = [
        'xs' => 'peer-checked:translate-x-3',
        'sm' => 'peer-checked:translate-x-3.5',
        'md' => 'peer-checked:translate-x-4',
        'lg' => 'peer-checked:translate-x-5',
    ];

    $gaps = [
        'xs' => 'gap-1.5',
        'sm' => 'gap-2',
        'md' => 'gap-2',
        'lg' => 'gap-2.5',
    ];

    $rung = isset($tracks[$size]) ? $size : 'md';
@endphp

@if ($chrome)
    {{-- The checkbox's row, for the checkbox's reason: the label names the thing
         beside it rather than the field above it. Control first rather than the
         settings-list order, so a switch, a box and a radio in one form line up
         down a single left edge. --}}
    <div class="flex items-start {{ $gaps[$rung] }}">
        <x-shape::switch
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$bad"
        />

        <div class="flex flex-col gap-1">
            <x-shape::label :for="$resolved->id" :size="$rung">{{ $label }}</x-shape::label>

            @if ($description !== null)
                <x-shape::description :id="$resolved->scope.'-description'" :size="$rung">{{ $description }}</x-shape::description>
            @endif

            {{-- The checkbox's rule rather than the radio's, and for the checkbox's
                 reason. A standalone switch *is* the whole field -- one control, one
                 question, "is two-factor on" -- so it owes an answer when the
                 validator has one. A switch whose name came from an enclosing
                 `<shape:field>` leaves the sentence to it.

                 `$resolved->inherited` rather than `$name === null`, which is the
                 obvious test and the wrong one: `@aware` reads a component's own
                 data before its ancestors' on both pipelines. What tells the two
                 apart is whether the bag still carries the name -- the reason it is
                 restored after `@aware`. See checkbox.blade.php and
                 Control::resolve(). --}}
            @if (! $resolved->inherited)
                <x-shape::error :name="$resolved->field" />
            @endif
        </div>
    </div>
@else
    @php
        // The checkbox's cell, sized to the track. `shrink-0` so a long label
        // shrinks the words rather than the switch.
        $cell = 'grid grid-cols-1 shrink-0 items-center '.$cells[$rung];

        // The checkbox's control stretched into a pill: `rounded-full` for
        // `rounded-sm`, a width alongside the height, and no `indeterminate:` pair.
        // A switch is on or it is not.
        //
        // The colours are the box's exactly, which is the point -- `bg-surface`
        // inside `border-neutral-border` off, solid `primary-fill` on, border and
        // all. A form carrying both should read as one set of controls rather than
        // two, and `border-primary-fill` on a filled track is the same call the
        // checkbox documents: `border-transparent` would leave the surface showing
        // through as a hairline.
        //
        // `focus-visible` rather than the box components' `focus-within`, for the
        // checkbox's reason: the control *is* the track, and a switch left on
        // wearing a permanent ring from a mouse click is noise.
        //
        // `disabled:opacity-50` rather than restated colours, so the disabled state
        // does not rest on `disabled:` sorting after `checked:`.
        $control = 'peer col-start-1 row-start-1 appearance-none rounded-full border bg-surface transition-colors checked:border-primary-fill checked:bg-primary-fill focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

        $ring = $bad
            ? 'border-danger-border focus-visible:outline-danger-ring'
            : 'border-neutral-border focus-visible:outline-neutral-ring';

        // The radio's dot, taught to move. It parks at the left inset --
        // `justify-self-start` plus `ml-0.5` -- rather than centred, and
        // `peer-checked:translate-x-*` walks it to the right one; the track's 1px
        // border is eaten equally at both ends, so the inset stays even.
        //
        // Two colours rather than the radio's one, because this mark is visible in
        // both states and sits on a different surface in each. On, it is the radio's
        // dot exactly: `primary-on-fill`, the token for a mark drawn on a fill. Off,
        // it sits on `surface`, where that token would be invisible -- so it takes
        // the muted ink instead, which is `neutral-500` on white and `neutral-400`
        // on `neutral-900` and legible on both. `neutral-border` would be a hairline
        // in light mode.
        //
        // `transition` rather than `transition-transform`, because the thumb changes
        // colour and position at the same moment and half a transition reads as a
        // bug.
        $thumb = 'pointer-events-none col-start-1 row-start-1 ml-0.5 self-center justify-self-start rounded-full bg-ink-muted transition peer-checked:bg-primary-on-fill';

        $cell = $attributes->only('class')->merge(['class' => $cell]);

        // `role="switch"` in the defaults rather than written on the element, so a
        // call site that means something else by it can say so. It is what turns
        // "checked" into "on" for a screen reader, and it is the only thing telling
        // one apart from a very round checkbox.
        $control = $attributes->except('class')->merge(array_merge(
            ['type' => 'checkbox', 'role' => 'switch', 'class' => $control.' '.$tracks[$rung].' '.$ring],
            $resolved->attributes(),
        ));
    @endphp

    <span {{ $cell }}>
        <input {{ $control }} />

        {{-- CSS rather than an icon, and for the radio's reason turned around: there
             is no glyph to point at here at all. A switch's thumb is a shape, so
             asking an icon set for one would be asking it for a filled circle --
             which is the thing Heroicons does not ship. Two tokens here, and the
             component renders with nothing published.

             A later sibling of the peer, in the same cell, so the control's checked
             state decides where it sits and `pointer-events-none` hands a click on
             it through to the control underneath. --}}
        <span class="{{ $thumb }} {{ $thumbs[$rung] }} {{ $travels[$rung] }}"></span>
    </span>
@endif
