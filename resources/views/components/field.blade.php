@blaze

{{-- `@blaze` on every member of this family, and the whole family together, which
     is the part that matters: `@aware` reads the render stack, so a field compiled
     by one pipeline and a label by the other would read two different names. Moving
     them as a unit is what keeps that boundary from ever existing.

     The disagreement between the two `@aware` implementations is real but narrower
     than it looks. `ManagesComponents::getConsumableComponentData()` checks the
     component's own data before walking its ancestors;
     `BlazeRuntime::getConsumableData()` only walks ancestors -- but Blaze's compiler
     emits `$__blaze->pushData($attrs)` at the *call site*, before the child runs, so
     the component's own attributes are sitting at the top of that stack and both
     implementations hand back the same value. The `name` and `the aware boundary`
     blocks in tests/Feature/FieldComponentTest pin that rather than trusting it.

     Where the two genuinely part company is that Blaze's `AwareCompiler` unsets each
     key from `$attributes` and Blade's `@aware` leaves it there. Every file in this
     family that reads the caller's own value back off the bag has to put it back
     first; input.blade.php carries that explanation, being the file with the
     sharpest version of it.

     No `fold` here, and not because it is unavailable -- `InvalidBlazeFoldUsageException::forAware()`
     is defined in Blaze and never called. It is off because folding bakes
     `Control::$sequence` into the compiled view, so unnamed fields in a loop would
     all answer to one id. See docs/performance.md. --}}

@props([
    'name' => null,
    'for' => null,
    'label' => null,
    'legend' => null,
    'description' => null,
    'descriptionTrailing' => null,
])

@php
    // Declaring `name` and `for` as props is what puts them on the component data
    // stack, which is the whole mechanism: the label, description and error below
    // are separate components with no way to see this one except through `@aware`.
    //
    // Two modes, told apart by whether any chrome prop was named. Given one, the
    // field writes the label, the help text and the message itself -- that is the
    // shorthand `<shape:input label="...">` expands into. Given none, it is a bare
    // column and the slot says what goes in it.
    //
    // The distinction matters for the message specifically. A composed field
    // already carries its own `<shape:error>`, and a field that also added one
    // would print the same sentence twice.
    $chrome = $label !== null
        || $legend !== null
        || $description !== null
        || $descriptionTrailing !== null;

    // The third mode, named the way the first two are: the prop that is set is what
    // decides the markup, with no boolean flag alongside it to get out of step.
    // `label` still means a `<div>` and a `<label>`; `legend` means a `<fieldset>`
    // and a `<legend>`, which is the only spelling that makes a set of radios or
    // boxes announce as a group -- and the only one where the group's name is not a
    // `for` pointing at an id nothing on the page renders.
    $group = $legend !== null;

    $field = is_string($name) && $name !== '' ? $name : null;

    // One derivation for both ids this component can hand out. `?: null` for the
    // reason Control::resolve() has it: a name that slugs away to nothing -- `---`
    // -- should name nothing rather than name `-description-trailing`.
    $slug = $field !== null ? (\Onelegstudios\Shape\Fields::id($field) ?: null) : null;

    // The trailing description needs an id of its own, because the leading one has
    // already claimed the name-derived default and two elements cannot answer to
    // the same `aria-describedby` token.
    $trailingId = $slug !== null ? $slug.'-description-trailing' : null;

    // What the group is described by, and only the sentences this component drew
    // itself -- the same rule Control::resolve() follows, for the same reason: a
    // reference to an element that was never rendered is a finding rather than a
    // courtesy. A composed group writes its own `aria-describedby`, exactly as a
    // composed field does.
    //
    // The message id is deliberately not in this list. Every radio and every box in
    // the group already carries `plan-error` in its own `aria-describedby`, so a
    // fieldset naming it too would have the sentence read out on entering the group
    // and again on the first option.
    //
    // Leaving it off also keeps the error bag out of this file. Naming it safely
    // would mean repeating Control's `$reported`-rather-than-`$bad` rule here,
    // because `:invalid="true"` styles a control the validator has not seen and
    // there is no sentence for it to point at -- which is the subtlest rule in that
    // class and not one to keep in two places.
    $described = [];

    if ($group && $slug !== null && $description !== null) {
        $described[] = $slug.'-description';
    }

    if ($group && $trailingId !== null && $descriptionTrailing !== null) {
        $described[] = $trailingId;
    }

    // `gap-1.5` rather than margins on the parts: the field owns the rhythm, so a
    // label used outside one does not carry spacing it did not ask for. The legend
    // is the one exception, and it owns its own -- see legend.blade.php.
    //
    // Where the caller's bag lands differs by mode, on purpose. On a `<div>` it
    // merges with the column, as it always has. On a `<fieldset>` it merges with
    // the box, because `max-w-sm` and a border of your own are things said about
    // the element you can see, while the column inside is an implementation detail.
    // The cost is worth stating plainly: `class="gap-4"` retunes a plain field and
    // does not retune a group.
    //
    // `merge` rather than a set for `aria-describedby`, so a call site naming
    // something else keeps the last word.
    $attributes = $group
        ? $attributes->merge(array_merge(
            ['class' => 'min-w-0'],
            $described !== [] ? ['aria-describedby' => implode(' ', $described)] : [],
        ))
        : $attributes->merge(['class' => 'flex flex-col gap-1.5']);
@endphp

@if ($group)
    {{-- `min-w-0` and nothing else on the box, which is two decisions.

         The column is not here because a rendered `<legend>` is not laid out by the
         fieldset it opens -- it is painted into the border box rather than placed
         as a flex or grid child -- so `flex flex-col gap-1.5` out here would space
         every part except the one that needs it. The wrapper inside carries the
         column and the legend carries its own margin.

         `min-w-0` because `fieldset` ships a UA `min-inline-size: min-content` that
         Tailwind's Preflight does not reset. Without it a group inside a flex row
         refuses to shrink below its widest option and pushes the row wide, which is
         the one way a fieldset behaves unlike the `<div>` it replaces.

         No `<shape:label>` here even when one was named. A `<label for="plan">`
         inside a fieldset points at an id no element renders -- the options are
         `plan-free` and `plan-pro` -- which is the finding this mode exists to
         remove, so the legend wins outright rather than the two being drawn
         together. Precedence rather than an exception: nothing in this directory
         throws, and every other bad input here lands on a default. --}}
    <fieldset {{ $attributes }}>
        <x-shape::legend>{{ $legend }}</x-shape::legend>

        <div class="flex flex-col gap-1.5">
            @if ($description !== null)
                <x-shape::description>{{ $description }}</x-shape::description>
            @endif

            {{ $slot }}

            @if ($descriptionTrailing !== null)
                <x-shape::description :id="$trailingId">{{ $descriptionTrailing }}</x-shape::description>
            @endif

            {{-- Unguarded, because a named legend is already one of the chrome
                 props: `$group` cannot be true where `$chrome` is false. --}}
            <x-shape::error />
        </div>
    </fieldset>
@else
    <div {{ $attributes }}>
        @if ($label !== null)
            <x-shape::label>{{ $label }}</x-shape::label>
        @endif

        @if ($description !== null)
            <x-shape::description>{{ $description }}</x-shape::description>
        @endif

        {{ $slot }}

        @if ($descriptionTrailing !== null)
            <x-shape::description :id="$trailingId">{{ $descriptionTrailing }}</x-shape::description>
        @endif

        @if ($chrome)
            <x-shape::error />
        @endif
    </div>
@endif
