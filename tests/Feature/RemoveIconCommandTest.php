<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Onelegstudios\Shape\Tests\TestCase;

// Same bargain as the add tests: the provider reads the icon path when it boots,
// so these run against the configured default rather than a per-test override.
//
// Removing clears compiled views, and that directory is shared with every other
// test, so everything here passes --no-clear except the two tests that are about
// clearing -- those point `view.compiled` at scratch space of their own.
beforeEach(function () {
    $this->iconPath = TestCase::iconPath();

    File::deleteDirectory($this->iconPath);
});

afterEach(function () {
    File::deleteDirectory($this->iconPath);
});

function publish(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:add', $arguments + ['--no-clear' => true]);
}

function removeIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:remove', $arguments + ['--no-clear' => true]);
}

it('removes a published icon', function () {
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['name' => ['check']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse();
});

it('takes the default forward away with the icon it points at', function () {
    // The forward is written by `add` alongside the real file, so leaving it
    // behind would break <shape:icon name="x" /> with nothing to render.
    publish(['name' => ['x']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/default/x.blade.php'))->toBeTrue();

    removeIcon(['name' => ['x']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/default/x.blade.php'))->toBeFalse();
});

it('leaves a forward alone when it points at another set', function () {
    // `icons.set` can move after an icon is published. The forward then belongs
    // to whichever set it names, and goes when that one does.
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publish(['name' => ['check']])->assertSuccessful();
    publish(['name' => ['check'], '--set' => 'fixture'])->assertSuccessful();

    removeIcon(['name' => ['check'], '--set' => 'fixture'])->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/check.blade.php'))->toBeFalse()
        ->and(File::get($this->iconPath.'/default/check.blade.php'))->toContain('lucide.art.check');
});

it('names the file rather than resolving it through the alias table', function () {
    // `add close` writes close.blade.php holding Lucide's `x`, so `remove close`
    // has to look for that file and not for one named after the set's own name.
    config()->set('shape.icons.aliases', ['close' => 'x']);

    publish(['name' => ['close']])->assertSuccessful();

    removeIcon(['name' => ['close']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/close.blade.php'))->toBeFalse();
});

it('removes a hand-edited icon, because naming a file is the whole instruction', function () {
    publish(['name' => ['check']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    removeIcon(['name' => ['check']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse();
});

it('reports a name that was never published and carries on', function () {
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['name' => ['check', 'never-published']])
        ->expectsOutputToContain('not published')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse();
});

it('refuses a name that would climb out of the published directory', function () {
    publish(['name' => ['check']])->assertSuccessful();

    $escape = $this->iconPath.'/lucide/../../hostage.blade.php';

    File::put($escape, 'hostage');

    removeIcon(['name' => ['../../hostage']])->assertSuccessful();

    expect(File::exists($escape))->toBeTrue();

    File::delete($escape);
});

it('succeeds and says so when nothing is published at all', function () {
    removeIcon(['name' => ['check']])
        ->expectsOutputToContain('No icons are published.')
        ->assertSuccessful();
});

it('drops a set directory once its last icon is gone', function () {
    // Which sets are published is read off these directories, so an empty one
    // would go on being offered as somewhere to remove icons from.
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['name' => ['check']])->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse()
        ->and(File::isDirectory($this->iconPath.'/default'))->toBeFalse();
});

it('keeps a set directory that still holds something', function () {
    publish(['name' => ['check', 'x']])->assertSuccessful();

    removeIcon(['name' => ['check']])->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeTrue()
        ->and(File::exists($this->iconPath.'/lucide/x.blade.php'))->toBeTrue();
});

it('removes every published icon in the set with --all', function () {
    publish(['name' => ['check', 'x']])->assertSuccessful();

    removeIcon(['--all' => true, '--force' => true])->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse();
});

it('asks before sweeping the whole set', function () {
    publish(['name' => ['check', 'x']])->assertSuccessful();

    removeIcon(['--all' => true])
        ->expectsConfirmation('Remove all 2 published icon(s) from set [lucide]?', 'no')
        ->expectsOutputToContain('No icons removed.')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('sweeps the set once the confirmation is answered', function () {
    publish(['name' => ['check', 'x']])->assertSuccessful();

    removeIcon(['--all' => true])
        ->expectsConfirmation('Remove all 2 published icon(s) from set [lucide]?', 'yes')
        ->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse();
});

it('fails rather than let an unanswerable confirmation stand in for --force', function () {
    // The failure mode this exists for: a prompt nobody answers returns its
    // default, and a scripted sweep would report success having removed nothing.
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['--all' => true, '--no-interaction' => true])
        ->expectsOutputToContain('--force')
        ->assertFailed();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('fails when given no names and no --all and nobody to ask', function () {
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['--no-interaction' => true])->assertFailed();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('asks which icons to remove when nothing is named', function () {
    publish(['name' => ['check', 'x']])->assertSuccessful();

    removeIcon()
        ->expectsChoice('Which icons should be removed?', ['check'], ['check', 'x'])
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse()
        ->and(File::exists($this->iconPath.'/lucide/x.blade.php'))->toBeTrue();
});

it('removes nothing and succeeds when nothing is selected', function () {
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon()
        ->expectsChoice('Which icons should be removed?', [], ['check'])
        ->expectsOutputToContain('No icons removed.')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('asks which set to remove from once more than one has icons in it', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publish(['name' => ['check']])->assertSuccessful();
    publish(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    removeIcon()
        ->expectsChoice('Which set should these icons be removed from?', 'fixture', [
            'fixture' => 'fixture',
            'lucide' => 'lucide',
        ])
        ->expectsChoice('Which icons should be removed?', ['cross'], ['cross'])
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/cross.blade.php'))->toBeFalse()
        ->and(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('does not ask which set when only one has icons in it', function () {
    // Only the icon question is expected, and the mock fails the test if the
    // command asks anything else.
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon()
        ->expectsChoice('Which icons should be removed?', [], ['check'])
        ->assertSuccessful();
});

it('offers the one published set even when it is not the configured default', function () {
    // The case this is for: the library was uninstalled and dropped from config,
    // and its artwork is still sitting in resources/views.
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    publish(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    removeIcon(['name' => ['cross']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/cross.blade.php'))->toBeFalse();
});

it('warns when the named set has nothing published in it', function () {
    publish(['name' => ['check']])->assertSuccessful();

    removeIcon(['name' => ['cross'], '--set' => 'fixture'])
        ->expectsOutputToContain('No icons are published in set [fixture].')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('refuses to remove an icon Shape\'s own components render', function () {
    // `spinner` is not published because anyone asked for it -- `shape:install`
    // puts it there so the button's loading state has artwork -- so removing it
    // leaves a shipped component rendering nothing.
    publish(['name' => ['spinner']])->assertSuccessful();

    removeIcon(['name' => ['spinner']])
        ->expectsOutputToContain('--force')
        ->assertFailed();

    expect(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeTrue()
        ->and(File::exists($this->iconPath.'/default/spinner.blade.php'))->toBeTrue();
});

it('removes an icon Shape renders once --force says so', function () {
    // An application that renders its own spinner, or has stopped using the
    // button, is owed a way to say the package is wrong about what it needs.
    publish(['name' => ['spinner']])->assertSuccessful();

    removeIcon(['name' => ['spinner'], '--force' => true])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeFalse();
});

it('carries out the rest of the names and still fails on the one it kept', function () {
    publish(['name' => ['check', 'spinner']])->assertSuccessful();

    removeIcon(['name' => ['check', 'spinner']])->assertFailed();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse()
        ->and(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeTrue();
});

it('leaves the icons Shape renders behind when --all sweeps the set', function () {
    publish(['name' => ['check', 'spinner']])->assertSuccessful();

    // One, not two: the count is the sweep as it will actually happen, so the
    // confirmation is not asking about a file that is staying either way.
    removeIcon(['--all' => true])
        ->expectsConfirmation('Remove all 1 published icon(s) from set [lucide]?', 'yes')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeFalse()
        ->and(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeTrue();
});

it('sweeps the icons Shape renders too when --all is forced', function () {
    publish(['name' => ['check', 'spinner']])->assertSuccessful();

    removeIcon(['--all' => true, '--force' => true])->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/lucide'))->toBeFalse();
});

it('does not offer the icons Shape renders in the prompt', function () {
    // The options the mock is given are the assertion: a name that cannot be
    // picked is a clearer statement than one that is refused after answering.
    publish(['name' => ['check', 'spinner']])->assertSuccessful();

    removeIcon()
        ->expectsChoice('Which icons should be removed?', ['check'], ['check'])
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeTrue();
});

it('says so rather than asking when the only published icons are Shape\'s own', function () {
    publish(['name' => ['spinner']])->assertSuccessful();

    removeIcon(['--all' => true])
        ->expectsOutputToContain('Only icons Shape\'s own components render are published in [lucide].')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/spinner.blade.php'))->toBeTrue();
});

it('drops compiled views, including Blaze\'s own, once something is removed', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    publish(['name' => ['check']])->assertSuccessful();

    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled.'/blaze');
    File::put($compiled.'/stale.php', 'stale');
    File::put($compiled.'/blaze/stale.php', 'stale');

    $this->artisan('shape:icon:remove', ['name' => ['check']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeFalse()
        ->and(File::isDirectory($compiled.'/blaze'))->toBeFalse();

    File::deleteDirectory($compiled);
});

it('leaves compiled views alone when nothing was actually removed', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    publish(['name' => ['check']])->assertSuccessful();

    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled);
    File::put($compiled.'/stale.php', 'stale');

    // Nothing left the disk, so there is nothing to invalidate and no reason to
    // make every other view in the application recompile.
    $this->artisan('shape:icon:remove', ['name' => ['never-published']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeTrue();

    File::deleteDirectory($compiled);
});
