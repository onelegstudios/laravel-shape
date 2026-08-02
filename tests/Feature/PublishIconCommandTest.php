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
    return test()->artisan('shape:icon', $arguments + ['--no-clear' => true]);
}

it('publishes an icon into a directory named for the set', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/check.blade.php'))->toBeTrue();
});

it('resolves a name through the alias table but keeps Shape\'s own name on the file', function () {
    // `close` is Lucide's `x`. The file is named for what views ask for, so
    // swapping the library later is a re-publish rather than a find-and-replace.
    publishIcon(['name' => ['close']])->assertSuccessful();

    expect(File::exists($this->iconPath.'/lucide/close.blade.php'))->toBeTrue()
        ->and(File::get($this->iconPath.'/lucide/close.blade.php'))->toContain('lucide-x');
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

it('does not overwrite a published icon unless forced', function () {
    publishIcon(['name' => ['check']])->assertSuccessful();

    File::put($this->iconPath.'/lucide/check.blade.php', 'EDITED');

    publishIcon(['name' => ['check']])->assertSuccessful();
    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->toBe('EDITED');

    publishIcon(['name' => ['check'], '--force' => true])->assertSuccessful();
    expect(File::get($this->iconPath.'/lucide/check.blade.php'))->not->toBe('EDITED');
});

it('fails naming the prefix it tried when the icon does not exist', function () {
    publishIcon(['name' => ['not-a-real-icon']])
        ->expectsOutputToContain('lucide')
        ->assertFailed();
});

it('fails when given no names and no --all', function () {
    publishIcon()->assertFailed();
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
    publishIcon(['name' => ['close']])->assertSuccessful();

    expect(Blade::render('<x-shape-icon::default.close />'))->toContain('<svg');
});

it('drops compiled views, including Blaze\'s own, once something is published', function () {
    $compiled = sys_get_temp_dir().'/shape-compiled-'.bin2hex(random_bytes(4));

    config()->set('view.compiled', $compiled);

    File::ensureDirectoryExists($compiled.'/blaze');
    File::put($compiled.'/stale.php', 'stale');
    File::put($compiled.'/blaze/stale.php', 'stale');

    $this->artisan('shape:icon', ['name' => ['check']])->assertSuccessful();

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
    $this->artisan('shape:icon', ['name' => ['check']])->assertSuccessful();

    expect(File::exists($compiled.'/stale.php'))->toBeTrue();

    File::deleteDirectory($compiled);
});
