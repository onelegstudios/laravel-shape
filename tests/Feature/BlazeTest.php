<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Livewire\Blaze\Blaze;
use Onelegstudios\Shape\Tests\TestCase;

// These assert on compiled output rather than rendered HTML, because rendering
// cannot tell the pipelines apart -- that is the whole point of Blaze, and it is
// also how a broken setup hides. The suite passed for a while against a harness
// that never registered Blaze at all, so the first test here exists to make that
// failure loud rather than silent.
//
// The icon has to be published before any of this means anything: an icon that is
// not on disk cannot be folded, and Blaze quietly falls back to the function
// compiler instead. A test that skipped this step would assert the fallback and
// call it a pass.
beforeEach(function () {
    File::deleteDirectory(TestCase::iconPath());

    $this->artisan('shape:icon', ['name' => ['check'], '--no-clear' => true])->run();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('is registered and enabled in the package test environment', function () {
    expect(Blaze::isEnabled())->toBeTrue();
});

it('compiles the button call site into a blaze function call', function () {
    // Blade's own pipeline resolves an AnonymousComponent and calls
    // renderComponent(); Blaze replaces the whole of that with one function call.
    // The button stops there rather than folding, because it reads its defaults
    // from config and folding would freeze them into the compiled view.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toContain('$__blaze')
        ->not->toContain('renderComponent');
});

it('folds the icon call site away entirely', function () {
    // Further than the button: nothing global is left in the icon once its set and
    // alias table are resolved at publish time, so a call site that names its icon
    // leaves no component behind at all -- not even the function call.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg')
        ->not->toContain('renderComponent');
});

it('optimises the branded tag and the namespaced tag the same way', function () {
    // The service provider rewrites <shape:button> into <x-shape::button> from a
    // prepareStringsForCompilationUsing callback, and Blaze hooks the same list
    // from app()->booted(). Registering later is what puts Blaze after the
    // rewrite, so it never sees the branded syntax. Nothing enforces that order,
    // so assert on it: the day it flips, the branded tag silently stops being
    // optimised while the namespaced one carries on working.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toBe(Blade::compileString('<x-shape::button>Save</x-shape::button>'));
});

it('memoizes neither component', function () {
    // Memoization caches rendered output against the call site alone. The icon has
    // no need of it -- folding removes the render entirely -- and the button cannot
    // have it, because its output depends on config the cache key cannot see.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->not->toContain('blaze_memoized_key');

    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->not->toContain('blaze_memoized_key');
});
