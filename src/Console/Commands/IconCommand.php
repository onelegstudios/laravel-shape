<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;

/**
 * List the icon commands Shape provides.
 *
 * `shape:icon` is an index rather than a verb of its own. Icon names and verb
 * names share one vocabulary -- `check`, `plus`, `x` are all icons somewhere --
 * so a positional action argument would make `shape:icon check` mean two things
 * depending on what happened to be published. Each verb gets its own command
 * name instead, and the bare name says which ones exist.
 */
class IconCommand extends Command
{
    /**
     * The namespace the icon verbs are registered under.
     */
    private const string NAMESPACE = 'shape:icon';

    /**
     * The command signature.
     */
    protected $signature = 'shape:icon';

    /**
     * The command description.
     */
    protected $description = 'List the icon commands Shape provides.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        // Read back off the console application rather than keeping a list here,
        // so a verb added later shows up by being registered and nothing has to
        // remember to name it twice.
        $commands = [];

        foreach ($this->getApplication()?->all() ?? [] as $command) {
            $name = (string) $command->getName();

            if (! str_starts_with($name, self::NAMESPACE.':')) {
                continue;
            }

            $commands[$name] = $command->getDescription();
        }

        if ($commands === []) {
            $this->components->warn('No icon commands are registered.');

            return self::SUCCESS;
        }

        ksort($commands);

        $this->newLine();
        $this->components->twoColumnDetail('<fg=gray>Command</>', '<fg=gray>Description</>');

        foreach ($commands as $name => $description) {
            $this->components->twoColumnDetail("<fg=green>{$name}</>", $description);
        }

        $this->newLine();
        $this->components->info('Run any of them with --help for the options they take.');

        return self::SUCCESS;
    }
}
