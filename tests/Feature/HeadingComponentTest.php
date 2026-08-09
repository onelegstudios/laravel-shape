<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * The heading element and its classes, with everything around it dropped. What most
 * of these assertions are about is which `h*` came out and what type it is set in,
 * and reading it off the whole render would let a class on the wrapper answer for
 * one on the title.
 */
function headingElement(string $html): string
{
    preg_match('/<h[1-6][^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders an h2 when nothing says otherwise', function () {
    // The level a section heading has, which is what this component mostly is. A
    // page's h1 is the exception and names itself.
    expect(headingElement(Blade::render('<shape:heading>Title</shape:heading>')))
        ->toStartWith('<h2');
});

it('renders the element the level names', function (string $level, string $expected) {
    expect(Blade::render('<shape:heading level="'.$level.'">Title</shape:heading>'))
        ->toContain($expected);
})->with([
    'the page title' => ['1', '<h1'],
    'a section' => ['3', '<h3'],
    'the deepest the language has' => ['6', '<h6'],
]);

it('falls back to a section heading when the level is not one', function (string $level) {
    expect(headingElement(Blade::render('<shape:heading level="'.$level.'">Title</shape:heading>')))
        ->toStartWith('<h2');
})->with([
    'zero' => ['0'],
    'past the six the language has' => ['7'],
    'not a number at all' => ['big'],
]);

it('keeps the level out of the appearance and the size out of the outline', function () {
    // The whole idea of the component: a section three levels down can be set in the
    // largest type on the page without claiming to be its first heading. Tying the
    // two together is what makes people reach for the wrong element to get the right
    // size.
    $small = Blade::render('<shape:heading level="1" size="xs">Title</shape:heading>');
    $large = Blade::render('<shape:heading level="6" size="lg">Title</shape:heading>');

    expect($small)->toContain('<h1')->toContain('text-sm');
    expect($large)->toContain('<h6')->toContain('text-3xl');
});

it('renders each rung of the size scale differently', function (string $size, string $expected) {
    expect(headingElement(Blade::render('<shape:heading size="'.$size.'">Title</shape:heading>')))
        ->toContain($expected);
})->with([
    'xs sits with body text' => ['xs', 'text-sm'],
    'sm is a subsection' => ['sm', 'text-base'],
    'md is the one a page is mostly made of' => ['md', 'text-xl'],
    'lg is the one at the top of it' => ['lg', 'text-3xl tracking-tight'],
]);

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:heading>Title</shape:heading>'))
        ->toBe(Blade::render('<shape:heading size="md">Title</shape:heading>'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:heading size="huge">Title</shape:heading>'))
        ->toBe(Blade::render('<shape:heading>Title</shape:heading>'));
});

it('tightens the tracking only on the rung large enough to need it', function () {
    // Letter-spacing that suits 16px text is loose at 30, and tightening the smaller
    // rungs would cost legibility to fix a problem they do not have.
    expect(Blade::render('<shape:heading size="lg">Title</shape:heading>'))->toContain('tracking-tight');
    expect(Blade::render('<shape:heading size="md">Title</shape:heading>'))->not->toContain('tracking');
});

it('renders a bare heading when there is nothing to put beside it', function () {
    // No wrapper, no landmark, no flex context for a single child to sit in. The
    // same call the input makes for `type="hidden"`, and what keeps this component
    // usable in the place headings mostly appear.
    $html = trim(Blade::render('<shape:heading>Title</shape:heading>'));

    expect($html)->toStartWith('<h2')->toEndWith('</h2>')
        ->and($html)->not->toContain('<header');
});

it('wraps in a header once there is a description to introduce', function () {
    $html = Blade::render('<shape:heading description="What this page is for">Title</shape:heading>');

    expect($html)
        ->toContain('<header')
        ->toContain('flex flex-col gap-1')
        ->toContain('<p class="text-ink-muted text-pretty text-sm">What this page is for</p>');
});

it('tells the title from the description on colour rather than on size alone', function () {
    // Hierarchy the library's own rule asks for: weight and colour first, so the
    // description stays large enough to read comfortably.
    $html = Blade::render('<shape:heading description="Help">Title</shape:heading>');

    expect(headingElement($html))->toContain('font-semibold')->toContain('text-ink');
    expect($html)->toContain('text-ink-muted');
});

it('steps the description down one rung from the title', function (string $size, string $expected) {
    preg_match('/<p class="([^"]*)"/', Blade::render(
        '<shape:heading size="'.$size.'" description="Help">Title</shape:heading>',
    ), $matches);

    expect($matches[1] ?? '')->toContain($expected);
})->with([
    'xs' => ['xs', 'text-xs'],
    'sm' => ['sm', 'text-sm'],
    'md' => ['md', 'text-sm'],
    'lg' => ['lg', 'text-base'],
]);

it('ignores a description that has nothing in it', function () {
    expect(Blade::render('<shape:heading description="">Title</shape:heading>'))
        ->toBe(Blade::render('<shape:heading>Title</shape:heading>'));
});

it('turns the stack into a row once there are actions', function () {
    $html = Blade::render(
        '<shape:heading><x-slot:actions><shape:button>Edit</shape:button></x-slot:actions>Title</shape:heading>',
    );

    expect($html)
        ->toContain('flex items-start justify-between gap-4')
        ->toContain('Edit');
});

it('lets a long title wrap rather than pushing the actions off the row', function () {
    // A flex item's minimum size is its content, so without `min-w-0` the title
    // would shove the buttons past the end of the row instead of giving way.
    $html = Blade::render('<shape:heading><x-slot:actions>Edit</x-slot:actions>Title</shape:heading>');

    expect($html)
        ->toContain('flex min-w-0 flex-col gap-1')
        ->toContain('flex shrink-0 items-center gap-2');
});

it('reads an actions slot with only whitespace in it as no actions', function () {
    // How anyone who indents their Blade writes a slot they meant to leave out.
    $html = Blade::render("<shape:heading><x-slot:actions>\n    \n</x-slot:actions>Title</shape:heading>");

    expect(trim($html))->toStartWith('<h2');
});

it('carries a description and actions together', function () {
    $html = Blade::render(
        '<shape:heading description="Help"><x-slot:actions>Edit</x-slot:actions>Title</shape:heading>',
    );

    expect($html)
        ->toContain('flex items-start justify-between gap-4')
        ->toContain('Help')
        ->toContain('Edit');
});

it('merges class onto whichever element is outermost', function () {
    // So a call site is always styling the thing the heading occupies rather than
    // one of its parts, whichever of the three shapes it came out as.
    expect(headingElement(Blade::render('<shape:heading class="mb-8">Title</shape:heading>')))
        ->toContain('mb-8');

    expect(Blade::render('<shape:heading class="mb-8" description="Help">Title</shape:heading>'))
        ->toContain('<header class="flex flex-col gap-1 mb-8"');
});

it('forwards attributes to the outermost element', function () {
    expect(Blade::render('<shape:heading id="section">Title</shape:heading>'))
        ->toContain('id="section"');

    expect(Blade::render('<shape:heading id="section" description="Help">Title</shape:heading>'))
        ->toMatch('/<header[^>]*id="section"/');
});
