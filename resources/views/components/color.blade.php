{{-- No `@blaze`, for the reason field.blade.php spells out: this component renders
     that one, and a family split across two `@aware` implementations reads two
     different names. It would not have folded regardless -- the `config()` read
     below is the same thing that disqualifies the button. --}}

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

@aware(['name' => null])

@php
    $defaults = array_filter((array) config('shape.components.color'), 'is_string');

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
        {{-- Long-form rather than `<shape:color>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::color
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$bad"
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
        $role = $bad
            ? 'border-danger-border focus-visible:outline-danger-ring'
            : 'border-neutral-border focus-visible:outline-neutral-ring';

        // No hex beside it. Reading the value back into text takes JavaScript, and
        // this library ships none -- a `<shape:input>` bound to the same model is
        // the way to show it, which is a call site's decision rather than this
        // component's.
        $control = $attributes->merge(array_merge(
            ['type' => 'color', 'class' => $control.' '.$sizes[$rung].' '.$role],
            $resolved->attributes(),
        ));
    @endphp

    <input {{ $control }} />
@endif
