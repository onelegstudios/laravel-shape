<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * The bar itself -- the outer `<header>` and nothing inside it. Asserting on this
 * rather than on the whole render is what keeps a claim about the chrome from
 * passing on a class that happens to appear on an item.
 */
function headerBar(string $html): string
{
    preg_match('/<header[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

/**
 * The track the bar centres its contents in -- the first `<div>` inside it, which
 * is where the width and the density rung land.
 */
function headerTrack(string $html): string
{
    preg_match('/<header[^>]*>\s*<div[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

/**
 * A header with the given attributes and one item inside it, which is the shape
 * most of these assertions want: something for the bar to size and something to
 * inherit that size.
 */
function renderHeader(string $attributes = '', string $item = ''): string
{
    return Blade::render(
        '<shape:header '.$attributes.'><shape:header.item '.$item.' href="/docs">Docs</shape:header.item></shape:header>',
    );
}

it('paints the bar in chrome so it can be tinted apart from the fields below it', function () {
    // The argument for `--color-chrome` being its own token rather than `surface`:
    // an application tinting its bar should not be tinting every text input on the
    // page to get there.
    expect(headerBar(renderHeader()))
        ->toContain('bg-chrome')
        ->toContain('border-b border-hairline');
});

it('renders each rung of the size scale differently', function (string $size, string $expected) {
    expect(headerTrack(renderHeader('size="'.$size.'"')))->toContain($expected);
})->with([
    'xs is the densest bar' => ['xs', 'gap-4 px-3 py-1.5'],
    'sm suits an application shell' => ['sm', 'gap-5 px-4 py-2'],
    'md is the default' => ['md', 'gap-6 px-4 py-3'],
    'lg is the roomiest' => ['lg', 'gap-8 px-6 py-4'],
]);

it('sizes to md when asked for nothing', function () {
    expect(renderHeader())->toBe(renderHeader('size="md"'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(renderHeader('size="huge"'))->toBe(renderHeader());
});

it('holds its regions further apart than a button holds its icon off its label', function () {
    // A gap measured for an icon and a word reads as one thing; a brand and a nav
    // are two, and the bar's scale says so. Pinned because the two tables are easy
    // to bring into line without noticing what that costs.
    preg_match('/gap-(\d+)/', headerTrack(renderHeader('size="md"')), $bar);
    preg_match('/gap-(\d+)/', Blade::render('<shape:button size="md">Save</shape:button>'), $button);

    expect((int) $bar[1])->toBeGreaterThan((int) $button[1]);
});

it('stops centring its contents where the container prop says', function (string $container, string $expected) {
    expect(headerTrack(renderHeader('container="'.$container.'"')))->toContain($expected);
})->with([
    '3xl is the narrowest' => ['3xl', 'max-w-3xl'],
    '5xl matches a reading column' => ['5xl', 'max-w-5xl'],
    '7xl is the default' => ['7xl', 'max-w-7xl'],
    'full is for a shell with no centred column' => ['full', 'max-w-full'],
]);

it('falls back to the default container when given one it does not have', function () {
    expect(renderHeader('container="enormous"'))->toBe(renderHeader());
});

it('centres the track rather than the bar, so the chrome still runs edge to edge', function () {
    // The reason there are two elements. Painting the background on the centred
    // element would leave the page showing either side of a bar that is supposed to
    // be the top of the window.
    $html = renderHeader('container="3xl"');

    expect(headerBar($html))
        ->toContain('w-full')
        ->not->toContain('max-w-3xl');

    expect(headerTrack($html))->toContain('mx-auto')->toContain('max-w-3xl');
});

it('follows the scroll only when asked to', function () {
    expect(headerBar(renderHeader('sticky')))->toContain('sticky top-0 z-40');
    expect(headerBar(renderHeader()))->not->toContain('sticky');
});

it('reads a stringified false as not sticky', function () {
    // The interesting call site passes a variable, and a template that stringified
    // one should not pin the bar to the top of every page.
    expect(headerBar(renderHeader('sticky="false"')))->not->toContain('sticky');
});

it('claims no banner role, so the bar is usable somewhere it would be wrong', function () {
    // A `<header>` outside an article, aside, main, nav or section already is the
    // banner. Writing the role restates it there and lies one level down.
    expect(renderHeader())->not->toContain('role=');
});

it('merges class onto the bar rather than the track', function () {
    // Chrome is the thing a call site most often wants to restate -- a transparent
    // bar over a hero, a heavier rule -- so `class` has to reach it.
    expect(headerBar(renderHeader('class="border-none"')))->toContain('border-none');
});

it('renders the brand as a link only when there is somewhere to go', function () {
    expect(Blade::render('<shape:header.brand href="/">Acme</shape:header.brand>'))
        ->toContain('<a href="/"');

    expect(Blade::render('<shape:header.brand>Acme</shape:header.brand>'))
        ->toContain('<div')
        ->not->toContain('<a');
});

it('gives the brand a focus ring only on the path that can take focus', function () {
    expect(Blade::render('<shape:header.brand href="/">Acme</shape:header.brand>'))
        ->toContain('focus-visible:outline-neutral-ring');

    expect(Blade::render('<shape:header.brand>Acme</shape:header.brand>'))
        ->not->toContain('focus-visible');
});

it('reads a bare href on the brand as no href at all', function () {
    // `<shape:header.brand href>` arrives as `true` and would link to "1", which is
    // a working link to the wrong place rather than a visible mistake.
    expect(Blade::render('<shape:header.brand href>Acme</shape:header.brand>'))
        ->not->toContain('<a');
});

it('keeps the brand from compressing when the nav beside it runs long', function () {
    expect(Blade::render('<shape:header.brand>Acme</shape:header.brand>'))->toContain('shrink-0');
});

it('names the nav landmark so it can be told from the others on the page', function () {
    expect(Blade::render('<shape:header.nav>Links</shape:header.nav>'))
        ->toContain('<nav aria-label="Main"');
});

it('lets a second nav in the bar name itself', function () {
    // Merged as a default rather than written, so a page with two navs in its
    // header does not need a prop to tell them apart.
    expect(Blade::render('<shape:header.nav aria-label="Account">Links</shape:header.nav>'))
        ->toContain('aria-label="Account"')
        ->not->toContain('Main');
});

it('marks the current item for a screen reader as well as for an eye', function () {
    // Two channels for one meaning. Colour alone would leave anyone not looking at
    // the colour unable to tell which page they are on, which is the whole job of
    // this state.
    $html = renderHeader('', 'current');

    expect($html)
        ->toContain('aria-current="page"')
        ->toContain('bg-neutral-tint text-ink');
});

it('leaves an item that is not current muted and unmarked', function () {
    expect(renderHeader())
        ->not->toContain('aria-current')
        ->toContain('text-ink-muted');
});

it('renders each rung of the item scale differently', function (string $size, string $expected) {
    expect(Blade::render('<shape:header.item size="'.$size.'" href="/docs">Docs</shape:header.item>'))
        ->toContain($expected);
})->with([
    'xs' => ['xs', 'px-2 py-1 text-xs'],
    'sm' => ['sm', 'px-2.5 py-1 text-sm'],
    'md' => ['md', 'px-3 py-1.5 text-sm'],
    'lg' => ['lg', 'px-3.5 py-2 text-base'],
]);

it('sizes an item from the header it stands in', function () {
    expect(renderHeader('size="lg"'))->toContain('px-3.5 py-2 text-base');
});

it('lets an item name a rung the header did not', function () {
    // The precedence `@aware` gets wrong on its own: it assigns unconditionally, so
    // the value written on the tag has to be read off the attribute bag first or
    // the header beats it. Pinned because the markup renders either way.
    expect(renderHeader('size="sm"', 'size="lg"'))->toContain('px-3.5 py-2 text-base');
});

it('sizes a stray item like the ones in a bar rather than like nothing', function () {
    // An item outside a header has no `@aware` to read, so it falls through to the
    // header's own config key.
    expect(Blade::render('<shape:header.item href="/docs">Docs</shape:header.item>'))
        ->toContain('px-3 py-1.5 text-sm');
});

it('keeps the size it resolved off the item element', function () {
    // `size` was never a prop, so nothing takes it off the bag: without `except` it
    // would style the item *and* render as a stray attribute on the anchor.
    expect(Blade::render('<shape:header.item size="lg" href="/docs">Docs</shape:header.item>'))
        ->not->toContain('size="lg"');
});

it('falls back to the default rung when an item is given one it does not have', function () {
    expect(Blade::render('<shape:header.item size="huge" href="/docs">Docs</shape:header.item>'))
        ->toBe(Blade::render('<shape:header.item href="/docs">Docs</shape:header.item>'));
});

it('forwards attributes to the element each part renders', function (string $tag, string $expected) {
    expect(Blade::render($tag))->toContain($expected);
})->with([
    'the bar takes an id' => ['<shape:header id="top">x</shape:header>', 'id="top"'],
    'the brand takes a title' => ['<shape:header.brand title="Home">x</shape:header.brand>', 'title="Home"'],
    'the nav takes an id' => ['<shape:header.nav id="main">x</shape:header.nav>', 'id="main"'],
    'the item takes a wire directive' => ['<shape:header.item wire:navigate href="/">x</shape:header.item>', 'wire:navigate'],
]);
