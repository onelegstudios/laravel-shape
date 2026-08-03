<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Onelegstudios\Shape\Tests\TestCase;

// The provider reads the icon path when it boots, so these run against the
// configured default rather than a per-test override -- which is the path a
// consumer actually publishes into.
//
// Publishing clears compiled views, and that directory is shared with every
// other test: clearing it mid-run pulls compiled views out from under whatever
// a parallel worker is rendering. So the tests below that go on to render pass
// --no-clear, and the two that are about clearing point `view.compiled` at
// scratch space and never render.
beforeEach(function () {
    $this->iconPath = TestCase::iconPath();

    File::deleteDirectory($this->iconPath);
});

afterEach(function () {
    File::deleteDirectory($this->iconPath);
});

function publishIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:add', $arguments + ['--no-clear' => true]);
}

it('publishes an icon into a directory named for the set', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('resolves a name through the alias table but keeps Shape\'s own name on the file', function () {
    // An alias an application added, rather than one the package ships: the
    // mechanism is the same and the test does not move when the shipped table
    // does. `close` is Lucide's `x`, and the file is named for what views ask
    // for, so swapping the library later is a re-publish rather than a
    // find-and-replace.
    config()->set('shape.icons.aliases', ['close' => 'x']);

    publishIcon(['name' => ['close']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/close.blade.php'))->toBeTrue()
        ->and(File::get($this->iconPath.'/lucide/close.blade.php'))->toContain('lucide-x');
});

it('publishes the alias the package ships', function () {
    // The button's loading state renders `spinner`, so this is the one entry a
    // component depends on: the shipped table has to name an icon the configured
    // set actually holds, or the state fails the first time it is rendered.
    publishIcon(['name' => ['spinner']])->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/spinner.blade.php'))->toContain('lucide-loader-circle');
});

it('records a stamp in the file it writes', function () {
    // What `shape:icon:check` reads back to tell a hand edit from a set that has
    // moved. A published file with no stamp can only be compared on its artwork.
    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toMatch('/\n\s*stamp:[0-9a-f]{16} --\}\}\n/');
});

it('keeps same-named icons from two sets apart', function () {
    // The case a flat directory would lose: two sets, one name, both wanted.
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publishIcon(['name' => ['check']])->assertSuccessful();
    publishIcon(['name' => ['check'], '--set' => 'fixture'])->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->not->toContain('data-fixture')
        ->and(File::get($this->iconPath.'/fixture/check.blade.php'))->toContain('data-fixture');
});

it('writes a default forward only for the configured default set', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/default/check.blade.php'))->toBeTrue();

    File::deleteDirectory($this->iconPath.'/default');

    publishIcon(['name' => ['check'], '--set' => 'fixture'])->assertSuccessful();

    expect(File::exists($this->iconPath.'/default/check.blade.php'))->toBeFalse();
});

it('warns and leaves a published icon alone rather than overwriting it', function () {
    // Adding never overwrites: the file may have been hand-tuned, and there is
    // no flag here that says otherwise. Refreshing one is a separate verb.
    publishIcon(['name' => ['check']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    publishIcon(['name' => ['check']])
        ->expectsOutputToContain('already published')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toBe('EDITED');
});

it('adds the icons that are missing and warns about the ones that are not', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    publishIcon(['name' => ['check', 'x']])->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/x.blade.php'))->toContain('<svg');
});

it('fails naming the prefix it tried when the icon does not exist', function () {
    publishIcon(['name' => ['not-a-real-icon']])
        ->expectsOutputToContain('lucide')
        ->assertFailed();
});

it('fails when given no names and no --all and nobody to ask', function () {
    // A scripted run has to fail rather than block on a prompt nothing will
    // answer, so --no-interaction keeps the old behaviour exactly.
    publishIcon(['--no-interaction' => true])->assertFailed();
});

it('asks for icon names when nothing is named', function () {
    publishIcon()
        ->expectsQuestion('Which icon?', 'check')
        ->expectsQuestion('Which icon?', 'x')
        ->expectsQuestion('Which icon?', '')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue()
        ->and(File::exists($this->iconPath.'/lucide/x.blade.php'))->toBeTrue();
});

it('asks which set to take them from once more than one is configured', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publishIcon()
        ->expectsChoice('Which set should these icons come from?', 'fixture', [
            'lucide' => 'lucide',
            'fixture' => 'fixture',
        ])
        ->expectsQuestion('Which icon?', 'cross')
        ->expectsQuestion('Which icon?', '')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/cross.blade.php'))->toBeTrue();
});

it('does not ask which set when the answer could only be one thing', function () {
    // Only the icon question is expected, and the mock fails the test if the
    // command asks anything else.
    publishIcon()
        ->expectsQuestion('Which icon?', '')
        ->assertSuccessful();
});

it('takes the set from --set rather than asking for it', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publishIcon(['--set' => 'fixture'])
        ->expectsQuestion('Which icon?', 'cross')
        ->expectsQuestion('Which icon?', '')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/cross.blade.php'))->toBeTrue();
});

it('adds nothing and succeeds when the first answer is empty', function () {
    publishIcon()
        ->expectsQuestion('Which icon?', '')
        ->expectsOutputToContain('No icons added.')
        ->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse();
});

it('rejects a name the set does not have instead of collecting it', function () {
    // Validating in the prompt is what keeps a typo from costing the session:
    // outside a test this asks again, where a test gives up on the first
    // rejection rather than looping on an answer that cannot change.
    publishIcon()
        ->expectsQuestion('Which icon?', 'not-a-real-icon')
        ->expectsOutputToContain('lucide-not-a-real-icon')
        ->assertFailed();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse();
});

it('offers Shape\'s own names as well as the set\'s', function () {
    // `close` is not a Lucide name -- it is an alias for `x` -- and it has to be
    // answerable here, because it is the name the published file gets.
    config()->set('shape.icons.aliases', ['close' => 'x']);

    publishIcon()
        ->expectsQuestion('Which icon?', 'close')
        ->expectsQuestion('Which icon?', '')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/close.blade.php'))->toContain('lucide-x');
});

it('publishes every icon in a set with --all', function () {
    config()->set('shape.icons.sets', ['fixture' => 'fixture']);

    publishIcon(['--set' => 'fixture', '--all' => true])->assertSuccessful();

    expect(count(File::files($this->iconPath.'/fixture')))->toBeGreaterThan(1);
});

it('renders a published icon through its tag prefix', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(Blade::render('<x-shape-icon::lucide.check class="size-4" />'))
        ->toContain('<svg')
        ->toContain('size-4')
        ->toContain('shrink-0');
});

it('folds a published icon away entirely', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    // The point of publishing: nothing global is left, so Blaze can inline it.
    expect(Blade::compileString('<x-shape-icon::lucide.check class="size-4" />'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg');
});

it('resolves the default forward to the default set', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(Blade::render('<x-shape-icon::default.check />'))->toContain('<svg');
});

it('drops compiled views, including Blaze\'s own, once something is published', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled.'/blaze');
    File::put($compiled.'/stale.php', 'stale');
    File::put($compiled.'/blaze/stale.php', 'stale');

    $this->artisan('shape:icon:add', ['name' => ['check']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeFalse()
        ->and(File::isDirectory($compiled.'/blaze'))->toBeFalse();

    File::deleteDirectory($compiled);
});

it('leaves compiled views alone when nothing new was published', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    config()->set('view.compiled', $compiled);

    publishIcon(['name' => ['check']])->assertSuccessful();

    File::ensureDirectoryExists($compiled);
    File::put($compiled.'/stale.php', 'stale');

    // The second run skips the already-published icon, so there is nothing to
    // invalidate and no reason to make every other view recompile.
    $this->artisan('shape:icon:add', ['name' => ['check']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeTrue();

    File::deleteDirectory($compiled);
});
