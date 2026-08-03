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
        return sys_get_temp_dir().'/shape-icons-'.self::worker();
    }

    /**
     * Where this process compiles views.
     *
     * Testbench resolves `view.compiled` to one directory in the shared skeleton,
     * and the icon verbs clear that directory wholesale -- folding copies an
     * icon's markup into every view that renders it, so a published file changing
     * means every compiled view is suspect. Under --parallel that makes one
     * worker's publish delete the views another worker is running on.
     *
     * POSIX hides it: a worker holding an open compiled view keeps reading it,
     * and one that loses a file recompiles. Windows does not, and the way it
     * fails is quiet -- Filesystem::delete() swallows the failed unlink and
     * returns false, which nothing checks, so the stale view survives the clear
     * and some later test renders artwork it never published.
     *
     * A directory per worker removes the sharing rather than the clearing, which
     * keeps the commands under test behaving as they do in an application.
     */
    public static function compiledViewPath(): string
    {
        return sys_get_temp_dir().'/shape-views-'.self::worker();
    }

    /**
     * Where this process publishes configuration.
     *
     * `shape:install` can publish `config/shape.php`, and the skeleton's config
     * directory is read at boot by every worker: a test that publishes into it
     * leaves a file the next boot loads, and one that cleans up afterwards can
     * delete it between another worker's directory listing and its require. That
     * failure is a fatal error in an unrelated test, which is a long way from
     * the test that caused it.
     *
     * Redirected before the provider boots, so the publish destination it
     * registers and the path the command checks are the same one.
     */
    public static function configPath(): string
    {
        return sys_get_temp_dir().'/shape-config-'.self::worker();
    }

    /**
     * The stylesheet this process installs the theme into.
     *
     * Inside the skeleton rather than in scratch space, because the import line
     * `shape:install` writes is relative to the application root -- a stylesheet
     * in /tmp would be given an absolute path and prove nothing about the one a
     * consumer gets. Keyed per worker for the usual reason: one skeleton, many
     * processes.
     *
     * Only callable once the application has booted.
     */
    public static function stylesheetPath(): string
    {
        return resource_path('css/shape-install-'.self::worker().'.css');
    }

    /**
     * What distinguishes this process from the others in a parallel run.
     *
     * TEST_TOKEN is Paratest's; the pid covers a single-process run, where there
     * is nobody to collide with anyway.
     */
    private static function worker(): string
    {
        $token = getenv('TEST_TOKEN');

        return (string) ($token === false ? getmypid() : $token);
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

        // The same sharing problem, for the directory the icon verbs clear (see
        // compiledViewPath). Blade writes into this path rather than creating it,
        // so it has to exist before the first render.
        $compiled = self::compiledViewPath();

        if (! is_dir($compiled)) {
            mkdir($compiled, 0777, true);
        }

        $app['config']->set('view.compiled', $compiled);

        // Config has been loaded by now and the providers have not booted, which
        // is the only window where moving this path is both safe and useful (see
        // configPath).
        $config = self::configPath();

        if (! is_dir($config)) {
            mkdir($config, 0777, true);
        }

        $app->useConfigPath($config);

        $app->afterResolving(IconFactory::class, function (IconFactory $factory): void {
            $factory->add('fixture', [
                'path' => __DIR__.'/Fixtures/svg',
                'prefix' => 'fixture',
            ]);
        });
    }
}
