<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Shape\Console\Commands\Concerns\InteractsWithPublishedIcons;
use Onelegstudios\Shape\Icons\Libraries;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;

/**
 * Take published icons back out of the application.
 *
 * The other half of `shape:icon:add`, and the reason adding never overwrites:
 * with a verb that removes, the command that publishes has no business deleting.
 * Refreshing an icon against an upgraded set is neither of these -- that is
 * `shape:icon:update`, which rewrites in place rather than deleting artwork
 * before anything has confirmed the replacement resolves.
 *
 * Everything here reads the published directory rather than the configured sets,
 * which is the one place this verb has to disagree with its counterpart. Adding
 * asks a set what it holds; removing can only ever act on what is already on
 * disk, and that stays true after the set is uninstalled or dropped from config
 * -- which is exactly when you want the files gone. Names are not resolved
 * through the alias table for the same reason: `add close` writes
 * `close.blade.php`, so `remove close` has to look for that and not for
 * Lucide's `x`.
 *
 * Named icons are removed without ceremony -- naming a file is as explicit as an
 * instruction gets. `--all` is the sweeping one, so it asks first, and a scripted
 * run has to say `--force` rather than have a prompt quietly answer itself.
 *
 * The exception is the handful of names Shape's own components render, which
 * `Libraries::required()` lists. Those are not published because somebody asked
 * for them -- `shape:install` puts them there so the shipped components have
 * artwork -- so removing one does not leave the application short of an icon it
 * chose, it leaves a button rendering nothing mid-submit. They are held back from
 * every route into this command rather than one: not swept by `--all`, not
 * offered by the prompt, and refused when named outright. `--force` is the way
 * past all three, because an application that has stopped using the button, or
 * that renders its own spinner, is owed a way to say so.
 */
class RemoveIconCommand extends Command
{
    use InteractsWithPublishedIcons;

    /**
     * The command signature.
     */
    protected $signature = 'shape:icon:remove
        {name?* : Published icon names to remove; omit them to pick interactively}
        {--set= : Which published set to remove them from, defaulting to the configured default}
        {--all : Remove every icon published in the set}
        {--force : Remove the icons the shipped components render, and answer the --all confirmation}
        {--no-clear : Leave compiled views in place, for scripting many removals before one clear}';

    /**
     * The command description.
     */
    protected $description = 'Remove published icons from the application.';

    /**
     * Execute the console command.
     */
    public function handle(Filesystem $files): int
    {
        /** @var array<string, mixed> $config */
        $config = (array) config('shape.icons');

        $path = $this->iconPath($config);

        $default = is_string($config['set'] ?? null) ? $config['set'] : 'lucide';

        /** @var array<int, string> $names */
        $names = (array) $this->argument('name');

        // Nothing named and nothing standing in for a name. `--all` counts as an
        // answer, and so does having nobody to ask.
        $asking = $names === [] && ! $this->option('all') && $this->canAsk();

        $sets = $this->publishedSets($files, $path);

        if ($sets === []) {
            $this->components->info('No icons are published.');

            return self::SUCCESS;
        }

        $set = $this->chosenPublishedSet(
            $sets,
            $default,
            $asking,
            'Which set should these icons be removed from?',
        );

        $available = $this->publishedIconsIn($files, $path, $set);

        if ($available === []) {
            $this->components->warn("No icons are published in set [{$set}].");

            return self::SUCCESS;
        }

        // What is published minus what Shape's own components render, which is
        // the list both the sweep and the prompt work from. Empty under --force,
        // so every guard below turns itself off by having nothing to hold back.
        $required = $this->option('force')
            ? []
            : array_values(array_intersect($available, Libraries::required()));

        $removable = array_values(array_diff($available, $required));

        if ($removable === [] && ($this->option('all') || $asking)) {
            $this->components->info("Only icons Shape's own components render are published in [{$set}].");
            $this->line('  <fg=gray>Pass --force to remove those too.</>');

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $stopped = $this->confirmRemovingEverything($set, count($removable), count($required));

            if ($stopped !== null) {
                return $stopped;
            }

            $names = $removable;
        }

        if ($asking) {
            $names = $this->askForIcons($removable, $required);

            // Selecting nothing is how the prompt is meant to be left, so it is
            // not the same as naming nothing on the command line.
            if ($names === []) {
                $this->components->info('No icons removed.');

                return self::SUCCESS;
            }
        }

        if ($names === []) {
            $this->components->error('Name at least one icon, or pass --all.');

            return self::FAILURE;
        }

        $removed = 0;
        $skipped = 0;
        $kept = 0;

        foreach ($names as $name) {
            // Only ever a name typed on the command line: the sweep and the
            // prompt were handed a list these were already out of. Reported
            // rather than silently dropped for that reason -- an instruction
            // this command declined to carry out is news.
            if (in_array($name, $required, true)) {
                $this->components->twoColumnDetail($set.'/'.$name, '<fg=yellow>kept</>');

                $kept++;

                continue;
            }

            // Checked against the published listing rather than the filesystem
            // so a name can only ever address a file this command put there. It
            // is also what keeps a name out of the path it is about to build.
            if (! in_array($name, $available, true)) {
                $this->components->twoColumnDetail($set.'/'.$name, '<fg=yellow>not published</>');

                $skipped++;

                continue;
            }

            $files->delete($path.'/'.$set.'/'.$name.'.blade.php');

            $this->removeForward($files, $path, $set, $name);

            $this->components->twoColumnDetail($set.'/'.$name, '<fg=green>removed</>');

            $removed++;
        }

        if ($removed > 0) {
            $this->pruneEmptyDirectory($files, $path.'/'.$set);
            $this->pruneEmptyDirectory($files, $path.'/default');

            if (! $this->option('no-clear')) {
                $this->clearCompiledViews($files);
            }
        }

        $this->newLine();

        $this->components->info("Removed {$removed} icon(s) from {$path}.");

        if ($skipped > 0) {
            $this->components->warn("Left {$skipped} name(s) alone that were not published.");
        }

        if ($kept > 0) {
            $this->components->warn("Kept {$kept} icon(s) Shape's own components render. Pass --force to remove them.");

            // A failure even when other names went, because the run did less
            // than it was told to. A script that asked for the spinner and got
            // an exit code of nought would have no way to find that out.
            return self::FAILURE;
        }

        return self::SUCCESS;
    }

    /**
     * The exit code a sweep of the whole set should stop with, or null to go on.
     *
     * A prompt nobody answers returns its default, which here is the difference
     * between removing everything and removing nothing -- so a run with no
     * terminal has to say `--force` out loud rather than be answered for.
     */
    private function confirmRemovingEverything(string $set, int $count, int $kept): ?int
    {
        if ($this->option('force')) {
            return null;
        }

        if (! $this->canAsk()) {
            $this->components->error("Pass --force to remove all {$count} published icon(s) in [{$set}] without confirming.");

            return self::FAILURE;
        }

        $confirmed = confirm(
            label: "Remove all {$count} published icon(s) from set [{$set}]?",
            default: false,
            // The count is already the sweep with Shape's own icons taken out,
            // so the hint says which ones are staying rather than leaving "all"
            // to mean something the next line quietly disagrees with.
            hint: $kept > 0
                ? "Keeps {$kept} icon(s) Shape's own components render; they can be published again with `php artisan shape:icon:add`."
                : 'They can be published again with `php artisan shape:icon:add`.',
        );

        if (! $confirmed) {
            $this->components->info('No icons removed.');

            return self::SUCCESS;
        }

        return null;
    }

    /**
     * Ask which of the published icons to remove.
     *
     * One list rather than the repeated question `add` asks, because the two are
     * choosing from different things: a set holds two thousand names and can
     * only be searched, where what you have published is short enough to read
     * and check off.
     *
     * Shape's own icons are left off the list rather than shown and refused. A
     * name you cannot pick is the clearer statement of the two, and the hint says
     * where they went -- an option that rejects the selection afterwards teaches
     * the same rule at the cost of a wasted answer.
     *
     * @param  array<int, string>  $available
     * @param  array<int, string>  $required
     * @return array<int, string>
     */
    private function askForIcons(array $available, array $required): array
    {
        $chosen = multiselect(
            label: 'Which icons should be removed?',
            options: $available,
            hint: $required === []
                ? 'Select none to remove nothing.'
                : 'Select none to remove nothing. '.implode(', ', $required).' left out; --force lists them.',
        );

        return array_values(array_map(strval(...), $chosen));
    }

    /**
     * Drop the default-set forward that points at the icon being removed.
     *
     * Only the forward that names this set: `icons.set` can have changed since
     * the icon was published, and a forward pointing somewhere else belongs to
     * the set it names and goes when that one does.
     */
    private function removeForward(Filesystem $files, string $path, string $set, string $name): void
    {
        $forward = $path.'/default/'.$name.'.blade.php';

        if (! $files->exists($forward)) {
            return;
        }

        if (! str_contains($files->get($forward), "x-shape-icon::{$set}.{$name}")) {
            return;
        }

        $files->delete($forward);
    }

    /**
     * Take a set directory away once the last icon in it is gone.
     *
     * Which set is published is read off these directories, so one left behind
     * empty would go on being offered as somewhere to remove icons from.
     */
    private function pruneEmptyDirectory(Filesystem $files, string $directory): void
    {
        if (! $files->isDirectory($directory)) {
            return;
        }

        if ($files->files($directory) !== [] || $files->directories($directory) !== []) {
            return;
        }

        $files->deleteDirectory($directory);
    }
}
