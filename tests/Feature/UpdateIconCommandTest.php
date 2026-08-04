<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Onelegstudios\Shape\Tests\TestCase;

// Same bargain as the add and remove tests: the provider reads the icon path
// when it boots, so these run against the configured default rather than a
// per-test override.
//
// Updating clears compiled views, and that directory is shared with every other
// test, so everything here passes --no-clear except the two tests that are about
// clearing -- those point `view.compiled` at scratch space of their own.
//
// The helpers are named apart from the ones the sibling suites declare because
// Pest's module-level functions all land in one namespace, and a redeclaration
// is a fatal error rather than a failing test.
beforeEach(function () {
    $this->iconPath = TestCase::iconPath();

    File::deleteDirectory($this->iconPath);
});

afterEach(function () {
    File::deleteDirectory($this->iconPath);
});

function seedIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:add', $arguments + ['--no-clear' => true]);
}

function updateIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:update', $arguments + ['--no-clear' => true]);
}

/**
 * Publish into a set whose artwork the tests can actually change.
 *
 * Upstream Lucide is redrawn on someone else's schedule and cannot be mutated
 * here, so a refresh is proven by moving what the name resolves to. The two
 * fixture SVGs carry a `data-fixture` marker saying which file was read.
 */
function usingFixtures(string $alias = 'mark', string $target = 'check'): void
{
    config()->set('shape.icons.set', 'fixture');
    config()->set('shape.icons.sets', ['fixture' => 'fixture']);
    config()->set('shape.icons.aliases', [$alias => $target]);
}

it('rewrites the file from what the name resolves to now', function () {
    // The set did not move; the alias did. Update resolves through config as it
    // stands, which is also why it never reads its own header to find out where
    // the file came from.
    usingFixtures();

    seedIcon(['name' => ['mark']])->assertSuccessful();

    expect(File::get($this->iconPath.'/fixture/mark.blade.php'))->toContain('data-fixture="check"');

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    updateIcon(['name' => ['mark']])
        ->expectsOutputToContain('updated')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/fixture/mark.blade.php'))
        ->toContain('data-fixture="cross"')
        ->not->toContain('data-fixture="check"');
});

it('records a stamp for the artwork it just wrote', function () {
    // Not the one the file had. A stamp left behind by the previous artwork
    // would make `shape:icon:check` report a file it had just refreshed as
    // edited, for as long as it sat there.
    usingFixtures();

    seedIcon(['name' => ['mark']])->assertSuccessful();

    $before = File::get($this->iconPath.'/fixture/mark.blade.php');

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    updateIcon(['name' => ['mark']])->assertSuccessful();

    $after = File::get($this->iconPath.'/fixture/mark.blade.php');

    expect($after)->toMatch('/\n\s*stamp:[0-9a-f]{16} --\}\}\n/')
        ->and($after)->not->toBe($before);
});

it('keeps the set directory and the file name when the artwork changes', function () {
    // The file is addressed the way `remove` addresses one -- by the name it was
    // published under -- and only its contents come from the alias table.
    usingFixtures();

    seedIcon(['name' => ['mark']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    updateIcon(['name' => ['mark']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/fixture/mark.blade.php'))->toBeTrue()
        ->and(File::exists($this->iconPath.'/fixture/cross.blade.php'))->toBeFalse();
});

it('picks up a set whose prefix now points at another library', function () {
    // The "we swapped the library behind this set name" case: the directory keeps
    // its name, the prefix underneath it moves.
    config()->set('shape.icons.sets', ['lucide' => 'lucide']);

    seedIcon(['name' => ['check']])->assertSuccessful();

    config()->set('shape.icons.sets', ['lucide' => 'fixture']);

    updateIcon(['name' => ['check']])->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toContain('data-fixture="check"');
});

it('refreshes a set against the names its own library uses', function () {
    // Published from the packaged table, so it has to be refreshed from the same
    // one: resolving `spinner` Lucide's way in a Heroicons directory would
    // report it missing from the set and leave the file to rot.
    seedIcon(['name' => ['spinner'], '--set' => 'outline'])->assertSuccessful();

    updateIcon(['name' => ['spinner'], '--set' => 'outline'])
        ->doesntExpectOutputToContain('missing from set')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/outline/spinner.blade.php'))->toContain('heroicon-o-arrow-path');
});

it('overwrites a hand-edited file, because that is the whole verb', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    updateIcon(['name' => ['check']])
        ->expectsOutputToContain('updated')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))
        ->toContain('<svg')
        ->not->toContain('EDITED');
});

it('reports an unchanged icon and does not touch the file', function () {
    // Also the proof that both verbs render the same bytes: if adding and
    // updating disagreed by a single character, nothing would ever be unchanged.
    seedIcon(['name' => ['check']])->assertSuccessful();

    $file = $this->iconPath.'/lucide/check.blade.php';

    touch($file, time() - 60);
    clearstatcache();

    $before = File::lastModified($file);

    updateIcon(['name' => ['check']])
        ->expectsOutputToContain('unchanged')
        ->assertSuccessful();

    clearstatcache();

    expect(File::lastModified($file))->toBe($before);
});

it('reports an icon the set no longer has and updates the rest', function () {
    // What a package upgrade that renamed a glyph looks like from here. Failing
    // outright would leave every other icon stale over one missing name.
    usingFixtures();

    config()->set('shape.icons.aliases', ['mark' => 'check', 'other' => 'cross']);

    seedIcon(['name' => ['mark', 'other']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross', 'other' => 'gone']);

    updateIcon(['name' => ['mark', 'other']])
        ->expectsOutputToContain('missing from set')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/fixture/mark.blade.php'))->toContain('data-fixture="cross"');
});

it('names the icon the set could not resolve and leaves the file as it was', function () {
    usingFixtures('other', 'cross');

    seedIcon(['name' => ['other']])->assertSuccessful();

    $before = File::get($this->iconPath.'/fixture/other.blade.php');

    config()->set('shape.icons.aliases', ['other' => 'gone']);

    // The resolved name, not just the published one: it is the name the set does
    // not have and the one you would search upstream for. Asserted on its own
    // because two substring expectations against one rendered line only ever
    // satisfy the first.
    updateIcon(['name' => ['other']])
        ->expectsOutputToContain('fixture-gone')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/fixture/other.blade.php'))->toBe($before);
});

it('reports a name that was never published and carries on', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    updateIcon(['name' => ['check', 'never-published']])
        ->expectsOutputToContain('not published')
        ->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('names the file rather than resolving it through the alias table', function () {
    // `add close` writes close.blade.php holding Lucide's `x`, so `update close`
    // has to look for that file and not for one named after the set's own name.
    config()->set('shape.icons.aliases', ['close' => 'x']);

    seedIcon(['name' => ['close']])->assertSuccessful();

    updateIcon(['name' => ['close']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/close.blade.php'))->toBeTrue()
        ->and(File::exists($this->iconPath.'/lucide/x.blade.php'))->toBeFalse();
});

it('refuses a name that would climb out of the published directory', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    $escape = $this->iconPath.'/lucide/../../hostage.blade.php';

    File::put($escape, 'hostage');

    updateIcon(['name' => ['../../hostage']])->assertSuccessful();

    // Existence is not the assertion here: an overwrite would leave the file
    // exactly as present as it started.
    expect(File::get($escape))->toBe('hostage');

    File::delete($escape);
});

it('writes a missing default forward for the configured default set', function () {
    // The case that matters is not a hand-deleted file but `icons.set` having
    // moved onto a set whose icons were published before it was the default.
    seedIcon(['name' => ['check']])->assertSuccessful();

    File::delete($this->iconPath.'/default/check.blade.php');

    updateIcon(['name' => ['check']])
        ->expectsOutputToContain('updated')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/default/check.blade.php'))->toContain('lucide.check');
});

it('repoints a default forward that names another set', function () {
    // Written directly rather than through `add`, which writes a forward only
    // alongside a file it has just published.
    seedIcon(['name' => ['check']])->assertSuccessful();

    File::put(
        $this->iconPath.'/default/check.blade.php',
        '<x-shape-icon::fixture.check {{ $attributes }} />',
    );

    updateIcon(['name' => ['check']])->assertSuccessful();

    expect(File::get($this->iconPath.'/default/check.blade.php'))->toContain('lucide.check');
});

it('leaves the default directory alone when the set is not the default', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    seedIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    updateIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    expect(File::isDirectory($this->iconPath.'/default'))->toBeFalse();
});

it('succeeds and says so when nothing is published at all', function () {
    updateIcon(['name' => ['check']])
        ->expectsOutputToContain('No icons are published.')
        ->assertSuccessful();
});

it('warns when the named set has nothing published in it', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    updateIcon(['name' => ['cross'], '--set' => 'fixture'])
        ->expectsOutputToContain('No icons are published in set [fixture].')
        ->assertSuccessful();
});

it('asks which set to update in once more than one has icons in it', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    seedIcon(['name' => ['check']])->assertSuccessful();
    seedIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    updateIcon()
        ->expectsChoice('Which set should these icons be updated in?', 'fixture', [
            'fixture' => 'fixture',
            'lucide' => 'lucide',
        ])
        ->expectsChoice('Which icons should be updated?', ['cross'], ['cross'])
        ->assertSuccessful();
});

it('does not ask which set when only one has icons in it', function () {
    // Only the icon question is expected, and the mock fails the test if the
    // command asks anything else.
    seedIcon(['name' => ['check']])->assertSuccessful();

    updateIcon()
        ->expectsChoice('Which icons should be updated?', [], ['check'])
        ->assertSuccessful();
});

it('takes the set from --set rather than asking for it', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    seedIcon(['name' => ['check']])->assertSuccessful();
    seedIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    updateIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();
});

it('updates nothing and succeeds when nothing is selected', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    updateIcon()
        ->expectsChoice('Which icons should be updated?', [], ['check'])
        ->expectsOutputToContain('No icons updated.')
        ->assertSuccessful();
});

it('fails when given no names and no --all and nobody to ask', function () {
    seedIcon(['name' => ['check']])->assertSuccessful();

    updateIcon(['--no-interaction' => true])
        ->expectsOutputToContain('Name at least one icon, or pass --all.')
        ->assertFailed();
});

it('updates every published icon in the set with --all', function () {
    usingFixtures();

    config()->set('shape.icons.aliases', ['mark' => 'check', 'other' => 'check']);

    seedIcon(['name' => ['mark', 'other']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross', 'other' => 'cross']);

    updateIcon(['--all' => true, '--force' => true])->assertSuccessful();

    expect(File::get($this->iconPath.'/fixture/mark.blade.php'))->toContain('data-fixture="cross"')
        ->and(File::get($this->iconPath.'/fixture/other.blade.php'))->toContain('data-fixture="cross"');
});

it('asks before rewriting the whole set', function () {
    seedIcon(['name' => ['check', 'x']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    updateIcon(['--all' => true])
        ->expectsConfirmation('Update all 2 published icon(s) in set [lucide]?', 'no')
        ->expectsOutputToContain('No icons updated.')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toBe('EDITED');
});

it('rewrites the set once the confirmation is answered', function () {
    seedIcon(['name' => ['check', 'x']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    updateIcon(['--all' => true])
        ->expectsConfirmation('Update all 2 published icon(s) in set [lucide]?', 'yes')
        ->assertSuccessful();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toContain('<svg');
});

it('fails rather than let an unanswerable confirmation stand in for --force', function () {
    // The failure mode this exists for: a prompt nobody answers returns its
    // default, and a scripted sweep would report success having rewritten
    // nothing.
    seedIcon(['name' => ['check']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    updateIcon(['--all' => true, '--no-interaction' => true])
        ->expectsOutputToContain('--force')
        ->assertFailed();

    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toBe('EDITED');
});

it('drops compiled views, including Blaze\'s own, once something is updated', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    usingFixtures();

    seedIcon(['name' => ['mark']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross']);
    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled.'/blaze');
    File::put($compiled.'/stale.php', 'stale');
    File::put($compiled.'/blaze/stale.php', 'stale');

    $this->artisan('shape:icon:update', ['name' => ['mark']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeFalse()
        ->and(File::isDirectory($compiled.'/blaze'))->toBeFalse();

    File::deleteDirectory($compiled);
});

it('leaves compiled views alone when nothing actually changed', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    seedIcon(['name' => ['check']])->assertSuccessful();

    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled);
    File::put($compiled.'/stale.php', 'stale');

    // Nothing on disk moved, so there is nothing to invalidate and no reason to
    // make every other view in the application recompile.
    $this->artisan('shape:icon:update', ['name' => ['check']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeTrue();

    File::deleteDirectory($compiled);
});
