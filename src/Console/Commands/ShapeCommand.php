<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands;

use Illuminate\Console\Command;

class ShapeCommand extends Command
{
    /**
     * The command signature.
     */
    protected $signature = 'laravel-shape:placeholder';

    /**
     * The command description.
     */
    protected $description = 'Placeholder Artisan command shipped by the package laravel-shape.';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $this->line('Shape placeholder command executed.');

        return self::SUCCESS;
    }
}
