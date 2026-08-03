<?php

declare(strict_types=1);

use Onelegstudios\Shape\Tests\TestCase;

// Set before the first application is created, which is earlier than any hook
// on the test case can reach: the service manifest is written while the
// framework is bootstrapping providers, before defineEnvironment() runs. All
// three, because Env reads through whichever adapters are enabled.
$services = TestCase::servicesCachePath();

putenv('APP_SERVICES_CACHE='.$services);

$_ENV['APP_SERVICES_CACHE'] = $services;
$_SERVER['APP_SERVICES_CACHE'] = $services;

uses(TestCase::class)->in(__DIR__);
