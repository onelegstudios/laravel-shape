<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Tests;

use BladeUI\Icons\BladeIconsServiceProvider;
use BladeUI\Icons\Factory as IconFactory;
use Livewire\Blaze\BlazeServiceProvider;
use MallardDuck\LucideIcons\BladeLucideIconsServiceProvider;
use Onelegstudios\Shape\ShapeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    /**
     * Where this process publishes icons.
     */
    public static function iconPath(): string
    {
        $token = getenv('TEST_TOKEN');

        return sys_get_temp_dir().'/shape-icons-'.($token === false ? getmypid() : $token);
    }

    /**
     * An application discovers these from Composer; Testbench does not, so the
     * list has to be spelled out. Blaze belongs on it for the same reason the
     * icon packages do: the shipped components carry `@blaze` directives, so a
     * suite without it would assert against a rendering path no consumer uses
     * and pass whatever Blaze happened to break.
     */
    protected function getPackageProviders($app): array
    {
        return [
            BladeIconsServiceProvider::class,
            BladeLucideIconsServiceProvider::class,
            BlazeServiceProvider::class,
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
        // Icons are published to disk and the provider reads this path when it
        // boots, so it has to be set before that -- `config()->set` inside a test
        // is already too late. Keying it per process keeps parallel workers off
        // each other's directory, since they share one skeleton application.
        $app['config']->set('shape.icons.path', self::iconPath());

        $app->afterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('fixture', [
                'path' => __DIR__.'/Fixtures/svg',
                'prefix' => 'fixture',
            ]);
        });
    }
}
