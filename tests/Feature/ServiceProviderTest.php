<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Artisan;
use Onelegstudios\Shape\Shape;

it('resolves the singleton', function () {
    expect(app(Shape::class))->toBeInstanceOf(Shape::class);
});

it('returns the same instance from the container', function () {
    expect(app(Shape::class))->toBe(app(Shape::class));
});

it('merges the package config', function () {
    expect(config('shape.components.button.variant'))->toBe('outline');
});

it('loads the package translations', function () {
    expect(trans('shape::messages.button.loading'))->toBe('Loading');
});

it('loads the package views', function (string $component) {
    expect(view()->exists('shape::components.'.$component))->toBeTrue();
})->with([
    'button', 'icon', 'field', 'label', 'description', 'error',
    'input', 'select', 'textarea', 'file', 'checkbox', 'radio', 'switch',
    'range', 'color',
]);

it('registers the artisan commands', function () {
    $registered = array_keys(Artisan::all());

    expect($registered)->toContain(
        'shape:install',
        'shape:icon',
        'shape:icon:add',
        'shape:icon:check',
        'shape:icon:remove',
        'shape:icon:update',
    );
});
