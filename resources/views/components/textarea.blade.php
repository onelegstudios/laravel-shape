@blaze

{{-- `@blaze` with the rest of the family: they move as a unit so no `@aware`
     boundary is ever mixed, and the bag is saved and restored around `@aware` for
     the reason input.blade.php spells out. See the top of field.blade.php.

     No `fold`, and what stands in the way is `@aware` rather than anything in this
     file -- the block at the top of field.blade.php has it. --}}

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

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

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
        label: $chrome ? $label : null,
        description: $description !== null,
        descriptionTrailing: $descriptionTrailing !== null,
        message: $chrome,
    );

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
            :invalid="$invalid"
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
        // Both states in one string, chosen by CSS rather than by a branch here.
        // This component folds -- it is evaluated once, when the view is compiled --
        // so a branch on the error bag would freeze whatever the validator happened
        // to be saying then. `has-invalid:` reads it off the control on render instead;
        // see the variant block in shape.css.
        //
        // The ring colour is unvariant where it used to sit behind the focus state.
        // It costs nothing, since `outline-width` is zero until the focus rule above
        // sets it, and it keeps the pair one variant apart so which of them wins is
        // Tailwind's plainest ordering rule rather than a question about how deeply
        // stacked variants sort.
        $box = 'border-neutral-border outline-neutral-ring has-invalid:border-danger-border has-invalid:outline-danger-ring';

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
        // `aria-describedby` leaves the bag because the island below writes it: the
        // list is settled here, but whether the message joins it is not, and one
        // element cannot carry the attribute twice. `Control::resolve()` has already
        // read the caller's own value, so nothing written at the call site is lost.
        $control = $attributes->except(['class', 'aria-describedby'])->merge(array_merge(
            ['rows' => '3', 'class' => $control.' '.$type[$rung].' '.$leading[$rung]],
            $resolved->attributes(),
        ));
    @endphp

    <div {{ $frame }}>
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
        <textarea {{ $control }}@unblaze($resolved->live()){!! \Onelegstudios\Shape\Control::state($scope, $errors ?? null) !!}@endunblaze>{{ $slot }}</textarea>
    </div>
@endif
