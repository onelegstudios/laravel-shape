@blaze

{{-- `@blaze` with the rest of the family: they move as a unit so no `@aware`
     boundary is ever mixed, and the bag is saved and restored around `@aware` for
     the reason input.blade.php spells out. See the top of field.blade.php.

     No `fold`, and what stands in the way is `@aware` rather than anything in this
     file -- the block at the top of field.blade.php has it. --}}

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

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

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
        option: $attributes->get('value'),
        label: $chrome ? $label : null,
        description: $description !== null,
        message: $chrome,
    );

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
            :invalid="$invalid"
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

        // Both states in one string, chosen by CSS rather than by a branch here.
        // This component folds -- it is evaluated once, when the view is compiled --
        // so a branch on the error bag would freeze whatever the validator happened
        // to be saying then. `invalid:` reads it off the control on render instead;
        // see the variant block in shape.css.
        //
        // The ring colour is unvariant where it used to sit behind the focus state.
        // It costs nothing, since `outline-width` is zero until the focus rule above
        // sets it, and it keeps the pair one variant apart so which of them wins is
        // Tailwind's plainest ordering rule rather than a question about how deeply
        // stacked variants sort.
        $ring = 'border-neutral-border outline-neutral-ring invalid:border-danger-border invalid:outline-danger-ring';

        $cell = $attributes->only('class')->merge(['class' => $cell]);

        // `aria-describedby` leaves the bag because the island below writes it: the
        // list is settled here, but whether the message joins it is not, and one
        // element cannot carry the attribute twice. `Control::resolve()` has already
        // read the caller's own value, so nothing written at the call site is lost.
        $control = $attributes->except(['class', 'aria-describedby'])->merge(array_merge(
            ['type' => 'radio', 'class' => $control.' '.$boxes[$rung].' '.$ring],
            $resolved->attributes(),
        ));
    @endphp

    <span {{ $cell }}>
        {{-- The island, and the only thing in this component that is not settled by
             the time the view is compiled. `aria-invalid` is what says the value is
             wrong -- to a screen reader, and to the variant that colours the control
             -- and whether the message joins `aria-describedby` depends on the same
             read. Folding evaluates this file once, so both are held back rather
             than baked; `Control::state()` weighs them per render against the array
             `live()` wrote into the compiled view beside it.

             The bag reaches it as an argument rather than being read inside, so the
             guard is the argument: it is shared onto views by ShareErrorsFromSession
             and a package cannot assume the middleware ran -- a Blade::render()
             outside the web group has no session, and neither does a mail template.
             `??` does not warn on a variable that was never set. --}}
        <input {{ $control }}@unblaze($resolved->live()){!! \Onelegstudios\Shape\Control::state($scope, $errors ?? null) !!}@endunblaze />

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
