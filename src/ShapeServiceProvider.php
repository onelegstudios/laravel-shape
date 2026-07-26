<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Support\ServiceProvider;
use Onelegstudios\Shape\Console\Commands\ShapeCommand;

class ShapeServiceProvider extends ServiceProvider
{
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
}
