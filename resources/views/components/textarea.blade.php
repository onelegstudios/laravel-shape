{{-- No `@blaze`, for the reason field.blade.php spells out: this component
     renders that one, and a family split across two `@aware` implementations
     reads two different names. It would not have folded regardless -- the
     `config()` read below is the same thing that disqualifies the button. --}}

@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'size' => null,
    'autosize' => false,
    'invalid' => null,
])

{{-- After `@props`, and it is the only order that works: `@props` ends by
     unsetting every variable whose name matches an attribute it did not claim as
     a prop, and `name` is deliberately not a prop here. See input.blade.php for
     why that order settles nothing about precedence on its own. --}}

@aware(['name' => null])

@php
    // Same floor-plus-config idiom as the input, for the same reason: config is
    // merged one level deep, so a consumer who publishes the file and later drops
    // a key gets nothing back from the package.
    $defaults = array_filter((array) config('shape.components.textarea'), 'is_string');

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
        {{-- Long-form rather than `<shape:textarea>`: the short tag is a
             convenience the package compiles for applications, and its own views
             should not need it to render. --}}
        <x-shape::textarea
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :autosize="$autosize"
            :invalid="$bad"
        />
    </x-shape::field>
@else
    @php
        // The input's box around a control that stretches instead of one that sits
        // on a line. Two things change and no more, which is the point: a textarea
        // that borrowed a different frame would be a second box to keep in step
        // with the first, and a form mixing the two would show the seam.
        //
        // `items-center` is the first. It centres a line box, which is exactly
        // right for a control one line tall and exactly wrong for one that is five
        // -- a centred textarea would leave its padding above and below the words
        // rather than around them. Left off, the default `items-stretch` gives the
        // control the height of the box.
        $frame = 'flex w-full rounded-md border bg-surface transition-colors focus-within:outline-2 focus-within:outline-offset-2 has-disabled:cursor-not-allowed has-disabled:bg-surface-muted';

        // `focus-within` for the input's reason: the ring belongs to the box, the
        // thing that takes focus is inside it, and a pointer click on something you
        // are about to type into should show it.
        $box = $bad
            ? 'border-danger-border focus-within:outline-danger-ring'
            : 'border-neutral-border focus-within:outline-neutral-ring';

        // No `gap` in the rung, which is the second change. The input holds a mark
        // off its value with one; there is nothing in this box but the control, so
        // a gap here would be a class that never applies to anything.
        //
        // The padding is the input's own, so a textarea and the input above it line
        // up down their left edge and the `py` still matches the button rung it
        // stands beside.
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

        // Stated rather than left to the type scale, because a paragraph and a
        // single line want different things from the same font size. `text-sm`
        // carries a 20px line box, which is comfortable for one line of a form
        // field and tight for five lines of prose -- so each rung takes the next
        // step up.
        $leading = [
            'xs' => 'leading-5',
            'sm' => 'leading-6',
            'md' => 'leading-6',
            'lg' => 'leading-7',
        ];

        $rung = isset($rungs[$size]) ? $size : 'md';

        // `min-w-0` so a long unbroken word shrinks the control rather than the
        // box, and `outline-none` so the wrapper's ring is the only one drawn.
        $control = 'w-full min-w-0 border-0 bg-transparent p-0 text-ink placeholder:text-ink-muted focus:outline-none disabled:cursor-not-allowed disabled:text-ink-muted';

        // How the control decides its own height, and the one place this component
        // takes a position rather than inheriting one.
        //
        // `field-sizing-content` grows the box with what is typed into it, and it
        // is opt-in rather than the default on purpose: it lands in Chromium and
        // not everywhere else, so a packaged control that reflowed under the cursor
        // in one engine and sat still in another would behave differently per
        // browser for no reason the call site asked for. A form that grows as you
        // write is a design decision; `rows` is the default HTML has.
        //
        // `resize-none` beside it because a manual drag and content sizing fight
        // over the same height, and with the box already following the text there
        // is nothing left to drag for.
        //
        // Vertical-only otherwise: horizontal resize inside a `w-full` box does
        // nothing useful, and Safari's default `both` lets a reader drag a textarea
        // clean out of the box it belongs to. `disabled:resize-none` because a
        // control that cannot be edited has no size worth choosing.
        $control .= filter_var($autosize, FILTER_VALIDATE_BOOLEAN)
            ? ' field-sizing-content resize-none'
            : ' resize-y disabled:resize-none';

        $frame = $attributes->only('class')->merge(['class' => $frame.' '.$rungs[$rung].' '.$box]);

        // `rows` merged as a default rather than declared as a prop. It is a plain
        // HTML attribute that already reaches the control through the pass-through,
        // so a prop would exist only to set a default -- and setting defaults is
        // what `merge` is for. Three rather than the browser's two, which is a
        // box so short it reads as broken.
        $control = $attributes->except('class')->merge(array_merge(
            ['rows' => '3', 'class' => $control.' '.$type[$rung].' '.$leading[$rung]],
            $resolved->attributes(),
        ));
    @endphp

    <div {{ $frame }}>
        <textarea {{ $control }}>{{ $slot }}</textarea>
    </div>
@endif
