@blaze(fold: true)

{{-- An icon is the component a dense page repeats most, so it is the one worth
     making foldable: with `fold: true` a static call site leaves no component
     behind at all, just the SVG inline in the calling template.

     That is only safe because nothing below touches global state. The set, the
     alias table, and the artwork itself were all resolved by `shape:icon` when
     the icon was published, so what is left here is arithmetic on the props: a
     size class, a colour class, and an accessibility default. A `config()` call
     anywhere in this file would freeze that config into every compiled view the
     first time it rendered, which is why the size default below is a literal
     rather than a lookup.

     A dynamic `:name` is not an error, it just declines to fold: Blaze falls back
     to the function compiler, which still skips Blade's component pipeline. --}}

@props([
    'name' => '',
    'set' => 'default',
    'size' => 'md',
    'color' => null,
    'label' => null,
])

@php
    // The same four rungs as the button, so `<shape:icon size="sm">` inside a
    // `size="sm"` button is the obvious thing rather than a lookup table. The
    // values are optical, not proportional: 20px is the icon that sits beside
    // `text-sm` without crowding it, and 14px is what survives a table row.
    $sizes = [
        'xs' => 'size-3.5',
        'sm' => 'size-4',
        'md' => 'size-5',
        'lg' => 'size-6',
    ];

    $scale = $sizes[$size] ?? $sizes['md'];

    // No colour means no colour class, which leaves the SVG on `currentColor` and
    // lets it take the colour of whatever it sits inside. `color` is for the
    // standalone icon that carries meaning by itself -- a status tick in a table.
    //
    // A value that is not shaped like a CSS identifier is dropped rather than
    // substituted into a class name, which lands back on inherit -- the default.
    $tint = is_string($color) && preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1
        ? ' text-'.$color.'-on-tint'
        : '';

    // Most icons repeat a label that is already next to them, so hiding them from
    // assistive tech is the default that is right more often. `label` is the
    // escape hatch for the icon that is the only content -- an icon-only button --
    // and it has to announce as an image to be read at all.
    //
    // These are merged as defaults, so an explicit aria-hidden or aria-label at
    // the call site still wins. The published icon deliberately sets no a11y of
    // its own: `merge` can add an attribute but never take one away, so an icon
    // that hid itself would leave `label` unable to unhide it.
    //
    // The label is decoded rather than escaped, which looks backwards and is not.
    // Dispatching through <x-dynamic-component> escapes attribute values exactly
    // once on the way in, with double-encoding on, so a `&amp;` written at the
    // call site would arrive as `&amp;amp;` and announce as "amp". Decoding first
    // and letting that one hop do the escaping is `e($label, false)` spelled for
    // the path the value actually takes: both a bare `&` and a written-out
    // `&amp;` come out as one escaped ampersand, which is what the markup around
    // this component would have done with the same text.
    $a11y = $label === null
        ? ['aria-hidden' => 'true']
        : ['role' => 'img', 'aria-label' => html_entity_decode($label, ENT_QUOTES, 'UTF-8')];

    // `shape-icon::` addresses the published icons, application copies first.
    // `default` is a real directory rather than a config lookup: `shape:icon`
    // writes one alongside the named set, forwarding to it, which is what keeps
    // the configured default set out of this file.
    $icon = 'shape-icon::'.$set.'.'.$name;

    // Reassigned rather than kept in a second variable: Blade's tag compiler
    // only treats the literal `{{ $attributes }}` as an attribute spread, and any
    // other name lands on the element as a stray `attrs="attrs"`.
    $attributes = $attributes->merge(array_merge(['class' => $scale.$tint], $a11y), escape: false);
@endphp

<x-dynamic-component :component="$icon" {{ $attributes }} />
