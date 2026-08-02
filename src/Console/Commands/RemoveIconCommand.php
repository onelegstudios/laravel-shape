<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Onelegstudios\Shape\Console\Commands\Concerns\InteractsWithPublishedIcons;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\multiselect;
use function Laravel\Prompts\select;

/**
 * Take published icons back out of the application.
 *
 * The other half of `shape:icon:add`, and the reason adding never overwrites:
 * with a verb that removes, the command that publishes has no business deleting.
 * Refreshing an icon against an upgraded set is these two in order.
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
        {--force : Answer the --all confirmation, for runs with nobody to ask}
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

        $set = $this->chosenSet($sets, $default, $asking);

        $available = $this->publishedIconsIn($files, $path, $set);

        if ($available === []) {
            $this->components->warn("No icons are published in set [{$set}].");

            return self::SUCCESS;
        }

        if ($this->option('all')) {
            $stopped = $this->confirmRemovingEverything($set, count($available));

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

        foreach ($names as $name) {
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

        return self::SUCCESS;
    }

    /**
     * The exit code a sweep of the whole set should stop with, or null to go on.
     *
     * A prompt nobody answers returns its default, which here is the difference
     * between removing everything and removing nothing -- so a run with no
     * terminal has to say `--force` out loud rather than be answered for.
     */
    private function confirmRemovingEverything(string $set, int $count): ?int
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
            hint: 'They can be published again with `php artisan shape:icon:add`.',
        );

        if (! $confirmed) {
            $this->components->info('No icons removed.');

            return self::SUCCESS;
        }

        return null;
    }

    /**
     * Which set the icons should be removed from.
     *
     * A set is offered because it has files in it, not because it is configured:
     * the case this exists for is the library you have just uninstalled, whose
     * artwork is still sitting in `resources/views`. `--set` is still passed
     * through as given, so a directory nothing knows about is reachable too.
     *
     * @param  array<int, string>  $sets
     */
    private function chosenSet(array $sets, string $default, bool $asking): string
    {
        $option = $this->option('set');

        if (is_string($option) && $option !== '') {
            return $option;
        }

        // One published set is not a choice, and it is the right answer even
        // when it is not the configured default -- there is nowhere else the
        // icons could be.
        if (count($sets) === 1) {
            return $sets[0];
        }

        if (! $asking) {
            return $default;
        }

        return (string) select(
            label: 'Which set should these icons be removed from?',
            options: array_combine($sets, $sets),
            default: in_array($default, $sets, true) ? $default : $sets[0],
            hint: 'Only sets with published icons are listed.',
        );
    }

    /**
     * Ask which of the published icons to remove.
     *
     * One list rather than the repeated question `add` asks, because the two are
     * choosing from different things: a set holds two thousand names and can
     * only be searched, where what you have published is short enough to read
     * and check off.
     *
     * @param  array<int, string>  $available
     * @return array<int, string>
     */
    private function askForIcons(array $available): array
    {
        $chosen = multiselect(
            label: 'Which icons should be removed?',
            options: $available,
            hint: 'Select none to remove nothing.',
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
     * Every set with icons published in it.
     *
     * `default/` is not one: it holds forwards written alongside the real files
     * and removed alongside them, so offering it would mean offering to break
     * every `<shape:icon name="...">` while leaving the artwork behind.
     *
     * @return array<int, string>
     */
    private function publishedSets(Filesystem $files, string $path): array
    {
        if (! $files->isDirectory($path)) {
            return [];
        }

        $sets = [];

        foreach ($files->directories($path) as $directory) {
            $name = basename($directory);

            if ($name === 'default') {
                continue;
            }

            $sets[] = $name;
        }

        sort($sets);

        return $sets;
    }

    /**
     * The icon names published under a set, as the files are actually named.
     *
     * @return array<int, string>
     */
    private function publishedIconsIn(Filesystem $files, string $path, string $set): array
    {
        $directory = $path.'/'.$set;

        if (! $files->isDirectory($directory)) {
            return [];
        }

        $names = [];

        foreach ($files->files($directory) as $file) {
            $name = $file->getFilename();

            if (! str_ends_with($name, '.blade.php')) {
                continue;
            }

            $names[] = substr($name, 0, -strlen('.blade.php'));
        }

        sort($names);

        return $names;
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
