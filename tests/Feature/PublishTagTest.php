<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\ShapeServiceProvider;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * Publish destinations for a tag, with separators normalized to "/" so the
 * assertions below hold on Windows, where Laravel's path helpers join with
 * a backslash.
 *
 * @return list<string>
 */
function publishDestinations(string $tag): array
{
    return array_map(
        static fn (string $path): string => str_replace('\\', '/', $path),
        array_values(ServiceProvider::pathsToPublish(ShapeServiceProvider::class, $tag)),
    );
}

it('publishes the config under the shape tag', function () {
    $paths = publishDestinations('shape-config');

    expect($paths)->toHaveCount(1)
        ->and($paths[0])->toEndWith('config/shape.php');
});

it('publishes the views under the shape tag', function () {
    $paths = publishDestinations('shape-views');

    expect($paths)->toHaveCount(1)
        ->and($paths[0])->toEndWith('views/vendor/shape');
});

it('publishes the translations under the shape tag', function () {
    $paths = publishDestinations('shape-lang');

    expect($paths)->toHaveCount(1)
        ->and($paths[0])->toEndWith('lang/vendor/shape');
});

it('publishes the packaged icons under their own tag, outside the shape bundle', function () {
    // Not in the "shape" bundle on purpose: publishing an icon is what
    // `shape:icon` is for, and a consumer who publishes every resource at once
    // should not silently take ownership of the icons Shape renders itself.
    $paths = publishDestinations('shape-icons');

    // The destination follows `shape.icons.path`, which the suite points at a
    // per-process directory so parallel workers do not share one.
    expect($paths)->toHaveCount(1)
        ->and($paths[0])->toBe(str_replace('\\', '/', TestCase::iconPath()))
        ->and(publishDestinations('shape'))->not->toContain($paths[0]);
});

it('publishes the theme stylesheet under the shape tag', function () {
    $paths = publishDestinations('shape-css');

    expect($paths)->toHaveCount(1)
        ->and($paths[0])->toEndWith('css/vendor/shape');
});

it('publishes every resource under the shared shape tag', function () {
    expect(publishDestinations('shape'))->toHaveCount(4);
});
