<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;

class ShapeCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'shape:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by Shape.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Shape placeholder command executed.');

        return self::SUCCESS;
    }
}
