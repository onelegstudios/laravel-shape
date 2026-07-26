<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Tests;

use Onelegstudios\Shape\ShapeServiceProvider;
use Orchestra\Testbench\TestCase as Orchestra;

abstract class TestCase extends Orchestra
{
    protected function getPackageProviders($app): array
    {
        return [
            ShapeServiceProvider::class,
        ];
    }
}
