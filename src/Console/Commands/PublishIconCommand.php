<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

/**
 * Copy icons out of an installed Blade Icons set and into the application as
 * Blade components.
 *
 * Shape resolves an icon name at publish time rather than on every render. That
 * is what lets the rendered SVG be a static template with nothing global in it:
 * no set lookup, no alias table, no file read. A static template is one Blaze can
 * fold away entirely, which an icon -- the component a dense page repeats most --
 * is the best candidate for.
 *
 * The cost is that a published icon stops tracking the set it came from. Re-run
 * the command after upgrading a set to pick up redrawn artwork.
 */
class PublishIconCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'shape:icon
        {name?* : Icon names to publish, resolved through the alias table}
        {--set= : Which configured set to take them from, defaulting to the configured default}
        {--all : Publish every icon the set contains}
        {--force : Overwrite icons that are already published}
        {--no-clear : Leave compiled views in place, for scripting many publishes before one clear}';

    /**
     * The command description.
     */
    protected $description = 'Publish an icon from an installed set into the application as a Blade component.';

    /**
     * Execute the console command.
     */
    public function handle(IconFactory $icons, Filesystem $files): int
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $sets = array_filter((array) ($config['sets'] ?? []), 'is_string');
        $aliases = array_filter((array) ($config['aliases'] ?? []), 'is_string');

        $default = is_string($config['set'] ?? null) ? $config['set'] : 'lucide';

        // An unmapped set name is treated as a prefix as-is, the same bargain the
        // icon component makes: an ad-hoc prefix works untouched, and a genuine
        // typo fails naming the prefix that was tried rather than quietly
        // answering with the default set's artwork.
        $set = is_string($this->option('set')) && $this->option('set') !== ''
            ? $this->option('set')
            : $default;

        $prefix = $sets[$set] ?? $set;

        $path = is_string($config['path'] ?? null) && $config['path'] !== ''
            ? $config['path']
            : resource_path('views/vendor/shape-icons');

        /** @var array<int, string> $names */
        $names = (array) $this->argument('name');

        if ($this->option('all')) {
            $names = $this->allIconsIn($icons, $prefix);

            if ($names === []) {
                $this->components->error("No icons found for set [{$set}] (prefix \"{$prefix}\").");

                return self::FAILURE;
            }
        }

        if ($names === []) {
            $this->components->error('Name at least one icon, or pass --all.');

            return self::FAILURE;
        }

        $published = 0;
        $skipped = 0;

        foreach ($names as $name) {
            // Aliases resolve before the prefix is applied, so the published file
            // is named for what Shape's views ask for rather than what the set
            // happens to call it. `close.blade.php` holding Lucide's `x` is the
            // point: swap the library, re-publish, and no call site moves.
            $resolved = $aliases[$name] ?? $name;
            $icon = $prefix === '' ? $resolved : $prefix.'-'.$resolved;

            try {
                $contents = $icons->svg($icon)->contents();
            } catch (SvgNotFound $exception) {
                $this->components->error($exception->getMessage());

                return self::FAILURE;
            }

            $target = $path.'/'.$set.'/'.$name.'.blade.php';

            if ($files->exists($target) && ! $this->option('force')) {
                $this->components->twoColumnDetail($set.'/'.$name, '<fg=yellow>already published</>');

                $skipped++;

                continue;
            }

            $files->ensureDirectoryExists(dirname($target));
            $files->put($target, $this->component($contents, $icon, $set));

            // The default set gets a second, one-line component that forwards to
            // the real one. That is what lets <shape:icon name="check" /> resolve
            // without reading config for the default set name -- a config read
            // would put global state back into the component and cost the fold.
            // The forward is itself folded away, so it is free at runtime.
            if ($set === $default) {
                $forward = $path.'/default/'.$name.'.blade.php';

                $files->ensureDirectoryExists(dirname($forward));
                $files->put($forward, $this->forward($set, $name));
            }

            $this->components->twoColumnDetail($set.'/'.$name, '<fg=green>published</>');

            $published++;
        }

        if ($published > 0 && ! $this->option('no-clear')) {
            $this->clearCompiledViews($files);
        }

        $this->newLine();
        $this->components->info("Published {$published} icon(s) to {$path}".($skipped > 0 ? ", skipped {$skipped}." : '.'));

        return self::SUCCESS;
    }

    /**
     * Drop compiled views so a re-published icon is actually seen.
     *
     * Folding copies an icon's markup into every compiled view that renders it,
     * and editing the published file does not invalidate those -- verified: a
     * re-published icon keeps serving the old artwork across fresh processes
     * until the compiled views are gone.
     *
     * This does the work inline rather than calling `view:clear` so it can also
     * take Blaze's own directory, which that command leaves behind: it globs one
     * level and Blaze renders folded components through a nested cache of its
     * own. Leaving that in place is enough to keep serving the stale icon.
     */
    private function clearCompiledViews(Filesystem $files): void
    {
        $compiled = config('view.compiled');

        if (! is_string($compiled) || $compiled === '') {
            return;
        }

        foreach ($files->glob($compiled.'/*.php') ?: [] as $view) {
            $files->delete($view);
        }

        $files->deleteDirectory($compiled.'/blaze');
    }

    /**
     * Wrap raw SVG markup in a component that takes the caller's attributes.
     */
    private function component(string $contents, string $icon, string $set): string
    {
        // `shrink-0` and nothing else. An icon is a fixed glyph beside text that
        // wraps, and flex will happily squash it into an ellipse to make room --
        // but accessibility is left to the caller, because `merge` can only add
        // an attribute. An icon that hid itself here could never be unhidden by a
        // <shape:icon label="..."> above it.
        $merge = "{{ \$attributes->merge(['class' => 'shrink-0']) }}";

        $svg = Str::replaceFirst('<svg', '<svg '.$merge, $contents);

        return <<<BLADE
            @blaze(fold: true)

            {{-- {$icon} -- published from set "{$set}" by `php artisan shape:icon`.
                 Re-run with --force to pick up redrawn artwork after a set upgrade. --}}

            {$svg}

            BLADE;
    }

    /**
     * Build the default-set component that forwards to the real one.
     */
    private function forward(string $set, string $name): string
    {
        return <<<BLADE
            {{-- Forwards to the configured default set. Regenerated by `php artisan shape:icon`. --}}

            <x-shape-icon::{$set}.{$name} {{ \$attributes }} />

            BLADE;
    }

    /**
     * Every icon name an installed set exposes under the given Shape prefix.
     *
     * Blade Icons registers each set with its own prefix and resolves a dotted
     * name to a nested path, so the names are recovered from the files rather
     * than asked for: a set that keeps its weights in filenames (`heroicon-o`)
     * and one that keeps them in directories both come out right.
     *
     * @return array<int, string>
     */
    private function allIconsIn(IconFactory $icons, string $prefix): array
    {
        $names = [];

        foreach ($icons->all() as $options) {
            $setPrefix = is_string($options['prefix'] ?? null) ? $options['prefix'] : '';

            foreach ((array) ($options['paths'] ?? []) as $path) {
                if (! is_string($path) || ! is_dir($path)) {
                    continue;
                }

                $files = glob(rtrim($path, '/').'/{,*/,*/*/}*.svg', GLOB_BRACE) ?: [];

                foreach ($files as $file) {
                    $relative = Str::of($file)
                        ->after(rtrim($path, '/').'/')
                        ->beforeLast('.svg')
                        ->replace('/', '.')
                        ->toString();

                    $full = $setPrefix === '' ? $relative : $setPrefix.'-'.$relative;

                    if ($prefix === '') {
                        $names[] = $full;

                        continue;
                    }

                    if (str_starts_with($full, $prefix.'-')) {
                        $names[] = Str::after($full, $prefix.'-');
                    }
                }
            }
        }

        sort($names);

        return array_values(array_unique($names));
    }
}
