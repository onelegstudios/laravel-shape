@blaze

{{-- Blaze compiles this template into a plain PHP function and calls it directly,
     skipping Blade's component pipeline. That is the whole optimisation here, and
     it has to be: the two stronger strategies both disqualify themselves. `memo`
     caches rendered output per call-site signature but only applies to components
     without slots, and a button is mostly slot. `fold` bakes the result into the
     calling template at compile time, which cannot survive the `config()` reads
     below -- an application's published defaults would stop being read the moment
     a view was compiled, which is the one promise this component makes.

     The directive has to be the first thing in the file: Blaze looks for it with
     an anchored match, so a comment above it reads as no directive at all. --}}

@props([
    'variant' => null,
    'color' => null,
    'size' => null,
    'loading' => false,
    'icon' => null,
    'iconTrailing' => null,
    'iconSet' => 'default',
])

@php
    // A prop left unnamed at the call site takes its value from config, so an
    // application can move the starting point for every button at once. The
    // packaged defaults are repeated here as a floor rather than left to config
    // alone: `mergeConfigFrom` merges one level deep, so a consumer who publishes
    // the config file and later drops a key gets nothing back from the package,
    // and a prop resolving to nothing renders a button with no styling at all.
    // Non-strings are filtered for the same reason -- a config typo should cost a
    // default, not a TypeError in a view.
    $defaults = array_filter((array) config('shape.components.button'), 'is_string');

    $variant ??= $defaults['variant'] ?? 'outline';
    $color ??= $defaults['color'] ?? 'neutral';
    $size ??= $defaults['size'] ?? 'md';

    // Emphasis ladder, not a colour list: `solid` carries the most weight, `soft`
    // and `outline` the middle, `ghost` the least. A screen usually spends `solid`
    // once, on its single primary action -- but that is a habit, not a rule, and
    // any variant composes with any colour role.
    //
    // The default is deliberately the quiet one, so the loud button is opt-in
    // (`variant="solid"`) rather than what you get for free. `outline` wins the
    // default over `soft` because a packaged component cannot know what surface it
    // lands on: a tinted fill disappears against a similarly tinted page, a border
    // never does.
    //
    // Recipes name surface tokens (`bg-primary-fill`) rather than ramp steps
    // (`bg-primary-700`), so a consumer can restyle one surface from the theme
    // without forking this file or changing what the ramp means elsewhere. Dark
    // mode lives in those tokens too, which is why no `dark:` classes appear here.
    //
    // `:role` stands in for the colour role. Substituting it keeps this file free
    // of a per-role matrix and, more usefully, means a role the package has never
    // heard of works the moment a consumer defines its tokens. Tailwind never sees
    // these class names literally, so shape.css declares the built-in set through
    // `@source inline()` -- that block is the other half of this, and the one line
    // a consumer adds to claim a role of their own.
    //
    // Radius lives here rather than in a size recipe: it is the component's shape
    // rather than its size, and keeping it in one place leaves a consumer a single
    // `--radius-md` to override instead of one per rung.
    // A bare `<shape:button loading>` arrives as a boolean, but the interesting
    // call site passes a variable -- and a `loading="false"` from a template that
    // stringified one should not read as busy.
    $busy = filter_var($loading, FILTER_VALIDATE_BOOLEAN);

    $base = 'inline-flex items-center justify-center rounded-md transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none';

    // A loading button is disabled, so it would take the fade every other disabled
    // button takes -- and a half-transparent spinner reads as a component that has
    // given up rather than one that is working. The spinner is the signal here, so
    // the fade steps aside and the button stays at full contrast.
    //
    // `relative` is the positioning context for the overlay below, and is only
    // worth what it costs in the one state that has something to position.
    $base .= $busy ? ' relative' : ' disabled:opacity-50';

    $recipes = [
        'solid' => 'font-semibold shadow-sm bg-:role-fill text-:role-on-fill hover:bg-:role-fill-hover hover:shadow-md active:shadow-sm focus-visible:outline-:role-ring',
        'soft' => 'font-medium bg-:role-tint text-:role-on-tint hover:bg-:role-tint-hover focus-visible:outline-:role-ring',
        'ghost' => 'font-medium text-:role-on-tint hover:bg-:role-tint focus-visible:outline-:role-ring',
        'outline' => 'font-medium border border-:role-border text-:role-on-tint hover:bg-:role-tint focus-visible:outline-:role-ring',
    ];

    // Size is density, not emphasis: `md` is the button a form gets, `sm` and `xs`
    // are what a toolbar or a table row can afford, `lg` is for a screen whose one
    // action is the point. It composes with the other two props like they compose
    // with each other -- a small solid danger button is an ordinary thing to want.
    //
    // Three things change, and they are written out per rung rather than derived
    // from a ratio, because padding, text size, and the gap holding an icon off a
    // label each have their own comfortable range and none of them scales with the
    // others. Weight belongs to the variant above, so it does not appear here.
    //
    // Both ends are deliberate. `xs` stands 24px tall, the smallest target WCAG
    // 2.5.8 allows; `lg` stands 44px, the size a thumb actually wants. Anything
    // bigger than `lg` is a landing page rather than an interface, and merged
    // classes cover it without the library having an opinion.
    $sizes = [
        'xs' => 'gap-1 px-2 py-1 text-xs',
        'sm' => 'gap-1.5 px-3 py-1.5 text-sm',
        'md' => 'gap-2 px-4 py-2 text-sm',
        'lg' => 'gap-2.5 px-5 py-2.5 text-base',
    ];

    // The same rungs with nothing to hold apart: a button whose only content is an
    // icon. The scale above does not fit it -- `px` measured for a label leaves a
    // wide pill around a 20px mark -- so the padding is equalised, and these are the
    // values that land such a button on exactly the height the labelled rung beside
    // it already stands at: 24, 32, 36 and 44px.
    //
    // They are not the rung's own `py` repeated, which is the part worth writing
    // down. A labelled button is as tall as its text's line-height plus padding; an
    // icon button is as tall as its icon plus padding, and 14px of icon is not 16px
    // of line-height. So each value here is whatever closes that gap, and only `md`
    // comes out matching its `py` -- the one rung where the icon and the line-height
    // happen to be the same 20px.
    //
    // Padding rather than a fixed `size-*` for one reason: a border sits outside the
    // box either way, so it adds its 2px to a padded button and to a fixed one that
    // has no room for it. Stated as padding, an outline icon button matches an
    // outline text button, and a solid one matches a solid one -- which is what
    // makes the claim hold for every variant rather than for the three without a
    // border. `xs` at 24px is also what keeps the WCAG 2.5.8 floor true for a button
    // with no label to widen it.
    //
    // No `gap` and no `text`: neither has anything to size when there is one child
    // and no words.
    $squares = [
        'xs' => 'p-1.25',
        'sm' => 'p-2',
        'md' => 'p-2',
        'lg' => 'p-2.5',
    ];

    // Variants are a closed set, so an unknown one falls back. Colours are not:
    // there is no list to check a consumer's own role against, and rejecting it
    // would be the denial this design exists to avoid. All that is left to enforce
    // is the shape of a CSS identifier, which stops an interpolated value from
    // smuggling extra classes onto the element.
    $role = preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1 ? $color : 'neutral';

    $classes = str_replace(':role', $role, $recipes[$variant] ?? $recipes['outline']);

    // The rung is resolved rather than only its classes, because every icon on the
    // button is handed the same one: an icon in a `sm` button should be the `sm`
    // icon, and a size the scale does not have has to fall back everywhere at once.
    $rung = isset($sizes[$size]) ? $size : 'md';

    // An icon is a prop rather than something the slot has to spell out, which is
    // the whole of what these three lines buy: the rung above is already resolved,
    // so the component can size the icon correctly and a call site cannot get the
    // pair out of step by changing one and forgetting the other. The slot is still
    // there for everything a prop cannot say -- a second set on one button, an icon
    // carrying its own colour, classes of its own.
    //
    // Guarded like the colour role is, and for the same reason: a bare
    // `<shape:button icon>` arrives as `true` and would go looking for an icon
    // named "1". Nothing beyond the type is checked here, because a name with no
    // artwork behind it is the icon component's exception to throw and it already
    // says which component it failed to find.
    $lead = is_string($icon) && $icon !== '' ? $icon : null;
    $trail = is_string($iconTrailing) && $iconTrailing !== '' ? $iconTrailing : null;
    $set = is_string($iconSet) && $iconSet !== '' ? $iconSet : 'default';

    // One `set` for both icons rather than one each. A single button drawing its two
    // marks from two different libraries is not a thing anyone wants; a button whose
    // icons come from a set other than the default is, and that is what this covers.
    //
    // An icon and no label is an icon-only button, which is a shape rather than a
    // mode: there is no prop for it because the markup already says it. The slot is
    // trimmed first so that a tag written across three lines counts as empty, which
    // is how anyone who indents their Blade will write one.
    $bare = ($lead !== null || $trail !== null) && trim((string) $slot) === '';

    $scale = $bare ? $squares[$rung] : $sizes[$rung];

    // Loading is disabled with a reason, and both halves matter: `disabled` is
    // what stops the second submit, `aria-busy` is what says why. They are merged
    // as defaults, so a call site that wants to spin without disabling can still
    // say so -- though `merge` can add an attribute and never take one away.
    $state = $busy ? ['disabled' => 'disabled', 'aria-busy' => 'true'] : [];
@endphp

<button {{ $attributes->merge(array_merge(['type' => 'button', 'class' => $base.' '.$scale.' '.$classes], $state)) }}>
    {{-- Everything the button says, in one wrapper the loading state can hide.

         `contents` is what makes that free. A wrapper that generated a box would
         collapse an icon-and-label into a single flex item and swallow the rung's
         `gap`; with `display: contents` the children stay flex items of the button
         itself, so this span costs nothing in the state that is not busy and the
         markup does not have to be written out twice to avoid it.

         What it buys in the state that is busy: the label stays in the layout, so a
         form that starts submitting does not reflow around it -- and so do the
         icons, which is the half that only matters now they exist. `visibility` is
         inherited, so hiding the wrapper hides all three, and takes them out of the
         accessibility tree, which is why the spinner below carries the label. --}}
    <span @class(['contents', 'invisible' => $busy])>
        {{-- No `color` and no `label`, both deliberate. Colour is left off so the
             icon inherits the button's `currentColor`, which is what makes one
             recipe work for every variant without the icon needing to know which
             one it landed in. The label is left off because the words beside it
             already say what the button does -- and where there are no words, the
             accessible name belongs on the button element, not on one of its
             children. --}}
        @if ($lead !== null)
            <x-shape::icon :name="$lead" :set="$set" :size="$rung" />
        @endif

        {{ $slot }}

        @if ($trail !== null)
            <x-shape::icon :name="$trail" :set="$set" :size="$rung" />
        @endif
    </span>

    {{-- The overlay is the wrapper's sibling rather than its child, which is what
         keeps it visible and on the button's own `currentColor`.

         Written long-form rather than as `<shape:icon>`: the short tag is a
         convenience the package compiles for applications, and its own views
         should not need it to render.

         `animate-spin` is a plain rotation because the artwork is the consumer's --
         the alias can point at a ring, a dial, or a set Shape has never seen, and
         continuous rotation is the one motion that suits all of them. The dynamic
         props decline to fold, which is right: a folded label would freeze the
         locale into the compiled view the same way folding would freeze this
         component's config. --}}
    @if ($busy)
        <span class="absolute inset-0 grid place-items-center">
            <x-shape::icon name="spinner" :size="$rung" class="animate-spin" :label="__('shape::messages.button.loading')" />
        </span>
    @endif
</button>
