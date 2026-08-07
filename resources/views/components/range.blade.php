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

{{-- No `icon` or `icon-set`, and the omission is the file component's: there is
     nowhere in this control for a mark to go. An input holds one off the value in
     the space its padding leaves, and a slider has no such space -- the track runs
     the full width, because a slider that stopped short of its own box would be a
     slider whose maximum is unreachable. A mark belongs beside this control rather
     than inside it, which makes it the call site's to place. --}}

@aware(['name' => null])

@php
    $defaults = array_filter((array) config('shape.components.range'), 'is_string');

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
        {{-- Long-form rather than `<shape:range>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::range
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$bad"
        />
    </x-shape::field>
@else
    @php
        // No wrapper. Every other control in this family is an element inside a box
        // Shape draws, and the class-on-the-box rule exists to say which of the two
        // a call site is talking about -- but a slider *is* its box. There is no
        // frame to give it, because the track is the only thing there is to see, and
        // a border around it would be a second horizontal line saying nothing. So
        // the attribute bag merges in one piece, and `class` lands where the caller
        // expects it.
        //
        // The heights are the input's, which is the whole reason to state them at
        // all: 26, 34, 38 and 46px. The track sits centred in that box, so a slider
        // and a text field of the same rung stand level in a row without either one
        // being told about the other -- and the pair below is why an `xs` slider is
        // not simply a shorter one.
        $rungs = [
            'xs' => 'h-6.5',
            'sm' => 'h-8.5',
            'md' => 'h-9.5',
            'lg' => 'h-11.5',
        ];

        $tracks = [
            'xs' => 'track:h-1',
            'sm' => 'track:h-1.5',
            'md' => 'track:h-2',
            'lg' => 'track:h-2',
        ];

        // The switch's thumbs exactly -- 12, 14, 16 and 20 -- and for the switch's
        // reason: a slider and a toggle down one column are two controls you drag or
        // flip, and a reader comparing them should find the same mark on both.
        $thumbs = [
            'xs' => 'thumb:size-3',
            'sm' => 'thumb:size-3.5',
            'md' => 'thumb:size-4',
            'lg' => 'thumb:size-5',
        ];

        // Chromium only, and checkable rather than chosen: the offset is
        // (track - thumb) / 2, which comes out -4, -4, -4 and -6px. Chromium seats
        // the thumb on the top edge of the track's box instead of centring it on the
        // track, so this margin is what puts it back.
        //
        // `webkit-thumb` rather than `thumb`, which is the one thing here that is
        // easy to get wrong: Firefox centres `::-moz-range-thumb` itself and reads
        // the same margin as a shove upwards. shape.css declares the two separately
        // for exactly this class.
        //
        // The track table above is picked so all four land on the spacing scale.
        // Nothing else in this library uses an arbitrary value, and a slider is a
        // poor place to start.
        $offsets = [
            'xs' => 'webkit-thumb:-mt-1',
            'sm' => 'webkit-thumb:-mt-1',
            'md' => 'webkit-thumb:-mt-1',
            'lg' => 'webkit-thumb:-mt-1.5',
        ];

        // A closed set, so an unknown rung falls back rather than rendering a slider
        // with an uncentred thumb.
        $rung = isset($rungs[$size]) ? $size : 'md';

        // `appearance-none` is what makes any of the rest apply: left native, the
        // browser draws its own track and thumb and ignores everything below.
        //
        // The ring goes on the thumb rather than on the control. An outline around
        // the whole 38px box of something whose visible part is a 2px bar reads as a
        // stray rectangle, so `focus:outline-none` takes the browser's away and
        // `focus-visible:thumb:` puts one where the eye already is.
        //
        // `focus-visible:thumb:` and not `thumb:focus-visible:`, which is the same
        // trap file.blade.php documents for `disabled:file:`. Variants apply left to
        // right: this compiles to `&:focus-visible::-webkit-slider-thumb`, the thumb
        // of a focused slider. The other order asks a pseudo-element to match
        // `:focus-visible`, which never happens.
        //
        // `thumb:border-0` for Firefox, which gives `::-moz-range-thumb` a border of
        // its own that no amount of background will hide.
        //
        // `disabled:opacity-50` rather than a second set of colours, the switch's
        // trick: opacity on the element carries its pseudo-elements, so one class
        // dims the track and the thumb together and neither state has to sort after
        // the other.
        $control = 'w-full cursor-pointer appearance-none bg-transparent focus:outline-none disabled:cursor-not-allowed disabled:opacity-50 track:rounded-full thumb:appearance-none thumb:rounded-full thumb:border-0 thumb:bg-primary-fill thumb:transition-colors focus-visible:thumb:outline-2 focus-visible:thumb:outline-offset-2';

        // Two roles, the input's pair. The only thing a slider's colour ever says is
        // whether the value is wrong -- and it has to say it on the track, since
        // there is no border here to say it on.
        //
        // Written out rather than interpolated through `:role`, so these class names
        // appear literally in this file and Tailwind's scanner finds them through
        // `@source "../views"`.
        $role = $bad
            ? 'track:bg-danger-tint focus-visible:thumb:outline-danger-ring'
            : 'track:bg-neutral-tint focus-visible:thumb:outline-neutral-ring';

        // No `track:h-full` and no filled portion. CSS cannot read a slider's value,
        // so the part left of the thumb can only be painted where the browser offers
        // a pseudo-element for it -- `::-moz-range-progress`, which is Firefox's
        // alone. Taking it would make this control look deliberately different in
        // one browser, which is the thing the date field's documentation says the
        // library declines to do. A filled track is what JavaScript is for.
        $control = $attributes->merge(array_merge(
            [
                'type' => 'range',
                'class' => $control.' '.$rungs[$rung].' '.$tracks[$rung].' '.$thumbs[$rung].' '.$offsets[$rung].' '.$role,
            ],
            $resolved->attributes(),
        ));
    @endphp

    <input {{ $control }} />
@endif
