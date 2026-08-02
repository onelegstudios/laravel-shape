<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

use function Laravel\Prompts\select;

/**
 * The facts about the published directory that the icon verbs share.
 *
 * Where it is, what is in it, which set is being addressed, whether there is
 * anyone to ask, and how to invalidate what was compiled from it. Each of those
 * encodes something non-obvious -- which stdin counts as answerable, which
 * compiled-view directories have to go, why `default/` is not a set -- and a
 * second copy of any of them is a copy that drifts.
 *
 * The listing methods carry more weight than that, though: `publishedIconsIn()`
 * is where a name earns the right to become a path. Both `remove` and `update`
 * check a name against that listing before building one, so a second
 * implementation would be a second copy of the traversal guard. And the set
 * choice has to land in the same directory for both, or `shape:icon:update check`
 * and `shape:icon:remove check` would address different files in one working
 * copy.
 *
 * The verbs themselves stay separate. Nothing that writes a file lives here (see
 * WritesIconComponents), and neither do the prompts that ask which icons -- the
 * list is shared, but "removed" and "rewritten" are different news.
 *
 * @phpstan-require-extends Command
 */
trait InteractsWithPublishedIcons
{
    /**
     * Where published icons live in the application.
     *
     * Resolved on use rather than taken from config as written, because a
     * package config file is merged before the application has finished booting
     * -- naming a path that early is how you get one built from the wrong base.
     *
     * @param  array<string, mixed>  $config
     */
    private function iconPath(array $config): string
    {
        $path = $config['path'] ?? null;

        return is_string($path) && $path !== ''
            ? $path
            : resource_path('views/vendor/shape-icons');
    }

    /**
     * Whether there is someone on the other end to answer a prompt.
     *
     * The same test Laravel applies before making prompts live, repeated here
     * because the two answers have to agree: a prompt that quietly returns its
     * default would turn a scripted run that used to fail loudly into one that
     * does nothing and reports success. --no-interaction and a redirected stdin
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
     * Which published set a verb should act on.
     *
     * A set is offered because it has files in it, not because it is configured:
     * the case this exists for is the library you have just uninstalled, whose
     * artwork is still sitting in `resources/views`. `--set` is still passed
     * through as given, so a directory nothing knows about is reachable too.
     *
     * The label is the caller's because the two verbs are asking about different
     * fates for the same files. The hint is not: it is a fact about the list.
     *
     * Named apart from `AddIconCommand::chosenSet()` on purpose. That one picks
     * from what is configured and this one from what is on disk, and a shared
     * name would invite a reader to assume they are interchangeable.
     *
     * @param  array<int, string>  $sets
     */
    private function chosenPublishedSet(array $sets, string $default, bool $asking, string $label): string
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
            label: $label,
            options: array_combine($sets, $sets),
            default: in_array($default, $sets, true) ? $default : $sets[0],
            hint: 'Only sets with published icons are listed.',
        );
    }

    /**
     * Every set with icons published in it.
     *
     * `default/` is not one: it holds forwards written alongside the real files
     * and removed alongside them, so offering it would mean offering to break
     * every `<shape:icon name="...">` while leaving the artwork behind -- or, for
     * a verb that rewrites, rewriting one-line forwards as though they were SVGs.
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
     * This is the list a name has to appear in before anything builds a path out
     * of it. Reading the directory is what makes that check total: a name that is
     * in the list is by construction a file these commands wrote, and a name that
     * is not never reaches string concatenation.
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
     * Drop compiled views so a change to a published icon is actually seen.
     *
     * Folding copies an icon's markup into every compiled view that renders it,
     * and touching the published file does not invalidate those -- verified: a
     * re-published icon keeps serving the old artwork across fresh processes
     * until the compiled views are gone. A removed one keeps rendering entirely.
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
}
