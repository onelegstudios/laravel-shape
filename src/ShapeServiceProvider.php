<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\Console\Commands\ShapeCommand;

class ShapeServiceProvider extends ServiceProvider
{
    /**
     * The tag prefix used to reference Shape components, e.g. <shape:button />.
     */
    private const string TAG_PREFIX = 'shape';

    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(__DIR__.'/../config/laravel-shape.php', 'laravel-shape');

        $this->app->singleton(Shape::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        $this->loadViewsFrom(__DIR__.'/../resources/views', 'laravel-shape');

        $this->loadTranslationsFrom(__DIR__.'/../lang', 'laravel-shape');

        $this->registerBladeComponents();

        if (! $this->app->runningInConsole()) {
            return;
        }

        $this->publishes([
            __DIR__.'/../config/laravel-shape.php' => config_path('laravel-shape.php'),
        ], ['laravel-shape', 'laravel-shape-config']);

        $this->publishes([
            __DIR__.'/../resources/views' => resource_path('views/vendor/laravel-shape'),
        ], ['laravel-shape', 'laravel-shape-views']);

        $this->publishes([
            __DIR__.'/../lang' => $this->app->langPath('vendor/laravel-shape'),
        ], ['laravel-shape', 'laravel-shape-lang']);

        $this->commands([
            ShapeCommand::class,
        ]);
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
        Blade::anonymousComponentNamespace('laravel-shape::components', self::TAG_PREFIX);

        Blade::prepareStringsForCompilationUsing(function (string $template): string {
            return preg_replace(
                '/<(\/?)'.preg_quote(self::TAG_PREFIX, '/').':(?=[\w-])/',
                '<${1}x-'.self::TAG_PREFIX.'::',
                $template,
            );
        });
    }
}
