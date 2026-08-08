<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Onelegstudios\Shape\Icons\Libraries;
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

/**
 * Put messages in the bag the framework shares onto every view, which is where the
 * field components read their invalid state from.
 *
 * Shared directly rather than through a request, and that is the point: the
 * components have to work without the session middleware that normally puts it
 * there, so a test that booted one would be proving the middleware instead. It
 * lives here rather than in a test file because two of them need it.
 *
 * @param  array<string, array<int, string>>  $messages
 */
/**
 * Publish the icons Shape's own views ask for, so a component that renders one has
 * something to resolve.
 *
 * The error message is the reason this exists: it draws `error` whenever it has a
 * sentence to show, and an unpublished icon is an exception rather than a blank
 * space. `Libraries::required()` rather than a list written out here, so a name
 * added to the alias table arrives in the suite without a second edit.
 */
function publishRequiredIcons(): void
{
    File::deleteDirectory(TestCase::iconPath());

    test()->artisan('shape:icon:add', [
        'name' => Libraries::required(),
        '--no-clear' => true,
    ])->run();
}

/**
 * Read a file from the package root, so a test can hold documentation or a
 * stylesheet against the behaviour it describes.
 */
function shapeFile(string $path): string
{
    return (string) file_get_contents(__DIR__.'/../'.$path);
}

/**
 * @param  array<string, array<int, string>>  $messages
 */
function seedErrors(array $messages): void
{
    $bag = new ViewErrorBag;

    $bag->put('default', new MessageBag($messages));

    view()->share('errors', $bag);
}
