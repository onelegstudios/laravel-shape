@props([
    'name' => '',
    'set' => null,
    'size' => null,
    'color' => null,
    'label' => null,
])

@php
    // Shape resolves names and styles the result; Blade Icons finds the file. That
    // split is deliberate -- reading SVGs off disk, caching them, and registering
    // directory sets are solved problems. What is left here is the part a component
    // library is actually for: one name for an icon across sets, one size scale
    // shared with the button, and an accessibility default that is right more often
    // than not.
    //
    // Lucide is the set config points at, but Shape does not require it: the set
    // stays a package the consumer owns, so swapping it is `composer remove` rather
    // than a Composer trick to prune a dependency they cannot reach. Nothing below
    // names it except as the fallback for a config file that has gone missing.
    $defaults = array_filter((array) config('shape.components.icon'), 'is_string');
    $size ??= $defaults['size'] ?? 'md';

    $icons = (array) config('shape.icons');
    $sets = array_filter((array) ($icons['sets'] ?? []), 'is_string');
    $aliases = array_filter((array) ($icons['aliases'] ?? []), 'is_string');

    $set ??= is_string($icons['set'] ?? null) ? $icons['set'] : 'lucide';

    // Two lookups, both of which fall through rather than fail. An alias is a name
    // Shape's own components use so they need not know which library is installed;
    // a name with no alias is already an icon name and passes straight to the set.
    //
    // A set name that is not mapped is treated as a prefix as-is. That reads as
    // sloppy next to the closed sets on the button, but the alternative -- falling
    // back to the default set -- answers `set="heroicon-o"` with a Lucide icon and
    // calls it success. Passing it through means an ad-hoc prefix works untouched
    // and a genuine typo raises SvgNotFound naming the prefix that was tried.
    $name = $aliases[$name] ?? $name;
    $prefix = $sets[$set] ?? $set;
    $icon = $prefix === '' ? $name : $prefix.'-'.$name;

    // The same four rungs as the button, so `<shape:icon size="sm">` inside a
    // `size="sm"` button is the obvious thing rather than a lookup table. The
    // values are optical, not proportional: 20px is the icon that sits beside
    // `text-sm` without crowding it, and 14px is what survives a table row.
    //
    // `shrink-0` is the one class here that is not about size. An icon is a fixed
    // glyph next to text that wraps, and flex will happily squash it into an
    // ellipse to make room for a long label.
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
    // `on-tint` is the role's readable-text surface, the same token the button's
    // quiet variants use, so a role a consumer defined works here the moment its
    // tokens exist and is already covered by the safelist in shape.css. A value
    // that is not shaped like a CSS identifier is dropped rather than substituted
    // into a class name, and dropping it lands back on inherit, which is the
    // default -- the same bargain the button makes, with a quieter floor.
    $tint = is_string($color) && preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1
        ? ' text-'.$color.'-on-tint'
        : '';

    // Most icons repeat a label that is already next to them, so hiding them from
    // assistive tech is the default that is right more often. `label` is the
    // escape hatch for the icon that is the only content -- an icon-only button --
    // and it has to announce as an image to be read at all.
    //
    // These go on before the caller's own attributes so an explicit aria-hidden or
    // aria-label at the call site still wins.
    //
    // Blade Icons renders attribute values as given, so escaping the label is this
    // component's job -- without double-encoding, the way Laravel's own attribute
    // bag does it, or a `&amp;` written in the markup would announce as "amp".
    $a11y = $label === null
        ? ['aria-hidden' => 'true']
        : ['role' => 'img', 'aria-label' => e($label, false)];

    // Classes are merged here rather than handed to svg() as its class argument:
    // Blade Icons drops that argument entirely once the attribute bag carries a
    // class of its own, so a caller adding `class="-ml-0.5"` would silently lose
    // the size. Merging first means both survive, caller's classes last.
    $attrs = $attributes->merge(['class' => $scale.$tint.' shrink-0'])->getAttributes();
@endphp

{{ svg($icon, '', array_merge($a11y, $attrs)) }}
