<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\View\ViewException;

it('renders an svg from the configured default set', function () {
    $html = Blade::render('<shape:icon name="check" />');

    expect($html)
        ->toContain('<svg')
        ->toContain('stroke="currentColor"');
});

it('resolves a set name through config rather than hard-coding a library', function () {
    // The point of the indirection: the call site names a set, config decides
    // which library that is. Nothing in the markup mentions Lucide or the fixture.
    config()->set('shape.icons.sets', ['glyph' => 'fixture']);

    expect(Blade::render('<shape:icon name="cross" set="glyph" />'))
        ->toContain('data-fixture="cross"');
});

it('moves every unnamed call site at once when the default set changes', function () {
    // The payoff. One config edit repoints an application from one icon library
    // to another without a single view being touched.
    config()->set('shape.icons.set', 'fixture');

    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('data-fixture="check"');
});

it('passes an unmapped set name through as a prefix', function () {
    // A set that is not registered in config is already a prefix, so using it
    // ad hoc works. Falling back to the default set instead would answer a request
    // for one library with an icon from another and call it success.
    expect(Blade::render('<shape:icon name="check" set="fixture" />'))
        ->toContain('data-fixture="check"');
});

it('raises the underlying error for a set that does not exist', function () {
    // Which is what makes passing an unmapped name through safe: a typo is loud,
    // and names the prefix that was tried, rather than being silently served from
    // the default set. Blade wraps what the view threw, so the assertion is on the
    // message -- SvgNotFound arrives as the previous exception.
    Blade::render('<shape:icon name="check" set="lucidee" />');
})->throws(ViewException::class, 'lucidee-check');

it('resolves a semantic alias to the name the set actually uses', function () {
    // Shape's own components ask for `close`; Lucide calls that icon `x`. Neither
    // the component nor the call site needs to know which.
    config()->set('shape.icons.aliases', ['close' => 'cross']);
    config()->set('shape.icons.set', 'fixture');

    expect(Blade::render('<shape:icon name="close" />'))
        ->toContain('data-fixture="cross"');
});

it('leaves a name with no alias untouched', function () {
    // Aliases are a small table for the icons Shape renders itself, not a second
    // vocabulary every call site has to be taught.
    config()->set('shape.icons.aliases', ['close' => 'cross']);
    config()->set('shape.icons.set', 'fixture');

    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('data-fixture="check"');
});

it('renders each rung of the size scale differently', function (string $size, string $expected) {
    $html = Blade::render('<shape:icon name="check" size="'.$size.'" />');

    expect($html)->toContain($expected);
})->with([
    'xs matches a table row' => ['xs', 'size-3.5'],
    'sm matches a toolbar' => ['sm', 'size-4'],
    'md sits beside text-sm' => ['md', 'size-5'],
    'lg matches the largest button' => ['lg', 'size-6'],
]);

it('shares its rung names with the button so the two compose', function (string $size) {
    // A `size="sm"` icon inside a `size="sm"` button should be the obvious thing
    // to write, which only holds while both components answer to the same names.
    expect(Blade::render('<shape:icon name="check" size="'.$size.'" />'))
        ->not->toContain('size-5');
})->with(['xs', 'sm', 'lg']);

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:icon name="check" size="huge" />'))
        ->toBe(Blade::render('<shape:icon name="check" />'));
});

it('keeps an icon from being squashed by a long label beside it', function () {
    expect(Blade::render('<shape:icon name="check" />'))->toContain('shrink-0');
});

it('inherits its colour by default so it takes the colour of what it sits in', function () {
    // The default that matters most: an icon inside a solid danger button has to
    // come out the button's colour, which means no colour class at all.
    expect(Blade::render('<shape:icon name="check" />'))
        ->not->toContain('text-neutral')
        ->not->toContain('-on-tint');
});

it('names a semantic surface token when given a colour role', function () {
    expect(Blade::render('<shape:icon name="check" color="danger" />'))
        ->toContain('text-danger-on-tint');
});

it('styles a colour role the package does not ship', function () {
    expect(Blade::render('<shape:icon name="check" color="ocean" />'))
        ->toContain('text-ocean-on-tint');
});

it('falls back to inheriting when the colour is not shaped like a CSS identifier', function (string $color) {
    expect(Blade::render('<shape:icon name="check" color="'.$color.'" />'))
        ->toBe(Blade::render('<shape:icon name="check" />'));
})->with([
    'a smuggled class' => ['danger hidden'],
    'an arbitrary value' => ['[#bada55]'],
    'a variant prefix' => ['danger md:text-red-500'],
    'empty' => [''],
    'uppercase' => ['Danger'],
]);

it('hides a decorative icon from assistive technology by default', function () {
    // Most icons repeat a label that is already beside them, so announcing them
    // is noise.
    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('aria-hidden="true"')
        ->not->toContain('role="img"');
});

it('announces an icon that is the only content', function () {
    $html = Blade::render('<shape:icon name="check" label="Saved" />');

    expect($html)
        ->toContain('role="img"')
        ->toContain('aria-label="Saved"')
        ->not->toContain('aria-hidden');
});

it('escapes a label rather than letting it reach the attribute raw', function () {
    expect(Blade::render('<shape:icon name="check" label="Rock &amp; Roll" />'))
        ->toContain('aria-label="Rock &amp; Roll"');
});

it('lets a call site override the accessibility default', function () {
    expect(Blade::render('<shape:icon name="check" aria-hidden="false" />'))
        ->toContain('aria-hidden="false"');
});

it('merges consumer classes without losing the size', function () {
    // Blade Icons drops its own class argument once the attribute bag carries a
    // class, so a caller nudging the alignment could silently unsize the icon.
    $html = Blade::render('<shape:icon name="check" class="-ml-0.5" />');

    expect($html)
        ->toContain('size-5')
        ->toContain('-ml-0.5');
});

it('forwards arbitrary attributes to the svg', function () {
    expect(Blade::render('<shape:icon name="check" data-testid="tick" />'))
        ->toContain('data-testid="tick"');
});

it('does not leak the resolution props onto the rendered svg', function () {
    $html = Blade::render('<shape:icon name="check" set="lucide" size="lg" color="info" label="Done" />');

    // Leading spaces matter here: `aria-label="Done"` legitimately contains
    // `label="Done"`, and the assertion is about the prop being forwarded verbatim.
    expect($html)
        ->not->toContain(' name="check"')
        ->not->toContain(' set="lucide"')
        ->not->toContain(' size="lg"')
        ->not->toContain(' color="info"')
        ->not->toContain(' label="Done"');
});

it('takes the value of every unnamed prop from config', function () {
    config()->set('shape.components.icon', ['size' => 'lg']);

    expect(Blade::render('<shape:icon name="check" />'))
        ->toBe(Blade::render('<shape:icon name="check" size="lg" />'));
});

it('lets a call site override the configured default', function () {
    config()->set('shape.components.icon', ['size' => 'lg']);

    expect(Blade::render('<shape:icon name="check" size="xs" />'))
        ->toContain('size-3.5');
});

it('ships config defaults that match the fallbacks baked into the component', function () {
    // Two copies of one fact drift. Rendering with the shipped config and then
    // with none at all has to come out identical.
    $configured = Blade::render('<shape:icon name="check" />');

    config()->set('shape.components.icon', null);
    config()->set('shape.icons', null);

    expect(Blade::render('<shape:icon name="check" />'))->toBe($configured);
});

it('falls back to a packaged default rather than rendering an unsized icon', function (mixed $components, mixed $icons) {
    // Laravel merges package config one level deep, so a published file that has
    // gone stale is not a hypothetical: drop a key and nothing restores it.
    config()->set('shape.components.icon', $components);
    config()->set('shape.icons', $icons);

    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('size-5')
        ->toContain('<svg');
})->with([
    'both blocks removed' => [null, null],
    'empty blocks' => [[], []],
    'blocks missing every key' => [['unrelated' => 'value'], ['unrelated' => 'value']],
    'values of the wrong type' => [['size' => ['lg']], ['set' => ['lucide'], 'sets' => 'lucide', 'aliases' => 3]],
    'blocks that are not arrays' => ['lg', 'lucide'],
]);
