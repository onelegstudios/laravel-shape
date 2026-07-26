<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\ShapeServiceProvider;

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

it('publishes every resource under the shared shape tag', function () {
    expect(publishDestinations('shape'))->toHaveCount(3);
});
