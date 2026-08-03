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
    expect(trans('shape::messages.placeholder'))->toBe('Shape placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('shape::components.button'))->toBeTrue();
});

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
