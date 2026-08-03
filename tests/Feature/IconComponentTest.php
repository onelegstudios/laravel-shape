<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\View\ViewException;
use Onelegstudios\Shape\Tests\TestCase;

// The component renders published icons rather than reading SVGs itself, so the
// icons these tests need have to exist first. Name resolution -- aliases, set
// prefixes, the artwork -- is the command's job now and is covered against the
// command; what is left here is what the component still does: size, colour,
// accessibility, and passing the caller's attributes through.
beforeEach(function () {
    File::deleteDirectory(TestCase::iconPath());

    $this->artisan('shape:icon:add', ['name' => ['check', 'close'], '--no-clear' => true])->run();

    config()->set('shape.icons.sets', ['fixture' => 'fixture']);

    $this->artisan('shape:icon:add', [
        'name' => ['check', 'cross'],
        '--set' => 'fixture',
        '--no-clear' => true,
    ])->run();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('renders a published icon from the default set', function () {
    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('<svg')
        ->toContain('stroke="currentColor"');
});

it('renders from a named set', function () {
    expect(Blade::render('<shape:icon name="cross" set="fixture" />'))
        ->toContain('data-fixture="cross"');
});

it('fails loudly, naming the component it looked for, when an icon is not published', function () {
    // The whole point of dropping the runtime lookup: a name that was never
    // published fails the first time the page is loaded, in development, the same
    // way any other typo'd Blade component does -- rather than at runtime in
    // production, or silently from the wrong set.
    Blade::render('<shape:icon name="never-published" />');
})->throws(ViewException::class, 'shape-icon::default.never-published');

it('escapes an ampersand written plainly in a label', function () {
    expect(Blade::render('<shape:icon name="check" label="Rock & Roll" />'))
        ->toContain('aria-label="Rock &amp; Roll"');
});

it('renders each rung of the size scale differently', function (string $size, string $expected) {
    expect(Blade::render('<shape:icon name="check" size="'.$size.'" />'))->toContain($expected);
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
    expect(Blade::render('<shape:icon name="check" />'))
        ->toContain('aria-hidden="true"')
        ->not->toContain('role="img"');
});

it('announces an icon that is the only content', function () {
    // Which is why the published icon sets no accessibility of its own: `merge`
    // can add an attribute but never remove one, so an icon that hid itself could
    // not be unhidden from here.
    expect(Blade::render('<shape:icon name="check" label="Saved" />'))
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
    expect(Blade::render('<shape:icon name="check" class="-ml-0.5" />'))
        ->toContain('size-5')
        ->toContain('-ml-0.5');
});

it('forwards arbitrary attributes to the svg', function () {
    expect(Blade::render('<shape:icon name="check" data-testid="tick" />'))
        ->toContain('data-testid="tick"');
});

it('does not leak the resolution props onto the rendered svg', function () {
    $html = Blade::render('<shape:icon name="check" set="fixture" size="lg" color="info" label="Done" />');

    // Leading spaces matter here: `aria-label="Done"` legitimately contains
    // `label="Done"`, and the assertion is about the prop being forwarded verbatim.
    expect($html)
        ->not->toContain(' name="check"')
        ->not->toContain(' set="fixture"')
        ->not->toContain(' size="lg"')
        ->not->toContain(' color="info"')
        ->not->toContain(' label="Done"');
});

it('folds a static call site away entirely', function () {
    // The reason the component reads no config: with nothing global left, a call
    // site that names its icon leaves no component behind at all.
    expect(Blade::compileString('<shape:icon name="check" size="sm" />'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg')
        ->toContain('size-4');
});

it('still renders a dynamic name, by declining to fold', function () {
    // A dynamic name cannot be resolved at compile time, so Blaze falls back to
    // the function compiler. That is a smaller win, not a failure.
    expect(Blade::compileString('<shape:icon :name="$n" />'))
        ->not->toContain('[BlazeFolded]');

    expect(Blade::render('<shape:icon :name="$n" />', ['n' => 'check']))->toContain('<svg');
});
