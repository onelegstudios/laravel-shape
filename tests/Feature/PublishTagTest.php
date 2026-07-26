<?php

declare(strict_types=1);

use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\ShapeServiceProvider;

/**
 * @return list<string>
 */
function publishDestinations(string $tag): array
{
    return array_values(ServiceProvider::pathsToPublish(ShapeServiceProvider::class, $tag));
}

it('publishes the config under the shape tag', function () {
    expect(publishDestinations('shape-config'))
        ->toHaveCount(1)
        ->{0}->toEndWith('config/shape.php');
});

it('publishes the views under the shape tag', function () {
    expect(publishDestinations('shape-views'))
        ->toHaveCount(1)
        ->{0}->toEndWith('views/vendor/shape');
});

it('publishes the translations under the shape tag', function () {
    expect(publishDestinations('shape-lang'))
        ->toHaveCount(1)
        ->{0}->toEndWith('lang/vendor/shape');
});

it('publishes every resource under the shared shape tag', function () {
    expect(publishDestinations('shape'))->toHaveCount(3);
});
