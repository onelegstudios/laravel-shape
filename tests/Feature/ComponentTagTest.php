<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders a component through the branded shape tag', function () {
    $html = Blade::render('<shape:button class="w-full">Save</shape:button>');

    expect($html)
        ->toContain('<button')
        ->toContain('type="button"')
        ->toContain('w-full')
        ->toContain('Save');
});

it('renders a self-closing branded shape tag', function () {
    $html = Blade::render('<shape:button />');

    expect($html)->toContain('<button');
});

it('rewrites a dotted tag to the component in the subdirectory', function (string $tag, string $element) {
    // The two families with views in a subdirectory, and the only thing that
    // exercises a dot in the branded syntax. The rewrite never consumes the name --
    // it matches the opener and hands the rest to Blade, whose own tag patterns
    // allow a dot -- so this works by not being special-cased. Worth pinning anyway:
    // nothing else would catch a lookahead that started eating the name.
    $html = Blade::render($tag);

    expect($html)
        ->toContain($element)
        ->not->toContain('shape:');
})->with([
    'the input affix' => ['<shape:input.prefix>$</shape:input.prefix>', '<span'],
    'a header nav item' => ['<shape:header.item href="/">Docs</shape:header.item>', '<a'],
]);

it('resolves the same component through the x-shape namespace', function () {
    $html = Blade::render('<x-shape::button>Save</x-shape::button>');

    expect($html)
        ->toContain('<button')
        ->toContain('Save');
});

it('leaves unrelated markup untouched', function () {
    $html = Blade::render('<section>plain</section>');

    expect(trim($html))->toBe('<section>plain</section>');
});
