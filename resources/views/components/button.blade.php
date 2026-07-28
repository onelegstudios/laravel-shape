@props([
    'variant' => null,
    'color' => null,
    'size' => null,
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
    $base = 'inline-flex items-center justify-center rounded-md transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50';

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

    // Variants are a closed set, so an unknown one falls back. Colours are not:
    // there is no list to check a consumer's own role against, and rejecting it
    // would be the denial this design exists to avoid. All that is left to enforce
    // is the shape of a CSS identifier, which stops an interpolated value from
    // smuggling extra classes onto the element.
    $role = preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1 ? $color : 'neutral';

    $classes = str_replace(':role', $role, $recipes[$variant] ?? $recipes['outline']);
    $scale = $sizes[$size] ?? $sizes['md'];
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $base.' '.$scale.' '.$classes]) }}>
    {{ $slot }}
</button>
