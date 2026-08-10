@blaze(fold: true, unsafe: ['*'])

{{-- `@blaze`: see the note at the top of field.blade.php for why this family is on
     it and why it moved as a unit. `fold: true` is this file's own, and the island
     at the foot is the whole of what makes it possible.

     What the validator said is the most per-request thing in the package, and so is
     whether it said anything at all. Folding evaluates a template once, at compile
     time, when no bag exists -- so a folded copy of this component would report
     every field clean for ever, silently, with well-formed markup. The element is
     held back to render time rather than the sentence inside it, because the
     element's existence is the part that depends on the answer.

     Blaze enforces that rather than trusting it. `Folder::checkProblematicPatterns()`
     greps a foldable component's raw source for the bag's variable and refuses the
     fold if it finds one -- comments included, since it looks before Blade strips
     them -- and it takes island blocks out before looking. That is why the prose in
     this file names the bag in words rather than spelling it.

     `unsafe: ['*']` is the gate, and it is the widest one Blaze offers: a call site
     with any bound attribute, or any content whatsoever, declines the fold. Both
     halves are load-bearing.

     Content, because of the slot. A call site with words of its own never reads the
     bag, so that path would fold correctly -- but the test telling the two apart is
     `trim((string) $slot)`, and at compile time a slot is a placeholder standing in
     for content that has not been restored yet. An indented

         <shape:error>
         </shape:error>

     is no slot at all when rendered and a non-empty placeholder when folded, which
     is the divergence the button's `square` prop exists for. A narrower
     `unsafe: ['slot']` does not close it -- it trims loose content before deciding,
     so whitespace still folds -- and `'*'` does, because it counts children rather
     than weighing them.

     Bound attributes, because of the name. It is read off the bag rather than
     declared as a prop, since a name written here has to beat the field's; to the
     compiler that looks like pass-through, so a bound one would be allowed to fold.
     It would not survive the trip: `Fields::id()` rewrites the placeholder's
     underscores on the way to the id below, and what reaches the browser is a
     BLAZE-PLACEHOLDER-0 sitting in an `id` attribute.

     The bag is saved and restored around `@aware` because the `name` read below is a
     read of the caller's own value -- input.blade.php has the reason. --}}

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

    // The slot wins over the bag, which is what lets a field say something the
    // validator did not -- and is why the slot is echoed rather than the message:
    // the words a call site wrote may carry markup, and the validator's never do.
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

{{-- The mark both branches below draw, explained once.

     Rendered without a `label`, so it stays out of the accessibility tree. The mark
     is not what says the field is wrong -- the sentence beside it is, and it is
     already the signal that survives a reader who cannot see the colour. What the
     icon adds is at a glance: a form with six fields and one message should not need
     reading to find the one that failed.

     `error` rather than an icon name, because Shape cannot know which library an
     application installed. It resolves through the same alias table the button's
     spinner does -- `circle-alert` in Lucide, `exclamation-circle` in Heroicons --
     which is also what puts it in `Libraries::required()`: `shape:install` publishes
     it unasked, `shape:icon:check` reports it missing, and `shape:icon:remove` will
     not take it away without `--force`.

     Long-form rather than `<shape:icon>`: the short tag is a convenience the package
     compiles for applications, and its own views should not need it to render.

     Fixed at `sm` rather than following a rung, because the message is fixed at
     `text-sm` too, and `mt-0.5` is what centres a 16px mark on the first line of a
     20px line box. Nothing here holds it against a long sentence squeezing it --
     every published icon merges its own `shrink-0`, which is what that class is
     there for. --}}

@if ($written)
    <p {{ $attributes }}>
        <x-shape::icon name="error" size="sm" class="mt-0.5" />

        <span>{{ $slot }}</span>
    </p>
@elseif ($field !== null)
    {{-- The island, and it holds the element rather than the words in it, for the
         reason at the top of this file.

         It cannot see this component's scope -- the block is no longer inside the
         function Blaze wrote -- so `$scope` carries what it needs. Both values are
         settled by the time a fold runs: the name came off the bag or down from the
         field, and the attributes have already been merged. They are handed over as
         a rendered string rather than as the bag itself because the scope is written
         into the compiled view with `var_export`, which takes arrays and scalars and
         not an object with no `__set_state`.

         The four lines inside repeat the branch above, and that is the price of the
         split rather than an oversight: `$slot` is a variable of this component's,
         and an island is compiled against the calling view's scope, where there is
         no such thing. The pairing that keeps them honest is a test rendering both.

         One thing is given up here. Island content skips the folding pass, so the
         mark inside a *folded* message is a component call rather than inline
         artwork -- the trade the button's spinner makes, for the same reason. It
         costs nothing on the ordinary path, where this file is compiled rather than
         folded and the icon folds into it as it always has, and on the folded path
         it only ever runs for a field that actually failed. --}}
    @unblaze(['field' => $field, 'attributes' => (string) $attributes])
        {{-- The bag is shared onto every view by ShareErrorsFromSession, and a
             package cannot assume the middleware ran: a Blade::render() outside the
             web group has no session, and neither does a mail template. Guarded
             rather than defaulted, because a component that quietly invented an
             empty bag would report every field as valid in the one case where it
             could not tell.

             `has()` rather than asking whether the first message is empty, so this
             file and `Control::resolve()` decide on the same question. The two owe
             each other that: a control names this element in its `aria-describedby`
             exactly when the bag has something to say, so a message that answered a
             different question would leave that reference pointing at an element
             nobody rendered. --}}
        @if (isset($errors) && $errors->has($scope['field']))
            <p {!! $scope['attributes'] !!}>
                <x-shape::icon name="error" size="sm" class="mt-0.5" />

                <span>{{ $errors->first($scope['field']) }}</span>
            </p>
        @endif
    @endunblaze
@endif
