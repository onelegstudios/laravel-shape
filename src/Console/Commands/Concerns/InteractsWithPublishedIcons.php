<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;

/**
 * What the icon verbs that touch the published directory all need.
 *
 * Two of these encode something non-obvious -- which stdin counts as answerable,
 * and which compiled-view directories have to go -- and a second copy of either
 * is a copy that drifts. The verbs themselves stay separate; only the knowledge
 * they cannot afford to disagree on lives here.
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
