<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Illuminate\Console\Command;

/**
 * Whether a command is allowed to ask a question.
 *
 * Every Shape command that prompts has the same two states to tell apart: a
 * person at a terminal, and a script. The difference decides what a missing
 * answer means -- a question for the first, and an error for the second -- so it
 * has to be one test rather than one per command.
 *
 * It lives apart from InteractsWithPublishedIcons because it is not a fact about
 * the published directory: `shape:install` needs it and never touches an icon
 * file. The icon verbs still reach it through that trait, which uses this one.
 *
 * @phpstan-require-extends Command
 */
trait AsksWhenAnswerable
{
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
}
