<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

it('renders a component through the branded shape tag', function () {
    $html = Blade::render('<shape:button class="primary">Save</shape:button>');

    expect($html)
        ->toContain('<button')
        ->toContain('type="button"')
        ->toContain('class="primary"')
        ->toContain('Save');
});

it('renders a self-closing branded shape tag', function () {
    $html = Blade::render('<shape:button />');

    expect($html)->toContain('<button');
});

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
