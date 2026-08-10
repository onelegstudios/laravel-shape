@blaze(fold: true)

{{-- Folding is the strongest thing Blaze does: a static call site is evaluated at
     compile time and its markup written straight into the calling template, so
     there is no component left at render -- not even the function call the bare
     directive would leave. It also collapses the `icon` prop, which is otherwise
     the expensive way to put a mark on a button: the nested icon is resolved here,
     at compile time, rather than resolved dynamically on every render.

     What folding costs is that everything this file reads is read once. Two of
     those reads had to be dealt with before the directive could go on:

     - `config('shape.components.button')` below is now a *compile-time* input. The
       published defaults are still honoured -- editing config/shape.php invalidates
       every view that folded them, which ShapeServiceProvider arranges by stamping
       the file as a fold dependency -- but a default set at runtime is not.
       `Config::set(...)` from a service provider, and per-tenant config, no longer
       reach this component: whoever compiles the view first wins. That is the one
       promise this component gave up to get here, and docs/performance.md says so
       in as many words.
     - `__()` in the loading overlay would bake a locale, which no invalidation can
       repair. It sits in an island instead; see the comment there.

     `memo`, the remaining strategy, still does not apply: it caches rendered output
     per call-site signature and only covers components without slots, and a button
     is mostly slot.

     Two mechanical things about editing a file that folds. The directive has to be
     the first thing in it: Blaze looks for it with an anchored match, so a comment
     above it reads as no directive at all. And comments are not inert here --
     folding pre-processes the raw source before Blade strips them, so a directive
     name written with its at-sign inside a comment is matched as the real thing.
     One in this block would pair itself with the island's closing tag below and
     swallow the component whole. Name directives in prose, as above. --}}

@props([
    'variant' => null,
    'color' => null,
    'size' => null,
    'loading' => false,
    'icon' => null,
    'iconTrailing' => null,
    'iconSet' => 'default',
    'square' => null,
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
    //
    // One `set` for both icons rather than one each. A single button drawing its two
    // marks from two different libraries is not a thing anyone wants; a button whose
    // icons come from a set other than the default is, and that is what this covers.
    $lead = is_string($icon) && $icon !== '' ? $icon : null;
    $trail = is_string($iconTrailing) && $iconTrailing !== '' ? $iconTrailing : null;
    $set = is_string($iconSet) && $iconSet !== '' ? $iconSet : 'default';

    // An icon and no label is an icon-only button, which the markup usually says on
    // its own: a mark, no words, nothing to hold apart. So the shape is inferred,
    // and `square` is there for the one call site the inference cannot read.
    //
    // That call site is a slot holding nothing but a line break:
    //
    //     <shape:button icon="check">
    //     </shape:button>
    //
    // which is how anyone who indents their Blade writes an icon button, and which
    // the two pipelines genuinely disagree about. Rendered, the slot arrives
    // trimmed and this reads it as no label. Folded, Blaze synthesises a slot
    // placeholder for whitespace-only content before the template is evaluated, so
    // `$slot` is a token standing in for content that has not been restored yet --
    // not empty, and not something trimming can rescue, because the whitespace is
    // no longer what is in there. The same tag comes out square through one
    // pipeline and padded through the other, and which one you get depends on
    // whether some unrelated prop happened to be dynamic.
    //
    // Nothing in a template can close that gap, so this does not pretend to: the
    // test is the plain one, and `square` is how an indented call site says what it
    // means. A self-closing tag says it too, and is the shorter answer where there
    // is nothing to put in the slot anyway.
    //
    // Guarded like `loading` is, and for the same reason: a template that
    // stringified a false should not read as square.
    $bare = $square !== null
        ? filter_var($square, FILTER_VALIDATE_BOOLEAN)
        : (($lead !== null || $trail !== null) && (string) $slot === '');

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
         continuous rotation is the one motion that suits all of them.

         The island below is what makes the rest of this component foldable. `__()`
         is a per-request read: folding evaluates the template once, at compile
         time, so a baked label would hand every later request whichever locale
         happened to compile the view. The island lifts this block out of that --
         Blaze sets it aside before folding and re-injects it uncompiled, so the
         translation is looked up on render as it always was. What the block cannot
         see is the component's own scope, since it is no longer inside the function
         Blaze wrote; `$scope` is the way across, and `$rung` is the one value it
         needs.

         The cost is that the icon here is compiled but never folded, because island
         content skips the folding pass. It was already unfoldable -- a dynamic
         `:size` and `:label` decline -- so nothing is lost, and it only renders in
         the busy state either way. --}}
    @if ($busy)
        @unblaze(['rung' => $rung])
            <span class="absolute inset-0 grid place-items-center">
                <x-shape::icon name="spinner" :size="$scope['rung']" class="animate-spin" :label="__('shape::messages.button.loading')" />
            </span>
        @endunblaze
    @endif
</button>
