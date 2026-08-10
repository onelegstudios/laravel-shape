@blaze

{{-- `@blaze` with the rest of the family: they move as a unit so no `@aware`
     boundary is ever mixed, and the bag is saved and restored around `@aware` for
     the reason input.blade.php spells out. See the top of field.blade.php.

     No `fold`, and what stands in the way is `@aware` rather than anything in this
     file -- the block at the top of field.blade.php has it. --}}

@props([
    'label' => null,
    'description' => null,
    'size' => null,
    'invalid' => null,
])

{{-- No `descriptionTrailing`, which the other four have. A field stacks its parts
     in a column, so "before the control" and "after the control" are two places a
     sentence can go. A checkbox is a row: the words are all on one side of the
     box, and a second block under the first is just the first block's second
     paragraph. --}}

@php
    $__bag = $attributes->getAttributes();
@endphp

@aware(['name' => null])

@php
    $attributes->setAttributes($__bag);
@endphp

@php
    $defaults = array_filter((array) config('shape.components.checkbox'), 'is_string');

    $size ??= $defaults['size'] ?? 'md';

    $chrome = $label !== null || $description !== null;

    // `option` is what keeps a group of boxes apart. Three checkboxes bound to one
    // `tags` array are one field and three controls, and `Fields::id('tags')` is
    // the same string for all three -- so without a discriminator all three would
    // answer to `id="tags"` and all three labels would click through to the first.
    //
    // Read off the bag and left in it: `value` has to reach the element for the
    // form to submit anything, so this is a read rather than a claim.
    //
    // Passed unconditionally rather than only when the name looks like an array.
    // `<shape:checkbox name="terms" value="1">` therefore gets `id="terms-1"`,
    // which is uglier than `terms` and always correct -- where sniffing for a `[]`
    // suffix would be silently wrong for the ordinary Livewire spelling, which puts
    // three boxes on `wire:model="tags"` with no brackets anywhere.
    $resolved = \Onelegstudios\Shape\Control::resolve(
        attributes: $attributes,
        name: $name,
        invalid: $invalid,
        option: $attributes->get('value'),
        label: $chrome ? $label : null,
        description: $description !== null,
        message: $chrome,
    );

    // Four rungs on both halves of the row, resolved once so the box, the mark and
    // the words cannot get out of step.
    //
    // The boxes are chosen to pair with an icon rung at a constant inset and to sit
    // inside the label's own line box, which is what aligns the row with no
    // `mt-*` to re-derive when the type moves:
    //
    //     rung   words       line box   cell   box    mark   inset
    //     xs     text-xs     16px       h-4    16px   14px   1px
    //     sm     text-sm     20px       h-5    18px   14px   2px
    //     md     text-sm     20px       h-5    20px   16px   2px
    //     lg     text-base   24px       h-6    24px   20px   2px
    //
    // Two of those numbers are worth defending. `md` is 20px rather than the
    // conventional 16px because the mark scale is closed at 14/16/20/24: a 14px
    // mark in a 16px box leaves a 1px inset and reads as a mark that does not fit,
    // where 16-in-20 reads as one that was meant. 20px is also exactly a `text-sm`
    // line box, which is what lets the cell be `h-5` and nothing else.
    //
    // And `xs` does not get a 14px box. The floor is 16px, because 14 with a 12
    // inside it is a target nobody can hit and there is no icon rung below `xs` to
    // put in it anyway -- so `xs` keeps its smallness in the type and the gap while
    // the box stops shrinking. All four are under WCAG 2.5.8's 24px minimum except
    // `lg`; what mitigates that is the `<label for>` beside it, which is part of the
    // same target.
    $boxes = [
        'xs' => 'size-4',
        'sm' => 'size-4.5',
        'md' => 'size-5',
        'lg' => 'size-6',
    ];

    $cells = [
        'xs' => 'h-4',
        'sm' => 'h-5',
        'md' => 'h-5',
        'lg' => 'h-6',
    ];

    // The mark's size as a class rather than as one of the icon's rung names, and
    // the reason is folding rather than taste. A bound `:size` is a declared prop,
    // so it declines the fold and costs a component render per mark on every
    // render of the page; a bound `class` is pass-through, so it folds. This
    // control draws two marks and a table row draws forty of it, which is where
    // that difference stops being academic.
    //
    // These are the icon's own values for the rungs this table used to name -- xs
    // and sm both took the icon's `xs`, md took `sm`, lg took `md`. The pairs are
    // pinned by the size dataset in CheckboxComponentTest, so a drift between this
    // table and the icon's shows up as a failure rather than as a mark that is
    // quietly the wrong size.
    $marks = [
        'xs' => 'size-3.5',
        'sm' => 'size-3.5',
        'md' => 'size-4',
        'lg' => 'size-5',
    ];

    $gaps = [
        'xs' => 'gap-1.5',
        'sm' => 'gap-2',
        'md' => 'gap-2',
        'lg' => 'gap-2.5',
    ];

    $rung = isset($boxes[$size]) ? $size : 'md';
@endphp

@if ($chrome)
    {{-- A row rather than the field's column, which is the shape decision here. A
         checkbox's label names the option *beside* it rather than the field above
         it, and reading them in the other order is reading a different sentence.

         `items-start` so a label that wraps wraps under its own first line instead
         of under the box. --}}
    <div class="flex items-start {{ $gaps[$rung] }}">
        {{-- The control is this same component called again with the chrome props
             left off, which lands in the bare branch below and stops -- one level,
             and no second copy of the markup. `class` reaches that render's cell,
             the way the input's reaches its box. --}}
        <x-shape::checkbox
            {{ $attributes->merge($resolved->forward()) }}
            :size="$size"
            :invalid="$invalid"
        />

        <div class="flex flex-col gap-1">
            {{-- `for` and the description's `id` are passed outright rather than
                 left to an enclosing field, because they come off the *scope*: three
                 boxes in one group must not all claim `tags-description`. Both
                 components read their own attribute off the bag before anything they
                 inherit, so an explicit value wins.

                 Both take the rung, which is why `size` exists on them at all: here
                 the label shares a line with the box, so the row's line box is
                 whatever the words set. --}}
            <x-shape::label :for="$resolved->id" :size="$rung">{{ $label }}</x-shape::label>

            @if ($description !== null)
                <x-shape::description :id="$resolved->scope.'-description'" :size="$rung">{{ $description }}</x-shape::description>
            @endif

            {{-- Only where nothing outside owns the message. A box whose name came
                 from an enclosing `<shape:field>` is one of a group, and a group has
                 one sentence rather than one per box -- the field renders it.
                 Standing on its own, `<shape:checkbox label="I agree" name="terms" />`
                 *is* the whole field, and a consent box that fails validation
                 silently is the bug this covers.

                 `$resolved->inherited` rather than `$name === null`, which is the
                 obvious test and the wrong one: `@aware` hands back a component's
                 own data before its ancestors' on both pipelines -- Blade checks
                 `currentComponentData` first, Blaze finds the call site's own
                 `pushData` at the top of its stack -- so `$name` is `terms` for the
                 standalone box above and a check against null would suppress exactly
                 the message that has nowhere else to go. What tells the two apart is
                 whether the bag still carries the name, which is the whole reason it
                 is restored after `@aware`. See Control::resolve(). --}}
            @if (! $resolved->inherited)
                <x-shape::error :name="$resolved->field" />
            @endif
        </div>
    </div>
@else
    @php
        // One cell, both marks stacked on the control, the same idiom the select
        // uses. `relative`/`absolute` would work too; the grid wins because it needs
        // no positioning context, and because at `sm` -- an 18px box in a 20px cell
        // -- the control is centred by `items-center` and the marks by `self-center`,
        // so the two are concentric by construction rather than by two independent
        // centring mechanisms agreeing.
        //
        // The cell is exactly the label's line box, which is what centres the box on
        // the first line of the words with nothing to tune.
        //
        // `shrink-0` so a long label shrinks the words rather than the box.
        $cell = 'grid grid-cols-1 shrink-0 items-center '.$cells[$rung];

        // `appearance-none` is what makes the box ours to draw. Everything after it
        // is drawn rather than inherited: no OS checkbox is left underneath.
        //
        // The checked fill is written out rather than interpolated through a role,
        // so Tailwind's scanner finds these literally through `@source "../views"`.
        // `border-primary-fill` deliberately points a *fill* token at a border: the
        // checked box is solid, and its border is the fill. `border-transparent`
        // would leave the surface showing through as a hairline.
        //
        // No `color` prop, for the input's reason: a control is not competing for
        // attention the way a button is, so there is no emphasis ladder to put it
        // on. A consumer who wants another colour repoints `--color-primary-fill`.
        //
        // `focus-visible` rather than the box components' `focus-within`, and this
        // is the one place in the family that differs. The control *is* the box here,
        // so `focus-within` would just be `focus` -- and a checkbox is the one
        // control where a pointer click must not leave a ring behind, because a
        // checked box wearing a permanent ring is noise. `focus-visible` is the
        // browser's own answer to "was that a keyboard or a mouse".
        //
        // `disabled:opacity-50` rather than restating the border and fill colours.
        // Those would have to beat `checked:`, which they do today only because
        // `disabled:` sorts later -- and a disabled state resting on variant order is
        // a thing to discover later rather than read now. Opacity touches a property
        // nothing else here touches.
        $control = 'peer col-start-1 row-start-1 appearance-none rounded-sm border bg-surface transition-colors checked:border-primary-fill checked:bg-primary-fill indeterminate:border-primary-fill indeterminate:bg-primary-fill focus-visible:outline-2 focus-visible:outline-offset-2 disabled:cursor-not-allowed disabled:opacity-50';

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
        $ring = 'border-neutral-border outline-neutral-ring invalid:border-danger-border invalid:outline-danger-ring';

        // Both marks sit in the cell whether or not they are showing, and the
        // control is a `peer` so its state decides which one does.
        $mark = 'pointer-events-none col-start-1 row-start-1 hidden self-center justify-self-center text-primary-on-fill';

        $cell = $attributes->only('class')->merge(['class' => $cell]);

        // `aria-describedby` leaves the bag because the island below writes it: the
        // list is settled here, but whether the message joins it is not, and one
        // element cannot carry the attribute twice. `Control::resolve()` has already
        // read the caller's own value, so nothing written at the call site is lost.
        $control = $attributes->except(['class', 'aria-describedby'])->merge(array_merge(
            ['type' => 'checkbox', 'class' => $control.' '.$boxes[$rung].' '.$ring],
            $resolved->attributes(),
        ));
    @endphp

    <span {{ $cell }}>
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

        {{-- Hidden from assistive tech by the icon component's own default, which is
             right: what a reader is told is the control's checked state, and the mark
             is a picture of it.

             `peer-indeterminate:opacity-0` on the tick rather than
             `peer-indeterminate:hidden`, and the reason is worth the class. A box can
             be checked *and* indeterminate at once -- setting `el.indeterminate` does
             not clear `checked` -- so both selectors match and both marks would draw.
             Two `peer-*` variants of `display` are equal weight, so which one won
             would come down to Tailwind's sort order. Opacity touches a different
             property entirely, so there is nothing to resolve: the tick stays in the
             cell, invisible, and the bar draws over it. --}}
        {{-- `size="none"` and the size on `class`, which is what lets these two
             fold. The icon's own note has the mechanism; the short version is that
             a bound prop declines a fold and a bound class does not. --}}
        <x-shape::icon
            name="checkbox-check"
            size="none"
            class="{{ $mark }} {{ $marks[$rung] }} peer-checked:block peer-indeterminate:opacity-0"
        />

        <x-shape::icon
            name="checkbox-indeterminate"
            size="none"
            class="{{ $mark }} {{ $marks[$rung] }} peer-indeterminate:block"
        />
    </span>
@endif
