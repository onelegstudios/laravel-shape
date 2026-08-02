<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

use function Laravel\Prompts\select;
use function Laravel\Prompts\suggest;

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
 * Adding never overwrites. A published icon is a file in the application that
 * someone may have hand-tuned, and the command that puts icons there is the wrong
 * place to take them away again: an already-published name is reported and left
 * alone. Refreshing one against an upgraded set is a separate verb.
 *
 * Naming nothing at all asks instead of failing. That is the one case where the
 * command can be sure it has not been scripted, so it picks up the set and the
 * names through prompts -- which is also the only way to get a list of what a set
 * actually holds without publishing all two thousand of them.
 */
class AddIconCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'shape:icon:add
        {name?* : Icon names to publish, resolved through the alias table; omit them to pick interactively}
        {--set= : Which configured set to take them from, defaulting to the configured default}
        {--all : Publish every icon the set contains}
        {--no-clear : Leave compiled views in place, for scripting many publishes before one clear}';

    /**
     * The command description.
     */
    protected $description = 'Add icons from an installed set to the application as Blade components.';

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

        /** @var array<int, string> $names */
        $names = (array) $this->argument('name');

        // Nothing named and nothing standing in for a name. `--all` counts as an
        // answer, and so does having nobody to ask.
        $asking = $names === [] && ! $this->option('all') && $this->canAsk();

        $set = $this->chosenSet($sets, $default, $asking);

        $prefix = $sets[$set] ?? $set;

        $path = is_string($config['path'] ?? null) && $config['path'] !== ''
            ? $config['path']
            : resource_path('views/vendor/shape-icons');

        if ($this->option('all')) {
            $names = $this->allIconsIn($icons, $prefix);
        }

        if ($asking) {
            $available = $this->allIconsIn($icons, $prefix);

            if ($available === []) {
                $this->components->error("No icons found for set [{$set}] (prefix \"{$prefix}\").");

                return self::FAILURE;
            }

            $names = $this->askForIcons($icons, $aliases, $prefix, $available);

            // Leaving the prompt empty is how the loop is meant to end, so it is
            // not the same as naming nothing on the command line.
            if ($names === []) {
                $this->components->info('No icons added.');

                return self::SUCCESS;
            }
        }

        if ($names === []) {
            $this->option('all')
                ? $this->components->error("No icons found for set [{$set}] (prefix \"{$prefix}\").")
                : $this->components->error('Name at least one icon, or pass --all.');

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

            if ($files->exists($target)) {
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

        $this->components->info("Published {$published} icon(s) to {$path}.");

        if ($skipped > 0) {
            $this->components->warn("Left {$skipped} already-published icon(s) untouched.");
        }

        return self::SUCCESS;
    }

    /**
     * Whether there is someone on the other end to answer a prompt.
     *
     * The same test Laravel applies before making prompts live, repeated here
     * because the two answers have to agree: a prompt that quietly returns its
     * default would turn a scripted run that used to fail loudly into one that
     * adds nothing and reports success. --no-interaction and a redirected stdin
     * both land back on the error.
     */
    private function canAsk(): bool
    {
        if (! $this->input->isInteractive()) {
            return false;
        }

        // Only the terminal half is waived under test, where there is a mock on
        // the other end and never a tty.
        return $this->laravel->runningUnitTests()
            || (defined('STDIN') && stream_isatty(STDIN));
    }

    /**
     * Which set the icons should come from.
     *
     * An unmapped set name is treated as a prefix as-is, the same bargain the
     * icon component makes: an ad-hoc prefix works untouched, and a genuine typo
     * fails naming the prefix that was tried rather than quietly answering with
     * the default set's artwork.
     *
     * @param  array<array-key, string>  $sets
     */
    private function chosenSet(array $sets, string $default, bool $asking): string
    {
        $option = $this->option('set');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        // One configured set is not a choice. A question with a single answer
        // costs a keystroke and teaches nothing, so it is only worth asking once
        // there is somewhere else the icons could have come from.
        if (! $asking || count($sets) < 2) {
            return $default;
        }

        $options = [];

        foreach ($sets as $name => $prefix) {
            // The prefix is shown where it differs, because the two names come
            // apart in exactly the case the question exists for: `outline` and
            // `solid` are both `heroicon`, told apart only by their prefixes.
            $options[(string) $name] = $name === $prefix ? "{$name}" : "{$name} ({$prefix})";
        }

        return (string) select(
            label: 'Which set should these icons come from?',
            options: $options,
            default: array_key_exists($default, $options) ? $default : array_key_first($options),
            hint: 'Configured in config/shape.php under icons.sets.',
        );
    }

    /**
     * Collect icon names one at a time until an empty answer ends the loop.
     *
     * @param  array<array-key, string>  $aliases
     * @param  array<int, string>  $available
     * @return array<int, string>
     */
    private function askForIcons(IconFactory $icons, array $aliases, string $prefix, array $available): array
    {
        // Shape's semantic names are offered alongside the set's own because
        // both are real answers here: `close` publishes Lucide's `x` under the
        // name views ask for, and it is the name worth being reminded of.
        $suggestions = array_values(array_unique([
            ...array_map(strval(...), array_keys($aliases)),
            ...$available,
        ]));

        sort($suggestions);

        $names = [];

        while (true) {
            $answer = suggest(
                label: 'Which icon?',
                options: $suggestions,
                placeholder: 'Start typing, or press enter to finish',
                // Checked here rather than at publish time so a typo costs one
                // answer instead of the whole session: the loop below cannot
                // fail once a name is in it.
                validate: fn (string $value): ?string => $this->unknownIcon($icons, $aliases, $prefix, $value),
                hint: $names === []
                    ? 'Leave empty to finish.'
                    : count($names).' queued. Leave empty to finish.',
            );

            $answer = trim($answer);

            if ($answer === '') {
                return $names;
            }

            if (! in_array($answer, $names, true)) {
                $names[] = $answer;
            }
        }
    }

    /**
     * The reason an answer cannot be published, or null if it can.
     *
     * @param  array<array-key, string>  $aliases
     */
    private function unknownIcon(IconFactory $icons, array $aliases, string $prefix, string $value): ?string
    {
        $value = trim($value);

        // Empty is how the loop is left, so it cannot be an error.
        if ($value === '') {
            return null;
        }

        $resolved = $aliases[$value] ?? $value;
        $icon = $prefix === '' ? $resolved : $prefix.'-'.$resolved;

        try {
            $icons->svg($icon);
        } catch (SvgNotFound) {
            return "No icon named \"{$icon}\" in this set.";
        }

        return null;
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

            {{-- {$icon} -- published from set "{$set}" by `php artisan shape:icon:add`.
                 Edits survive: publishing again reports this file and leaves it as it is. --}}

            {$svg}

            BLADE;
    }

    /**
     * Build the default-set component that forwards to the real one.
     */
    private function forward(string $set, string $name): string
    {
        return <<<BLADE
            {{-- Forwards to the configured default set. Written by `php artisan shape:icon:add`. --}}

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
