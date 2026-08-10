<?php

declare(strict_types=1);

use Illuminate\Support\Facades\File;
use Illuminate\Testing\PendingCommand;
use Onelegstudios\Shape\Icons\Libraries;
use Onelegstudios\Shape\Tests\TestCase;

// The same bargain as the sibling suites: the provider reads the icon path when
// it boots, so these run against the configured default rather than a per-test
// override.
//
// Nothing here passes --no-clear, because checking has nothing to clear. Seeding
// does, since it goes through `add` and the compiled-view directory is shared
// with every other test.
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

function stageIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:add', $arguments + ['--no-clear' => true]);
}

function checkIcons(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:check', $arguments);
}

function refreshIcon(array $arguments = []): PendingCommand
{
    return test()->artisan('shape:icon:update', $arguments + ['--no-clear' => true]);
}

/**
 * Publish into a set whose artwork the tests can actually move.
 *
 * Upstream Lucide is redrawn on someone else's schedule, so "the set moved" is
 * staged by moving what the name resolves to. The two fixture SVGs carry a
 * `data-fixture` marker saying which file was read.
 */
function fixtureSet(string $alias = 'mark', string $target = 'check'): void
{
    config()->set('shape.icons.set', 'fixture');
    config()->set('shape.icons.sets', ['fixture' => 'fixture']);
    config()->set('shape.icons.aliases', [$alias => $target]);
}

/**
 * Take the stamp back out of a published file, leaving one as it would have
 * been published before stamps existed.
 */
function unstamp(string $file): void
{
    File::put($file, preg_replace('/^\s*stamp:[0-9a-f]{16} --\}\}$/m', ' --}}', File::get($file)) ?? '');
}

it('reports a freshly published icon as up to date', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    checkIcons()
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('reports an icon the set has moved under as an update available', function () {
    fixtureSet();

    stageIcon(['name' => ['mark']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    checkIcons()
        ->expectsOutputToContain('update available')
        ->assertSuccessful();
});

it('reports a hand-edited file as edited', function () {
    // The distinction the stamp exists for. Nothing about the set moved here,
    // so the only thing that can explain the difference is the edit.
    stageIcon(['name' => ['check']])->assertSuccessful();

    $file = $this->iconPath.'/lucide/art/check.blade.php';

    File::put($file, File::get($file)."\n{{-- mine --}}\n");

    checkIcons()
        ->expectsOutputToContain('edited')
        ->assertSuccessful();
});

it('tells a hand edit and an upgrade apart when both have happened', function () {
    // Two independent comparisons rather than one: the file against its own
    // stamp, and the stamp against a fresh render. Neither can stand in for the
    // other, which is why this state has a name.
    fixtureSet();

    stageIcon(['name' => ['mark']])->assertSuccessful();

    $file = $this->iconPath.'/fixture/art/mark.blade.php';

    File::put($file, File::get($file)."\n{{-- mine --}}\n");

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    checkIcons()
        ->expectsOutputToContain('edited, update available')
        ->assertSuccessful();
});

it('reports an icon as up to date again once it has been updated', function () {
    // The round trip: what `update` writes is what `check` reads back, so a
    // refreshed icon reports clean. A stamp taken of anything other than the
    // bytes on disk would show up here as an icon that is permanently edited.
    fixtureSet();

    stageIcon(['name' => ['mark']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    refreshIcon(['name' => ['mark']])->assertSuccessful();

    checkIcons()
        ->expectsOutputToContain('up to date')
        ->doesntExpectOutputToContain('update available')
        ->assertSuccessful();
});

it('names the icon the set no longer has', function () {
    // The resolved name, not the published one: it is the name that went missing
    // upstream and the one to search for. Asserted on its own because two
    // substring expectations against one rendered line only ever satisfy the
    // first.
    fixtureSet('other', 'cross');

    stageIcon(['name' => ['other']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['other' => 'gone']);

    checkIcons()
        ->expectsOutputToContain('fixture-gone')
        ->assertSuccessful();
});

it('carries on past an icon the set no longer has', function () {
    fixtureSet();

    config()->set('shape.icons.aliases', ['mark' => 'check', 'other' => 'cross']);

    stageIcon(['name' => ['mark', 'other']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'check', 'other' => 'gone']);

    checkIcons()
        ->expectsOutputToContain('missing from set')
        ->expectsOutputToContain('1 up to date.')
        ->assertSuccessful();
});

it('reports a default forward that has gone missing', function () {
    // Updating writes this file, so a report that ignored it would call an icon
    // current that `shape:icon:update` is about to touch.
    stageIcon(['name' => ['check']])->assertSuccessful();

    File::delete($this->iconPath.'/default/check.blade.php');

    checkIcons()
        ->expectsOutputToContain('forward out of date')
        ->assertSuccessful();
});

it('reports a default forward that names another set', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    File::put(
        $this->iconPath.'/default/check.blade.php',
        '<x-shape-icon::fixture.check {{ $attributes }} />',
    );

    checkIcons()
        ->expectsOutputToContain('forward out of date')
        ->assertSuccessful();
});

it('leaves the default forward out of it when the set is not the default', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    stageIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    checkIcons()
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('reports a name that was never published and carries on', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    checkIcons(['name' => ['check', 'never-published']])
        ->expectsOutputToContain('not published')
        ->assertSuccessful();
});

it('refuses a name that would climb out of the published directory', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    $escape = $this->iconPath.'/lucide/../../hostage.blade.php';

    File::put($escape, 'hostage');

    checkIcons(['name' => ['../../hostage']])
        ->expectsOutputToContain('not published')
        ->assertSuccessful();

    File::delete($escape);
});

it('checks every published set without being asked which one', function () {
    // Where it parts company with its siblings. They act, so narrowing them to a
    // set is a safety property; this one looks, and a report covering one of two
    // directories is half an answer. The console mock also fails this test if the
    // command asks anything at all.
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    stageIcon(['name' => ['check']])->assertSuccessful();
    stageIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    checkIcons()
        ->expectsOutputToContain('fixture/cross')
        ->expectsOutputToContain('lucide/check')
        ->assertSuccessful();
});

it('reads each set with its own names when two libraries are published', function () {
    // The sweep is the place a single alias table would show: `spinner` is one
    // name to a view and two icons to the libraries, so a report that resolved
    // both directories the same way would call one of them out of date on every
    // run and send somebody chasing an update that changes nothing.
    stageIcon(['name' => ['spinner']])->assertSuccessful();
    stageIcon(['name' => ['spinner'], '--set' => 'outline'])->assertSuccessful();

    checkIcons()
        ->expectsOutputToContain('lucide/spinner')
        ->expectsOutputToContain('outline/spinner')
        ->doesntExpectOutputToContain('update available')
        ->doesntExpectOutputToContain('missing from set')
        ->assertSuccessful();
});

it('narrows the report to the named set', function () {
    config()->set('shape.icons.sets', ['lucide' => 'lucide', 'fixture' => 'fixture']);

    stageIcon(['name' => ['check']])->assertSuccessful();
    stageIcon(['name' => ['cross'], '--set' => 'fixture'])->assertSuccessful();

    checkIcons(['--set' => 'fixture'])
        ->expectsOutputToContain('fixture/cross')
        ->doesntExpectOutputToContain('lucide/check')
        ->assertSuccessful();
});

it('narrows the report to the named icons', function () {
    stageIcon(['name' => ['check', 'x']])->assertSuccessful();

    checkIcons(['name' => ['x']])
        ->expectsOutputToContain('lucide/x')
        ->doesntExpectOutputToContain('lucide/check')
        ->assertSuccessful();
});

it('succeeds and says so when nothing is published at all', function () {
    checkIcons()
        ->expectsOutputToContain('No icons are published.')
        ->assertSuccessful();
});

it('warns when the named set has nothing published in it', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    checkIcons(['--set' => 'fixture'])
        ->expectsOutputToContain('No icons are published in set [fixture].')
        ->assertSuccessful();
});

it('succeeds on a clean directory with --strict', function () {
    // Clean means both halves: nothing has drifted, *and* every name Shape's own
    // views ask for is on disk. A directory holding one icon of a consumer's own
    // choosing is not clean -- it is a directory where the button's spinner has no
    // artwork behind it.
    publishRequiredIcons();

    stageIcon(['name' => ['check']])->assertSuccessful();

    checkIcons(['--strict' => true])->assertSuccessful();
});

it('fails on drift with --strict', function () {
    fixtureSet();

    stageIcon(['name' => ['mark']])->assertSuccessful();

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    checkIcons(['--strict' => true])
        ->expectsOutputToContain('Published icons are not up to date.')
        ->assertFailed();
});

it('succeeds on drift without --strict', function () {
    // A hand-edited icon is a choice somebody made, not a fault. A report that
    // failed the build over one would teach people to stop running it.
    stageIcon(['name' => ['check']])->assertSuccessful();

    $file = $this->iconPath.'/lucide/art/check.blade.php';

    File::put($file, File::get($file)."\n{{-- mine --}}\n");

    checkIcons()->assertSuccessful();
});

it('says so when a file carries no stamp', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    unstamp($this->iconPath.'/lucide/art/check.blade.php');

    checkIcons()
        ->expectsOutputToContain('(unstamped)')
        ->assertSuccessful();
});

it('still answers whether an unstamped file is out of date', function () {
    // The header cannot be compared, since its format is the thing that changed.
    // The artwork below it still can, which is the half of the question worth
    // answering on the day the package is upgraded.
    fixtureSet();

    stageIcon(['name' => ['mark']])->assertSuccessful();

    unstamp($this->iconPath.'/fixture/mark.blade.php');

    config()->set('shape.icons.aliases', ['mark' => 'cross']);

    checkIcons()
        ->expectsOutputToContain('update available')
        ->assertSuccessful();
});

it('calls an unstamped file up to date when its artwork still matches', function () {
    stageIcon(['name' => ['check']])->assertSuccessful();

    unstamp($this->iconPath.'/lucide/art/check.blade.php');

    checkIcons()
        ->expectsOutputToContain('up to date')
        ->assertSuccessful();
});

it('changes nothing, even where everything is wrong', function () {
    // The property the whole command rests on. Every state it can report is
    // staged at once and the directory is compared byte for byte and mtime for
    // mtime afterwards.
    fixtureSet();

    config()->set('shape.icons.aliases', ['mark' => 'check', 'other' => 'cross']);

    stageIcon(['name' => ['mark', 'other']])->assertSuccessful();

    $edited = $this->iconPath.'/fixture/art/other.blade.php';

    File::put($edited, File::get($edited)."\n{{-- mine --}}\n");

    File::delete($this->iconPath.'/default/mark.blade.php');

    config()->set('shape.icons.aliases', ['mark' => 'cross', 'other' => 'cross']);

    $before = [];

    foreach (File::allFiles($this->iconPath) as $file) {
        touch($file->getPathname(), time() - 60);

        $before[$file->getPathname()] = [File::get($file->getPathname()), filemtime($file->getPathname())];
    }

    clearstatcache();

    checkIcons(['name' => ['mark', 'other', 'never-published']])->assertSuccessful();

    clearstatcache();

    $after = [];

    foreach (File::allFiles($this->iconPath) as $file) {
        $after[$file->getPathname()] = [File::get($file->getPathname()), filemtime($file->getPathname())];
    }

    expect($after)->toBe($before);
});

describe('the icons Shape renders', function () {
    // The one absence this command can see and the acting verbs cannot. `add` is
    // driven by what a caller named, `remove` and `update` by what a directory
    // already holds, so a name that was never published is invisible to all three
    // -- and a component that starts drawing a new mark leaves an application
    // that has not re-run `shape:install` with a view that throws.

    it('names every one that is not published', function () {
        stageIcon(['name' => ['check']])->assertSuccessful();

        $report = checkIcons();

        foreach (Libraries::required() as $name) {
            $report->expectsOutputToContain('default/'.$name);
        }

        $report
            ->expectsOutputToContain("5 icon(s) Shape's own components render are not published.")
            ->assertSuccessful();
    });

    it('says so even when nothing at all is published', function () {
        // The emptiest directory is the one where the most is about to break, so
        // it must not also be the quietest report.
        checkIcons()
            ->expectsOutputToContain('No icons are published.')
            ->expectsOutputToContain("5 icon(s) Shape's own components render are not published.")
            ->assertSuccessful();
    });

    it('fails --strict', function () {
        // Which is the point of the whole addition: a CI run should be able to
        // catch this before a page does.
        checkIcons(['--strict' => true])
            ->expectsOutputToContain('Published icons are not up to date.')
            ->assertFailed();
    });

    it('stays quiet once they are published', function () {
        publishRequiredIcons();

        checkIcons()
            ->doesntExpectOutputToContain("Shape's own components render are not published")
            ->assertSuccessful();
    });

    it('reads the forward rather than the set the artwork sits in', function () {
        // Those views ask without a `set` prop, so `default/` is what answers.
        // Artwork in a set with no forward beside it is a directory nothing
        // renders from, which is the same absence.
        publishRequiredIcons();

        File::delete($this->iconPath.'/default/spinner.blade.php');

        checkIcons()
            ->expectsOutputToContain('default/spinner')
            ->expectsOutputToContain("1 icon(s) Shape's own components render are not published.")
            ->assertSuccessful();
    });

    it('leaves a run narrowed to one name alone', function () {
        // `check spinner` is a question about one icon. Answering it with a
        // paragraph about four others is the report nobody asked for.
        stageIcon(['name' => ['check']])->assertSuccessful();

        checkIcons(['name' => ['check']])
            ->doesntExpectOutputToContain("Shape's own components render are not published")
            ->assertSuccessful();
    });

    it('still reports them when the rows were narrowed to one set', function () {
        // `--set` narrows which directories are swept. What these names are
        // missing from is `default/`, which is not a set a caller can name.
        stageIcon(['name' => ['check']])->assertSuccessful();

        checkIcons(['--set' => 'lucide'])
            ->expectsOutputToContain("5 icon(s) Shape's own components render are not published.")
            ->assertSuccessful();
    });
});
