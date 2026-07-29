<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use BladeUI\Icons\Factory as IconFactory;
use MallardDuck\LucideIcons\BladeLucideIconsServiceProvider;
use Onelegstudios\Shape\ShapeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeLucideIconsServiceProvider::class,
            ShapeServiceProvider::class,
        ];
    }

    /**
     * Register a second icon set so the tests can prove Shape resolves names
     * against more than one.
     *
     * Lucide covers the shipped default, but a set-switching test written against
     * two real libraries asserts on artwork that upstream is free to redraw. Two
     * fixture SVGs with a marker attribute say which file was read and nothing
     * else, and they keep the suite from needing a second vendor package to
     * exercise the mapping.
     *
     * Registration goes through the resolved factory rather than
     * `blade-icons.sets` config because Blade Icons resolves a configured path
     * against the application base path, which an absolute fixture path is not.
     */
    protected function defineEnvironment($app): void
    {
        $app->afterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('fixture', [
                'path' => __DIR__.'/Fixtures/svg',
                'prefix' => 'fixture',
            ]);
        });
    }
}
