{{-- No `@blaze`, and it is the one component here that could not have it anyway.

     Blaze compiles `@aware` against its own data stack rather than Blade's, and the
     two do not agree: `Illuminate\View\Concerns\ManagesComponents` checks the
     component's own data before walking up to its ancestors, while
     `Livewire\Blaze\Runtime\BlazeRuntime::getConsumableData` only walks the
     ancestors -- and Blaze's `AwareCompiler` also unsets the key from
     `$attributes` on the way past. A field compiled by one and a label by the
     other therefore read a different name, which is the one thing this component
     exists to make reliable.

     So the whole family stays on Blade's pipeline: the field pushes its data
     through `$__env`, and its children read it back the same way. `fold` was never
     available to any of them regardless -- Blaze throws
     InvalidBlazeFoldUsageException for a component using `@aware` at all. --}}

@props([
    'name' => null,
    'for' => null,
    'label' => null,
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
    $chrome = $label !== null || $description !== null || $descriptionTrailing !== null;

    $field = is_string($name) && $name !== '' ? $name : null;

    // The trailing description needs an id of its own, because the leading one has
    // already claimed the name-derived default and two elements cannot answer to
    // the same `aria-describedby` token.
    $trailingId = $field !== null
        ? \Onelegstudios\Shape\Fields::id($field).'-description-trailing'
        : null;

    // `gap-1.5` rather than margins on the parts: the field owns the rhythm, so a
    // label used outside one does not carry spacing it did not ask for.
    $attributes = $attributes->merge(['class' => 'flex flex-col gap-1.5']);
@endphp

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
