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
    'invalid' => null,
])

{{-- No `icon` or `icon-set`, and here the reason is not room but redundancy: this
     control is already a mark. A swatch with a glyph beside it inside the same box
     would be two things claiming to say what the field holds. --}}

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    $defaults = array_filter((array) config('shape.components.color'), 'is_string');

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
        {{-- Long-form rather than `<shape:color>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::color
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$invalid"
        />
    </x-shape::field>
@else
    @php
        // Square, at the rung's height, rather than the `w-full` every other control
        // in this family takes. A field stretches because what it holds has no
        // length you can predict; this one holds a colour, which has no length at
        // all. Stretched, it is a flat band of saturated colour carrying more weight
        // on the page than the question it answers -- and the widest thing in a form
        // should not be the one field with the least in it.
        //
        // The four sizes are the input's own outer heights: 26, 34, 38 and 46px. So
        // a swatch and a text field of the same rung stand level in a row, the same
        // bargain the slider takes.
        $sizes = [
            'xs' => 'size-6.5',
            'sm' => 'size-8.5',
            'md' => 'size-9.5',
            'lg' => 'size-11.5',
        ];

        // A closed set, so an unknown rung falls back rather than rendering a swatch
        // at whatever size the browser picks.
        $rung = isset($sizes[$size]) ? $size : 'md';

        // No wrapper, for the slider's reason: the control is the box. What differs
        // is that this one keeps the input's frame -- `rounded-md border bg-surface`
        // -- because a swatch needs an edge. A pale colour on a pale page has no
        // boundary of its own, and the border is what stops the value from bleeding
        // into the surface behind it. `bg-surface` is what shows through before a
        // value is picked.
        //
        // `appearance-none` first, or the browser draws its own well and none of the
        // rest applies. `p-0` because Chromium pads the input as well as the wrapper
        // inside it, and both have to go for the colour to meet the border.
        //
        // `swatch-wrapper:p-0` is the second half of that: Chromium's
        // `::-webkit-color-swatch-wrapper` carries its own 4px inset, which `p-0` on
        // the input does not reach. Left in, the value floats in a frame of
        // `bg-surface` rather than filling it.
        //
        // `swatch:border-0` removes the hairline both engines draw around the colour
        // itself -- a second edge inside the one this component already gives it.
        //
        // `swatch:rounded` one step tighter than the box's `rounded-md`, so the
        // border stays visible in the corners instead of being swallowed by a colour
        // rounded to the same radius.
        //
        // `focus-visible` rather than the input's `focus-within`: the control is the
        // box, and there is nothing here to type into. Same call the switch makes.
        $control = 'block cursor-pointer appearance-none rounded-md border bg-surface p-0 transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50 swatch-wrapper:p-0 swatch:rounded swatch:border-0';

        // The input's pair, unchanged. This control has a border, so it says it
        // there -- and written out rather than interpolated so Tailwind's scanner
        // meets both class names literally.
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
        $role = 'border-neutral-border outline-neutral-ring invalid:border-danger-border invalid:outline-danger-ring';

        // No hex beside it. Reading the value back into text takes JavaScript, and
        // this library ships none -- a `<shape:input>` bound to the same model is
        // the way to show it, which is a call site's decision rather than this
        // component's.
        // `aria-describedby` leaves the bag because the island below writes it: the
        // list is settled here, but whether the message joins it is not, and one
        // element cannot carry the attribute twice. `Control::resolve()` has already
        // read the caller's own value, so nothing written at the call site is lost.
        $control = $attributes->except('aria-describedby')->merge(array_merge(
            ['type' => 'color', 'class' => $control.' '.$sizes[$rung].' '.$role],
            $resolved->attributes(),
        ));
    @endphp

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
@endif
