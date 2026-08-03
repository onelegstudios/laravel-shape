<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Onelegstudios\Shape\Tests\TestCase;

// Every test here writes into the Testbench skeleton rather than scratch space,
// because the two things the command computes -- the `../` depth of the import
// and the path it prints back -- are both relative to the application root. A
// stylesheet in /tmp would exercise the absolute-path fallback and nothing else.
//
// The default icon set is left off unless a test is about icons: with Lucide
// absent from the skeleton's composer.json, the icon step would otherwise reach
// for Composer, and no test should be able to start a download.
beforeEach(function () {
    $this->stylesheet = TestCase::stylesheetPath();
    $this->manifest = base_path('package.json');
    // Redirected out of the shared skeleton by TestCase, so publishing and
    // deleting it here cannot reach another worker.
    $this->config = TestCase::configPath().'/shape.php';

    File::ensureDirectoryExists(dirname($this->stylesheet));

    File::delete([$this->stylesheet, $this->manifest, $this->config]);
});

afterEach(function () {
    File::delete([$this->stylesheet, $this->manifest, $this->config]);

    File::deleteDirectory(TestCase::iconPath());
});

// Scripted by default. Most of what this command does has nothing to do with
// being asked, and a test that spelled out every prompt to get at the file it
// cares about would be asserting on the prompts by accident.
function installShape(array $arguments = []): PendingCommand
{
    return askingInstall($arguments + ['--no-interaction' => true]);
}

function askingInstall(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:install', $arguments + [
        '--css' => TestCase::stylesheetPath(),
        '--no-icons' => true,
    ]);
}

it('imports the theme into the application stylesheet', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape()->assertSuccessful();

    expect(File::get($this->stylesheet))
        ->toContain('@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";');
});

it('puts the import after the last one rather than at the end of the file', function () {
    // @import has to precede every other rule, so appending would produce a line
    // the browser and the Tailwind parser both drop -- and a dropped import
    // looks exactly like never having run this command.
    File::put($this->stylesheet, implode("\n", [
        '/* the application theme */',
        '@import "tailwindcss";',
        '',
        '@import "./brand.css";',
        '',
        '@theme {',
        '    --color-brand: #123456;',
        '}',
    ]));

    installShape()->assertSuccessful();

    $lines = explode("\n", File::get($this->stylesheet));

    expect($lines[4])->toBe('@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";')
        ->and($lines[3])->toBe('@import "./brand.css";');
});

it('keeps the line endings the stylesheet already used', function () {
    // Rewriting a checked-in file from CRLF to LF would put every line of it in
    // the diff, and the one line this command wrote would be lost in it.
    File::put($this->stylesheet, "@import \"tailwindcss\";\r\n\r\nbody { margin: 0; }\r\n");

    installShape()->assertSuccessful();

    $contents = File::get($this->stylesheet);

    expect($contents)->toContain("shape.css\";\r\n")
        ->and(preg_match('/[^\r]\n/', $contents))->toBe(0);
});

it('counts the path from where the stylesheet actually is', function () {
    // The conventional resources/css/app.css is two levels down, but nothing
    // makes a consumer keep it there, and a hard-coded ../../ would resolve to
    // a vendor directory that does not exist.
    $stylesheet = base_path('shape-install-'.getmypid().'.css');

    File::put($stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--css' => $stylesheet])->assertSuccessful();

    expect(File::get($stylesheet))
        ->toContain('@import "./vendor/onelegstudios/laravel-shape/resources/css/shape.css";');

    File::delete($stylesheet);
});

it('leaves a stylesheet that already imports the theme untouched', function () {
    $contents = implode("\n", [
        '@import "tailwindcss";',
        '@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";',
    ]);

    File::put($this->stylesheet, $contents);

    installShape()
        ->expectsOutputToContain('theme already imported')
        ->assertSuccessful();

    expect(File::get($this->stylesheet))->toBe($contents);
});

it('warns about a stylesheet with no Tailwind import but still writes the line', function () {
    // Refusing would leave nothing to fix by hand. The import is still the line
    // the consumer needs; it just does nothing until Tailwind is above it.
    File::put($this->stylesheet, "body { margin: 0; }\n");

    installShape()
        ->expectsOutputToContain('does not import Tailwind')
        ->assertSuccessful();

    expect(File::get($this->stylesheet))->toContain('laravel-shape/resources/css/shape.css');
});

it('fails naming the file when --css points at a stylesheet that is not there', function () {
    installShape(['--css' => 'resources/css/nope.css'])
        ->expectsOutputToContain('nope.css')
        ->assertFailed();
});

it('fails with the line to add by hand when there is no stylesheet and nobody to ask', function () {
    installShape(['--css' => null])
        ->expectsOutputToContain('resources/css/app.css')
        ->expectsOutputToContain('@import "../../vendor/onelegstudios/laravel-shape/resources/css/shape.css";')
        ->assertFailed();
});

it('asks which stylesheet to use when the conventional one is missing', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    $answer = 'resources/css/'.basename($this->stylesheet);

    askingInstall(['--css' => null])
        ->expectsQuestion('Which stylesheet should import the theme?', $answer)
        ->expectsConfirmation('Publish the config file?', 'no')
        ->assertSuccessful();

    expect(File::get($this->stylesheet))->toContain('laravel-shape/resources/css/shape.css');
});

it('leaves the stylesheet alone under --no-css', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--no-css' => true])->assertSuccessful();

    expect(File::get($this->stylesheet))->toBe('@import "tailwindcss";'."\n");
});

it('warns when package.json asks for a Tailwind older than the theme needs', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");
    File::put($this->manifest, json_encode(['devDependencies' => ['tailwindcss' => '^4.0.9']]));

    installShape()
        ->expectsOutputToContain('4.1')
        ->assertSuccessful();
});

it('says nothing about a Tailwind new enough to build the theme', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");
    File::put($this->manifest, json_encode(['devDependencies' => ['tailwindcss' => '^4.1.3']]));

    installShape()
        ->doesntExpectOutputToContain('needs 4.1')
        ->assertSuccessful();
});

it('warns when package.json lists no Tailwind at all', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");
    File::put($this->manifest, json_encode(['devDependencies' => ['vite' => '^7.0']]));

    installShape()
        ->expectsOutputToContain('does not list tailwindcss')
        ->assertSuccessful();
});

it('says nothing about Tailwind in an application that has no package.json', function () {
    // Not every application builds its CSS through npm, and one that does not is
    // not misconfigured -- the question just cannot be answered from here.
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape()
        ->doesntExpectOutputToContain('tailwindcss')
        ->assertSuccessful();
});

it('publishes the config file when asked', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--config' => true])->assertSuccessful();

    expect(File::exists($this->config))->toBeTrue();
});

it('leaves the config unpublished when the answer is no', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    askingInstall()
        ->expectsConfirmation('Publish the config file?', 'no')
        ->assertSuccessful();

    expect(File::exists($this->config))->toBeFalse();
});

it('does not ask about the config when there is nobody to answer', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape()->assertSuccessful();

    expect(File::exists($this->config))->toBeFalse();
});

it('publishes the icons named in the alias table', function () {
    // Pointed at the fixture set, which no Composer package installs, so the
    // command publishes from what is already there rather than reaching for the
    // network. That is the same branch an application with its set installed
    // takes.
    config()->set('shape.icons.set', 'fixture');
    config()->set('shape.icons.sets', ['fixture' => 'fixture']);
    config()->set('shape.icons.aliases', ['check' => 'check', 'close' => 'cross']);

    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--no-icons' => false])->assertSuccessful();

    $path = TestCase::iconPath();

    expect(File::exists($path.'/fixture/check.blade.php'))->toBeTrue()
        // Named for what Shape's views ask for, holding what the set calls it.
        ->and(File::exists($path.'/fixture/close.blade.php'))->toBeTrue()
        ->and(File::get($path.'/fixture/close.blade.php'))->toContain('fixture-cross');
});

it('publishes no icons under --no-icons', function () {
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape()->assertSuccessful();

    expect(File::exists(TestCase::iconPath()))->toBeFalse();
});

it('prints the two commands rather than reaching for Composer with nobody to ask', function () {
    // The skeleton does not require Lucide, so this is the branch where the set
    // has to be installed. Without an answer and without --icons, it says what
    // to run and installs nothing.
    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--no-icons' => false])
        ->expectsOutputToContain('composer require mallardduck/blade-lucide-icons')
        ->expectsOutputToContain('php artisan shape:icon:add')
        ->assertSuccessful();

    expect(File::exists(TestCase::iconPath()))->toBeFalse();
});

it('can be run twice', function () {
    config()->set('shape.icons.set', 'fixture');
    config()->set('shape.icons.sets', ['fixture' => 'fixture']);
    config()->set('shape.icons.aliases', ['check' => 'check']);

    File::put($this->stylesheet, '@import "tailwindcss";'."\n");

    installShape(['--no-icons' => false, '--config' => true])->assertSuccessful();

    $stylesheet = File::get($this->stylesheet);

    installShape(['--no-icons' => false, '--config' => true])
        ->expectsOutputToContain('theme already imported')
        ->expectsOutputToContain('already published')
        ->assertSuccessful();

    expect(File::get($this->stylesheet))->toBe($stylesheet);
});
