@props([
    'variant' => 'outline',
    'color' => 'neutral',
])

@php
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
    $base = 'inline-flex items-center justify-center gap-2 rounded-md px-4 py-2 text-sm transition-colors focus-visible:outline-2 focus-visible:outline-offset-2 disabled:pointer-events-none disabled:opacity-50';

    $recipes = [
        'solid' => 'font-semibold shadow-sm bg-:role-fill text-:role-on-fill hover:bg-:role-fill-hover hover:shadow-md active:shadow-sm focus-visible:outline-:role-ring',
        'soft' => 'font-medium bg-:role-tint text-:role-on-tint hover:bg-:role-tint-hover focus-visible:outline-:role-ring',
        'ghost' => 'font-medium text-:role-on-tint hover:bg-:role-tint focus-visible:outline-:role-ring',
        'outline' => 'font-medium border border-:role-border text-:role-on-tint hover:bg-:role-tint focus-visible:outline-:role-ring',
    ];

    // Variants are a closed set, so an unknown one falls back. Colours are not:
    // there is no list to check a consumer's own role against, and rejecting it
    // would be the denial this design exists to avoid. All that is left to enforce
    // is the shape of a CSS identifier, which stops an interpolated value from
    // smuggling extra classes onto the element.
    $role = preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1 ? $color : 'neutral';

    $classes = str_replace(':role', $role, $recipes[$variant] ?? $recipes['outline']);
@endphp

<button {{ $attributes->merge(['type' => 'button', 'class' => $base.' '.$classes]) }}>
    {{ $slot }}
</button>
