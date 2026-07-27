<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('defaults to the quiet button so the primary action stays opt-in', function () {
    $html = Blade::render('<shape:button>Cancel</shape:button>');

    expect($html)
        ->toContain('border-neutral-border')
        ->not->toContain('bg-primary-fill');
});

it('renders each step of the emphasis ladder differently', function (string $variant, string $expected) {
    $html = Blade::render('<shape:button variant="'.$variant.'" color="primary">Save</shape:button>');

    expect($html)->toContain($expected);
})->with([
    'solid fills the surface' => ['solid', 'bg-primary-fill text-primary-on-fill'],
    'soft tints it' => ['soft', 'bg-primary-tint text-primary-on-tint'],
    'ghost stays transparent' => ['ghost', 'text-primary-on-tint hover:bg-primary-tint'],
    'outline is the only bordered step' => ['outline', 'border-primary-border'],
]);

it('names only semantic colour roles, never raw palette hues', function (string $color) {
    $html = Blade::render('<shape:button color="'.$color.'">Save</shape:button>');

    expect($html)->toContain($color.'-');
})->with(['primary', 'success', 'warning', 'danger', 'info', 'neutral']);

it('composes variant and colour independently', function () {
    $html = Blade::render('<shape:button variant="outline" color="danger">Delete</shape:button>');

    expect($html)
        ->toContain('border-danger-border')
        ->toContain('text-danger-on-tint')
        ->not->toContain('bg-danger-fill');
});

it('falls back to the default variant when given one it does not have', function () {
    // Variants are a closed set -- there are four rungs on the ladder and no way
    // for a consumer to add a fifth -- so an unknown one is a typo.
    expect(Blade::render('<shape:button variant="nope">Save</shape:button>'))
        ->toBe(Blade::render('<shape:button>Save</shape:button>'));
});

it('styles a colour role the package does not ship', function () {
    // The point of the open colour set: a consumer defines `accent` (or anything
    // else) in their own theme and the component follows without being taught it.
    $html = Blade::render('<shape:button variant="solid" color="accent">Save</shape:button>');

    expect($html)
        ->toContain('bg-accent-fill')
        ->toContain('text-accent-on-fill')
        ->toContain('hover:bg-accent-fill-hover')
        ->toContain('focus-visible:outline-accent-ring');
});

it('refuses a colour that is not shaped like a CSS identifier', function (string $color) {
    // Nothing here can know which roles a consumer defined, so the only guard left
    // is the shape of the name -- which is what stops an interpolated value from
    // smuggling extra classes onto the element.
    expect(Blade::render('<shape:button color="'.$color.'">Save</shape:button>'))
        ->toBe(Blade::render('<shape:button>Save</shape:button>'));
})->with([
    'a smuggled class' => ['neutral hidden'],
    'an arbitrary value' => ['[#bada55]'],
    'a variant prefix' => ['primary md:bg-red-500'],
    'empty' => [''],
    'uppercase' => ['Primary'],
]);

it('merges consumer classes alongside the recipe', function () {
    $html = Blade::render('<shape:button variant="soft" color="primary" class="w-full">Save</shape:button>');

    expect($html)
        ->toContain('bg-primary-tint')
        ->toContain('w-full');
});

it('does not leak the variant and colour props onto the rendered element', function () {
    $html = Blade::render('<shape:button variant="ghost" color="info">Save</shape:button>');

    expect($html)
        ->not->toContain('variant="ghost"')
        ->not->toContain('color="info"');
});

it('still honours a caller-supplied type attribute', function () {
    $html = Blade::render('<shape:button type="submit">Save</shape:button>');

    expect($html)
        ->toContain('type="submit"')
        ->not->toContain('type="button"');
});
