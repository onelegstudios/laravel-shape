<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\Console\Commands\AddIconCommand;
use Onelegstudios\Shape\Console\Commands\IconCommand;
use Onelegstudios\Shape\Console\Commands\ShapeCommand;

class ShapeServiceProvider extends ServiceProvider
{
    /**
     * The tag prefix used to reference Shape components, e.g. <shape:button />.
     */
    private const string TAG_PREFIX = 'shape';

    /**
     * The tag prefix published icons are reachable under, e.g.
     * <x-shape-icon::lucide.check />.
     */
    private const string ICON_PREFIX = 'shape-icon';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/shape.php', 'shape');

        $this->app->singleton(Shape::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'shape');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'shape');

        $this->registerIconNamespace();

        $this->registerBladeComponents();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/shape.php' => config_path('shape.php'),
        ], ['shape', 'shape-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/shape'),
        ], ['shape', 'shape-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/shape'),
        ], ['shape', 'shape-lang']);

        $this->publishes([
            __DIR__.'/../resources/css' => resource_path('css/vendor/shape'),
        ], ['shape', 'shape-css']);

        // Deliberately not part of the "shape" bundle: the icons Shape ships are
        // the ones its own components render, and a consumer publishing them
        // takes on keeping them current for no benefit. Publishing an icon is
        // what `shape:icon:add` is for.
        $this->publishes([
            __DIR__.'/../resources/icons' => $this->iconPath(),
        ], ['shape-icons']);

        $this->commands([
            AddIconCommand::class,
            IconCommand::class,
            ShapeCommand::class,
        ]);
    }

    /**
     * Register the directories published icons resolve through.
     *
     * Icons get their own tag prefix rather than living under "shape" so that
     * publishing one stays separate from publishing the component views.
     * Publishing a view means forking `button.blade.php` and giving up package
     * updates to it; publishing an icon is routine. Sharing a directory would
     * make the routine act quietly do the costly one.
     */
    private function registerIconNamespace(): void
    {
        $published = $this->iconPath();
        $packaged = __DIR__.'/../resources/icons';

        // The named view namespace is what the icon component includes through.
        $this->loadViewsFrom([$published, $packaged], 'shape-icons');

        // Application first: paths are searched in registration order, so an icon
        // a consumer publishes shadows one the package ships by filename alone.
        // That is the whole override mechanism -- no registry, no precedence
        // config, no merge step.
        //
        // `anonymousComponentPath` rather than `anonymousComponentNamespace`
        // because the latter builds a view name by appending to a directory,
        // which cannot address a namespace root: it would look for
        // "shape-icons::.lucide.check", stray dot and all. This registers a
        // directory under a tag prefix, which is exactly what an icon set is.
        Blade::anonymousComponentPath($published, self::ICON_PREFIX);
        Blade::anonymousComponentPath($packaged, self::ICON_PREFIX);
    }

    /**
     * Where published icons live in the application.
     */
    private function iconPath(): string
    {
        $path = config('shape.icons.path');

        return is_string($path) && $path !== ''
            ? $path
            : resource_path('views/vendor/shape-icons');
    }

    /**
     * Register Shape's Blade components and the branded <shape:*> tag syntax.
     *
     * Anonymous components live in resources/views/components and are exposed
     * under the "shape" namespace so that <x-shape::button /> resolves to them.
     * A compilation preprocessor rewrites the shorter <shape:button /> syntax
     * into that form before Blade compiles its component tags.
     */
    private function registerBladeComponents(): void
    {
        Blade::anonymousComponentNamespace('shape::components', self::TAG_PREFIX);

        Blade::prepareStringsForCompilationUsing(function (string $template): string {
            return preg_replace(
                '/<(\/?)'.preg_quote(self::TAG_PREFIX, '/').':(?=[\w-])/',
                '<${1}x-'.self::TAG_PREFIX.'::',
                $template,
            );
        });
    }
}
