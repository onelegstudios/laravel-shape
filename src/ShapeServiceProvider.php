<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;
use Illuminate\View\View as RenderedView;
use Livewire\Blaze\BlazeManager;
use Livewire\Blaze\Events\ComponentFolded;
use Onelegstudios\Shape\Console\Commands\AddIconCommand;
use Onelegstudios\Shape\Console\Commands\CheckIconCommand;
use Onelegstudios\Shape\Console\Commands\IconCommand;
use Onelegstudios\Shape\Console\Commands\InstallCommand;
use Onelegstudios\Shape\Console\Commands\RemoveIconCommand;
use Onelegstudios\Shape\Console\Commands\UpdateIconCommand;

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
     * The view namespace published icons resolve through, e.g.
     * `@include('shape-icons::lucide.art.check')`.
     *
     * A namespace as well as a tag prefix because the two address different
     * things: the prefix reaches a published icon as a component, and this
     * reaches its artwork as a view -- which is what `<shape:icon>` includes.
     */
    private const string ICON_NAMESPACE = 'shape-icons';

    /**
     * Whether the template being compiled has already been stamped with its
     * dependency on the config file. Reset once per compilation.
     */
    private bool $stamped = false;

    /**
     * Artwork files already stamped during the template being compiled, keyed by
     * path. Reset alongside the flag above, once per compilation.
     *
     * @var array<string, true>
     */
    private array $stampedArtwork = [];

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

        $this->registerConfigAsFoldDependency();

        $this->registerArtworkAsFoldDependency();

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
            CheckIconCommand::class,
            IconCommand::class,
            InstallCommand::class,
            RemoveIconCommand::class,
            UpdateIconCommand::class,
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
        $this->loadViewsFrom([$published, $packaged], self::ICON_NAMESPACE);

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
            // One compilation begins here, which is the only reason this callback
            // knows anything about folding. See registerConfigAsFoldDependency().
            $this->stamped = false;
            $this->stampedArtwork = [];

            return preg_replace(
                '/<(\/?)'.preg_quote(self::TAG_PREFIX, '/').':(?=[\w-])/',
                '<${1}x-'.self::TAG_PREFIX.'::',
                $template,
            );
        });
    }

    /**
     * Make the config file a dependency of every compiled view Shape folded into.
     *
     * Folding evaluates a component once, at compile time, so the defaults the
     * button reads from `config('shape')` are baked into the calling template. On
     * its own that would mean editing config/shape.php changed nothing until the
     * view cache was cleared by hand -- a silently stale page, which is the worst
     * shape a caching bug can take.
     *
     * Blaze already has the machinery to prevent it. A folded component writes a
     * `[BlazeFolded]` marker naming its own source file and that file's mtime into
     * the compiled view, and a view composer recompiles the moment any listed
     * file is newer than its marker. The marker is just a path, though, and nothing
     * says it has to be the component's: dispatching a second ComponentFolded event
     * naming the config file puts that on the list too, so a config edit invalidates
     * exactly the views that folded it. It is checked on every render, so it holds
     * under `view:cache` as well.
     *
     * Three things make this safe rather than merely clever:
     *
     * - A path that does not exist counts as expired, so stamping an unpublished
     *   config_path() would recompile every view on every render. Hence is_file().
     * - Blaze appends whatever is dispatched, including what this listener
     *   dispatches. The namespace check is what stops the stamp re-triggering
     *   itself, and it also keeps the stamp off components that are not Shape's.
     * - Both files are stamped. An application that has never published still
     *   takes its defaults from the packaged config, whose mtime moves on
     *   `composer update`.
     *
     * Once per compilation, not once per folded component: a page folding two
     * hundred icons would otherwise carry two hundred identical markers. The reset
     * lives in the tag-rewriting callback above, which is the same callback that
     * has to run for `<shape:button>` to resolve at all -- so a compilation that
     * somehow skipped it would fail loudly long before it could fold a stale
     * default quietly.
     */
    private function registerConfigAsFoldDependency(): void
    {
        Event::listen(function (ComponentFolded $event): void {
            if ($this->stamped || ! str_starts_with($event->name, self::TAG_PREFIX.'::')) {
                return;
            }

            $this->stamped = true;

            // Resolved and deduplicated, because the two candidates are two ways of
            // naming a file rather than two files: an application that keeps the
            // package inside its own tree, or symlinks a published config back at
            // it, would otherwise be stamped twice for one dependency.
            $paths = array_filter([config_path('shape.php'), __DIR__.'/../config/shape.php'], 'is_file');

            foreach (array_unique(array_map('realpath', $paths)) as $path) {
                Event::dispatch(new ComponentFolded(
                    name: self::TAG_PREFIX.'-config',
                    path: (string) $path,
                    filemtime: (int) filemtime((string) $path),
                ));
            }
        });
    }

    /**
     * Make a published icon's artwork a dependency of the views that fold it.
     *
     * `<shape:icon>` reaches artwork with `@include`, which is what makes a
     * dynamic icon cheap -- but an include is invisible to Blaze. A component tag
     * was not: folding one dispatched ComponentFolded, and the marker that wrote
     * into the compiled view is what made re-publishing an icon show up without
     * anything being cleared by hand. Swapping the dispatch for an include quietly
     * took that away, so a compiled page went on serving artwork that had since
     * been rewritten under it.
     *
     * This puts it back, and by the same route the config file takes: an include
     * renders a view, a view fires the composer below, and a composer that fires
     * while Blaze is folding is looking at a file the fold is about to bake. The
     * event it dispatches lands in the same front matter a folded component would
     * have written.
     *
     * Only while folding. Outside a fold the include is an ordinary runtime
     * render, there is nothing being baked, and a marker would be recording a
     * dependency that no compiled view has.
     */
    private function registerArtworkAsFoldDependency(): void
    {
        View::composer(self::ICON_NAMESPACE.'::*', function (RenderedView $view): void {
            if (! app(BlazeManager::class)->isFolding()) {
                return;
            }

            $path = $view->getPath();

            // Once per file per compilation. A page drawing one icon forty times
            // folds it forty times, and forty identical markers say nothing the
            // first one did not.
            if (isset($this->stampedArtwork[$path]) || ! is_file($path)) {
                return;
            }

            $this->stampedArtwork[$path] = true;

            Event::dispatch(new ComponentFolded(
                name: self::ICON_PREFIX.'-artwork',
                path: $path,
                filemtime: (int) filemtime($path),
            ));
        });
    }
}
