<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use BladeUI\Icons\Exceptions\SvgNotFound;
use BladeUI\Icons\Factory as IconFactory;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Shape\Console\Commands\Concerns\InteractsWithPublishedIcons;
use Onelegstudios\Shape\Console\Commands\Concerns\WritesIconComponents;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

/**
 * Rewrite published icons from the set as it stands now.
 *
 * The third verb, and the reason the first one gets to keep refusing. Adding is
 * the routine command -- the one you re-run to pick up one more name -- and a
 * `--force` on it would put overwriting one keystroke away from a command you
 * run without thinking. A separate verb makes overwriting the thing you asked
 * for by name, which is the same bargain `shape:icon:remove` already makes.
 *
 * It also replaces a recipe that had a real cost: remove-then-add deleted the
 * file before anything had confirmed the new artwork resolved at all. This
 * rewrites in place, and an icon whose name the set no longer has is reported
 * and left exactly as it was.
 *
 * Names are resolved through config rather than read back out of the file. The
 * header a published icon carries is a comment, and a comment is documentation,
 * not state: resolving through `icons.aliases` and `icons.sets` as they stand
 * now is what makes this the verb that brings a published directory in line with
 * configuration. Repoint `close` from `x` to `x-mark` in config/shape.php, run
 * update, and that is the whole migration.
 *
 * Which leaves it sitting between its two neighbours: it addresses files the way
 * `remove` does and fills them the way `add` does. `add close` wrote
 * `close.blade.php` holding Lucide's `x`, so `update close` looks for that
 * filename with no alias applied, and then resolves `close` through the alias
 * table to decide what goes inside it.
 */
class UpdateIconCommand extends Command
{
    use InteractsWithPublishedIcons;
    use WritesIconComponents;

    /**
     * The command signature.
     *
     * `--all` means every icon published in the set, not every icon the set
     * contains -- the same as `shape:icon:remove` and the opposite of
     * `shape:icon:add`. The two are worth telling apart before you run one: this
     * sweeps what you have, adding sweeps what exists.
     */
    protected $signature = 'shape:icon:update
        {name?* : Published icon names to refresh against the installed set; omit them to pick interactively}
        {--set= : Which published set to refresh, defaulting to the configured default}
        {--all : Refresh every icon published in the set}
        {--force : Answer the --all confirmation, for runs with nobody to ask}
        {--no-clear : Leave compiled views in place, for scripting many updates before one clear}';

    /**
     * The command description.
     */
    protected $description = 'Update published icons against the currently installed set.';

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

        $path = $this->iconPath($config);

        /** @var array<int, string> $names */
        $names = (array) $this->argument('name');

        // Nothing named and nothing standing in for a name. `--all` counts as an
        // answer, and so does having nobody to ask.
        $asking = $names === [] && ! $this->option('all') && $this->canAsk();

        $published = $this->publishedSets($files, $path);

        if ($published === []) {
            $this->components->info('No icons are published.');

            return self::SUCCESS;
        }

        $set = $this->chosenPublishedSet(
            $published,
            $default,
            $asking,
            'Which set should these icons be updated in?',
        );

        $available = $this->publishedIconsIn($files, $path, $set);

        if ($available === []) {
            $this->components->warn("No icons are published in set [{$set}].");

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $stopped = $this->confirmUpdatingEverything($set, count($available));

            if ($stopped !== null) {
                return $stopped;
            }

            $names = $available;
        }

        if ($asking) {
            $names = $this->askForIcons($available);

            // Selecting nothing is how the prompt is meant to be left, so it is
            // not the same as naming nothing on the command line.
            if ($names === []) {
                $this->components->info('No icons updated.');

                return self::SUCCESS;
            }
        }

        if ($names === []) {
            $this->components->error('Name at least one icon, or pass --all.');

            return self::FAILURE;
        }

        // An unmapped set name is treated as a prefix as-is, the same bargain
        // adding makes. It earns its keep differently here: a set published under
        // a directory name that config no longer lists still resolves as its own
        // prefix, so the files can be refreshed after the set was dropped from
        // `icons.sets`. If the package is gone too, every icon comes back missing
        // and the summary says so -- which is the honest answer.
        $prefix = $sets[$set] ?? $set;

        $updated = 0;
        $unchanged = 0;
        $missing = 0;
        $skipped = 0;

        foreach ($names as $name) {
            // Checked against the published listing rather than the filesystem so
            // a name can only ever address a file these commands put there. The
            // check sits in the loop rather than at the argument boundary because
            // all three ways of arriving at a name pass through here.
            if (! in_array($name, $available, true)) {
                $this->components->twoColumnDetail($set.'/'.$name, '<fg=yellow>not published</>');

                $skipped++;

                continue;
            }

            $resolved = $aliases[$name] ?? $name;
            $icon = $prefix === '' ? $resolved : $prefix.'-'.$resolved;

            try {
                $contents = $icons->svg($icon)->contents();
            } catch (SvgNotFound) {
                // Adding fails outright here and should: its names were typed
                // seconds ago, so one that does not resolve is a typo worth
                // stopping for. These names came off the disk and were all valid
                // when they were published, so one that no longer resolves means
                // the set moved -- which is the circumstance this verb exists
                // for. Aborting a two-hundred-icon refresh over a renamed glyph
                // would fail hardest exactly where it is needed most.
                $this->components->twoColumnDetail(
                    $set.'/'.$name.' <fg=gray>('.$icon.')</>',
                    '<fg=yellow>missing from set</>',
                );

                $missing++;

                continue;
            }

            $target = $path.'/'.$set.'/'.$name.'.blade.php';

            $rendered = $this->component($contents, $icon, $set);

            $wrote = false;

            // Rewriting a file that already says the right thing would move its
            // mtime, and mtime is what every file watcher, every rsync and every
            // "what did this deploy touch?" reads. A run that changed nothing
            // should leave nothing looking changed.
            if ($files->get($target) !== $rendered) {
                $files->put($target, $rendered);

                $wrote = true;
            }

            if ($set === $default && $this->refreshForward($files, $path, $set, $name)) {
                $wrote = true;
            }

            // A hand-edited file lands here as `updated`, because the edit is
            // being overwritten. That is the reporting doing its job: `unchanged`
            // means the file already says what the set says, and a hand-edited
            // one does not.
            $this->components->twoColumnDetail(
                $set.'/'.$name,
                $wrote ? '<fg=green>updated</>' : '<fg=gray>unchanged</>',
            );

            $wrote ? $updated++ : $unchanged++;
        }

        if ($updated > 0 && ! $this->option('no-clear')) {
            $this->clearCompiledViews($files);
        }

        $this->newLine();

        $this->components->info("Updated {$updated} icon(s) in {$path}.");

        if ($unchanged > 0) {
            // Not a warning: it is the answer to a question, not a caution.
            $this->components->info("Left {$unchanged} icon(s) unchanged.");
        }

        if ($missing > 0) {
            $this->components->warn("{$missing} icon(s) are no longer in set [{$set}]. Remove them, or add them under their new names.");
        }

        if ($skipped > 0) {
            $this->components->warn("Left {$skipped} name(s) alone that were not published.");
        }

        return self::SUCCESS;
    }

    /**
     * The exit code a sweep of the whole set should stop with, or null to go on.
     *
     * A prompt nobody answers returns its default, which here is the difference
     * between rewriting everything and rewriting nothing -- so a run with no
     * terminal has to say `--force` out loud rather than be answered for.
     *
     * Deliberately a near-twin of the one `shape:icon:remove` carries rather than
     * a shared helper. The `--force` policy is shared, and it is shared through
     * `canAsk()`; what is left is three branches and two strings, and the strings
     * are the value. "Removed" and "hand edits lost" are different damage.
     */
    private function confirmUpdatingEverything(string $set, int $count): ?int
    {
        if ($this->option('force')) {
            return null;
        }

        if (! $this->canAsk()) {
            $this->components->error("Pass --force to update all {$count} published icon(s) in [{$set}] without confirming.");

            return self::FAILURE;
        }

        $confirmed = confirm(
            label: "Update all {$count} published icon(s) in set [{$set}]?",
            default: false,
            hint: 'Each file is rewritten from the installed set; hand edits are lost.',
        );

        if (! $confirmed) {
            $this->components->info('No icons updated.');

            return self::SUCCESS;
        }

        return null;
    }

    /**
     * Ask which of the published icons to update.
     *
     * One list rather than the repeated question `add` asks, for the same reason
     * `remove` shows one: a set holds two thousand names and can only be
     * searched, where what you have published is short enough to read.
     *
     * @param  array<int, string>  $available
     * @return array<int, string>
     */
    private function askForIcons(array $available): array
    {
        $chosen = multiselect(
            label: 'Which icons should be updated?',
            options: $available,
            hint: 'Select none to update nothing.',
        );

        return array_values(array_map(strval(...), $chosen));
    }

    /**
     * Bring the default-set forward for an icon in line, reporting whether it
     * had to be written.
     *
     * A missing one is written. The case that matters is not a hand-deleted file
     * but `icons.set` having moved onto this set since the icons were published:
     * those files never got forwards, because they were not the default when they
     * were written, and an icon in the default set without a forward is one
     * `<shape:icon name="check" />` cannot find.
     *
     * One naming another set is repointed, which is where this parts company with
     * `remove` -- and the asymmetry is safe in one direction only. Removing
     * cannot verify that dropping such a forward is harmless, because the set it
     * names may still be published and rendering happily. Updating can verify
     * that rewriting it is: the name came off the published listing and the
     * component beside it was confirmed microseconds ago. Without the repoint,
     * updating the set you have just made default would report every icon green
     * while the old library went on rendering.
     */
    private function refreshForward(Filesystem $files, string $path, string $set, string $name): bool
    {
        $target = $path.'/default/'.$name.'.blade.php';

        $rendered = $this->forward($set, $name);

        if ($files->exists($target) && $files->get($target) === $rendered) {
            return false;
        }

        $files->ensureDirectoryExists(dirname($target));
        $files->put($target, $rendered);

        return true;
    }
}
