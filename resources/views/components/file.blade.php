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
    'icon' => null,
    'iconSet' => 'default',
    'invalid' => null,
])

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    $defaults = array_filter((array) config('shape.components.file'), 'is_string');

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
        {{-- Long-form rather than `<shape:file>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::file
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :icon="$icon"
            :icon-set="$iconSet"
            :invalid="$invalid"
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

        // `aria-describedby` leaves the bag because the island below writes it: the
        // list is settled here, but whether the message joins it is not, and one
        // element cannot carry the attribute twice. `Control::resolve()` has already
        // read the caller's own value, so nothing written at the call site is lost.
        $control = $attributes->except(['class', 'aria-describedby'])->merge(array_merge(
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
    </div>
@endif
