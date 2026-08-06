{{-- No `@blaze`, for the reason field.blade.php spells out: this component renders
     that one, and a family split across two `@aware` implementations reads two
     different names. It would not have folded regardless -- the `config()` read
     below is the same thing that disqualifies the button. --}}

@props([
    'label' => null,
    'description' => null,
    'descriptionTrailing' => null,
    'size' => null,
    'icon' => null,
    'iconTrailing' => null,
    'iconSet' => 'default',
    'invalid' => null,
])

{{-- After `@props`, and it is the only order that works. `@props` ends by
     unsetting every variable whose name matches an attribute it did not claim as a
     prop -- and `name` is deliberately not a prop here, so an `@aware` above this
     would have `$name` taken away again the moment a call site wrote one.

     The usual objection to this order does not apply. `@aware` assigns
     unconditionally, so putting it last lets an enclosing field beat the tag's own
     attribute; the read below takes the caller's value off the bag directly and
     puts it first, which is what settles the precedence rather than the ordering
     of these two directives. --}}

@aware(['name' => null])

@php
    // Same floor-plus-config idiom as the button, for the same reason: config is
    // merged one level deep, so a consumer who publishes the file and later drops
    // a key gets nothing back from the package, and a prop resolving to nothing
    // renders an unstyled field. Non-strings are filtered so a config typo costs a
    // default rather than a TypeError in a view.
    $defaults = array_filter((array) config('shape.components.input'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    // Shorthand: any chrome prop expands this into a field. The control itself is
    // this same component called again with those props left off, which lands in
    // the bare branch below and stops -- one level, and no second copy of the
    // markup to keep in step with the first.
    $chrome = $label !== null || $description !== null || $descriptionTrailing !== null;

    // Which field this is, whether the validator minds, what id its label points
    // at, and which of the sentences around it describe it -- four questions every
    // control in this family has to answer, resolved in one place so a select and a
    // checkbox do not each answer them again slightly differently. `Control` next
    // to `Fields` in src/ has the rules and the reasons.
    //
    // `name` is deliberately not a prop. It has to reach the rendered element for
    // an ordinary HTML form to work at all, so it stays on the bag -- read out of
    // it below rather than taken from it, unlike every other value this component
    // consumes.
    //
    // `$errors ?? null` rather than an `isset` branch: the bag is shared onto views
    // by ShareErrorsFromSession, and a package cannot assume the middleware ran --
    // a Blade::render() outside the web group has no session, and neither does a
    // mail template. `??` does not warn on a variable that was never set, so the
    // guard is the argument itself.
    $resolved = \Onelegstudios\Shape\Control::resolve(
        attributes: $attributes,
        name: $name,
        invalid: $invalid,
        errors: $errors ?? null,
        chrome: $chrome,
        description: $description !== null,
        descriptionTrailing: $descriptionTrailing !== null,
    );

    // Kept as a local because the recipe below reads it and the recursion passes
    // it down: the shorthand has already settled the question, so the bare render
    // inside it should not go back to the bag and settle it again.
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
        {{-- Long-form rather than `<shape:input>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render. --}}
        <x-shape::input
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :icon="$icon"
            :icon-trailing="$iconTrailing"
            :icon-set="$iconSet"
            :invalid="$bad"
        />
    </x-shape::field>
@else
    @php
        // The box is the wrapper and the control is transparent inside it, which is
        // what lets an icon be an ordinary flex sibling. Positioning one absolutely
        // would have been fewer elements and one bug: a call site narrowing the
        // field with `max-w-*` narrows the control, and an icon anchored to a
        // wrapper that stayed full width detaches from the box it belongs to.
        //
        // It is also the shape a prefix or a suffix will need, so the seam is cut
        // once rather than twice.
        //
        // Padding and type are split across the two elements for the same reason:
        // the wrapper owns the box, the control owns the words. Height still comes
        // out of the pair -- the rung's `py` plus the text's line-height -- and the
        // values are the button's own, so an input and an `outline` button of the
        // same rung stand level in a row: 26, 34, 38 and 46px with their borders.
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

        // A closed set, so an unknown rung falls back rather than rendering an
        // unpadded field. Resolved once and handed to the icons as well, so the
        // mark in a `sm` field is the `sm` mark without the call site saying so.
        $rung = isset($rungs[$size]) ? $size : 'md';

        // Two roles rather than a `color` prop. An input is not competing for
        // attention the way a button is -- there is no emphasis ladder to climb --
        // so the only thing its colour ever says is whether the value is wrong.
        //
        // Written out rather than interpolated through `:role`, which is worth the
        // four extra words: these class names appear literally in this file, so
        // Tailwind's scanner finds them through `@source "../views"` and the
        // safelist in shape.css has nothing to say about them.
        $box = $bad
            ? 'border-danger-border focus-within:outline-danger-ring'
            : 'border-neutral-border focus-within:outline-neutral-ring';

        // `focus-within` rather than `focus-visible`: the ring belongs to the box,
        // and the thing that takes focus is the control inside it. A text field is
        // also the one control where a pointer click should show the ring -- you
        // are about to type into it.
        $frame = 'flex w-full items-center rounded-md border bg-surface transition-colors focus-within:outline-2 focus-within:outline-offset-2 has-disabled:cursor-not-allowed has-disabled:bg-surface-muted';

        // `min-w-0` so a long value shrinks the control rather than the box, and
        // `outline-none` so the wrapper's ring is the only one drawn.
        $control = 'w-full min-w-0 border-0 bg-transparent p-0 text-ink placeholder:text-ink-muted focus:outline-none disabled:cursor-not-allowed disabled:text-ink-muted';

        // The one piece of native chrome left in this component, and the only one
        // Shape does not draw itself: the little calendar button Chromium puts in a
        // date field. `picker` is a variant shape.css declares -- Tailwind names no
        // such pseudo-element -- and what these three classes buy is a pointer
        // cursor on a thing that is a button, and a glyph knocked back to the same
        // visual weight as the trailing `text-ink-muted` mark in the field beside
        // it. `picker:hover:` is the glyph hovered; `hover:picker:` would be the
        // whole field.
        //
        // Nothing inverts it for dark mode. Chromium draws it from the element's
        // `color-scheme`, which shape.css sets, so it already follows the theme.
        //
        // Gated on the type rather than always added, because the classes are inert
        // on a text input and three dead ones on every field in every page is the
        // kind of thing a reader stops on. Read off the bag before the `text`
        // default below is merged, which is the only place the caller's own answer
        // is visible.
        $dated = in_array(
            $attributes->get('type'),
            ['date', 'datetime-local', 'month', 'time', 'week'],
            true,
        );

        if ($dated) {
            $control .= ' picker:cursor-pointer picker:opacity-60 picker:hover:opacity-100';
        }

        // Guarded the way the button guards its own, and for the same reason: a
        // bare `<shape:input icon>` arrives as `true` and would go looking for an
        // icon named "1". A name with no artwork behind it stays the icon
        // component's exception to throw -- it already says what it could not find.
        $lead = is_string($icon) && $icon !== '' ? $icon : null;
        $trail = is_string($iconTrailing) && $iconTrailing !== '' ? $iconTrailing : null;
        $set = is_string($iconSet) && $iconSet !== '' ? $iconSet : 'default';

        // The class goes on the box and everything else on the control, which is
        // the one rule this shape costs. It is the right way round: `max-w-sm`,
        // `rounded-none` and a border colour of your own are all things you are
        // saying about the box you can see, while `wire:model`, `type`, `required`
        // and `placeholder` are things only the control can act on.
        $frame = $attributes->only('class')->merge(['class' => $frame.' '.$rungs[$rung].' '.$box]);

        $control = $attributes->except('class')->merge(array_merge(
            ['type' => 'text', 'class' => $control.' '.$type[$rung]],
            $resolved->attributes(),
        ));
    @endphp

    <div {{ $frame }}>
        {{-- No `label` on either mark, so both stay out of the accessibility tree:
             an icon in a field decorates a control that is already named by its
             label. `text-ink-muted` rather than the control's ink for the same
             reason -- it is an affordance, not the value.

             No `shrink-0` either: a published icon merges its own, so a long value
             cannot squeeze either mark. --}}
        @if ($lead !== null)
            <x-shape::icon :name="$lead" :set="$set" :size="$rung" class="text-ink-muted" />
        @endif

        <input {{ $control }} />

        @if ($trail !== null)
            <x-shape::icon :name="$trail" :set="$set" :size="$rung" class="text-ink-muted" />
        @endif
    </div>
@endif
