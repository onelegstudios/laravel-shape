<?php

declare(strict_types=1);

use Onelegstudios\Shape\Shape;

it('resolves the singleton', function () {
    expect(app(Shape::class))->toBeInstanceOf(Shape::class);
});

it('returns the same instance from the container', function () {
    expect(app(Shape::class))->toBe(app(Shape::class));
});

it('merges the package config', function () {
    expect(config('shape.placeholder'))->toBe('default');
});

it('loads the package translations', function () {
    expect(trans('shape::messages.placeholder'))->toBe('Shape placeholder translation.');
});

it('loads the package views', function () {
    expect(view()->exists('shape::placeholder'))->toBeTrue();
});

it('registers the artisan command', function () {
    $this->artisan('shape:placeholder')
        ->expectsOutputToContain('Shape placeholder command executed.')
        ->assertSuccessful();
});
