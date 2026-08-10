@blaze

{{-- `@blaze`: see the note at the top of field.blade.php. The bag is saved and
     restored around `@aware` because the `name` read below is a read of the
     caller's own value -- input.blade.php has the reason. --}}

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    // A name written on this tag wins over the field's, for the reason spelled out
    // in label.blade.php: the nearer answer is the one the author meant.
    $own = $attributes->get('name');

    $field = is_string($own) && $own !== '' ? $own : (is_string($name) && $name !== '' ? $name : null);

    // `$errors` is shared onto every view by ShareErrorsFromSession, and a package
    // cannot assume the middleware ran: a Blade::render() outside the web group has
    // no session, and neither does a mail template. Guarded rather than defaulted,
    // because a component that quietly invented an empty bag would report every
    // field as valid in the one case where it could not tell.
    $message = $field !== null && isset($errors) ? (string) $errors->first($field) : '';

    // The slot wins over the bag, which is what lets a field say something the
    // validator did not -- and is why the slot is echoed rather than $message: the
    // words a call site wrote may carry markup, and the validator's never do.
    $written = trim((string) $slot) !== '';

    // `font-medium` rather than the description's plain weight -- this is the one
    // line in a field that has to be read.
    //
    // A flex row rather than a plain block, because the mark below is a sibling of
    // the sentence rather than part of it: a message that wraps should wrap under
    // its own first line, not under the icon.
    $defaults = ['class' => 'flex items-start gap-1.5 text-sm font-medium text-danger-on-tint'];

    if ($field !== null) {
        $defaults['id'] = \Onelegstudios\Shape\Fields::id($field).'-error';
    }

    $attributes = $attributes->except('name')->merge($defaults);
@endphp

@if ($written || $message !== '')
    <p {{ $attributes }}>
        {{-- Rendered without a `label`, so it stays out of the accessibility tree.
             The mark is not what says the field is wrong -- the sentence beside it
             is, and it is already the signal that survives a reader who cannot see
             the colour. What the icon adds is at a glance: a form with six fields
             and one message should not need reading to find the one that failed.

             `error` rather than an icon name, because Shape cannot know which
             library an application installed. It resolves through the same alias
             table the button's spinner does -- `circle-alert` in Lucide,
             `exclamation-circle` in Heroicons -- which is also what puts it in
             `Libraries::required()`: `shape:install` publishes it unasked,
             `shape:icon:check` reports it missing, and `shape:icon:remove` will
             not take it away without `--force`.

             Long-form rather than `<shape:icon>`: the short tag is a convenience
             the package compiles for applications, and its own views should not
             need it to render.

             Fixed at `sm` rather than following a rung, because the message is
             fixed at `text-sm` too, and `mt-0.5` is what centres a 16px mark on
             the first line of a 20px line box. Nothing here holds it against a
             long sentence squeezing it -- every published icon merges its own
             `shrink-0`, which is what that class is there for. --}}
        <x-shape::icon name="error" size="sm" class="mt-0.5" />

        <span>{{ $written ? $slot : $message }}</span>
    </p>
@endif
