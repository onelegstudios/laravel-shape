<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Livewire\Blaze\Blaze;

// These assert on compiled output rather than rendered HTML, because rendering
// cannot tell the two pipelines apart -- that is the whole point of Blaze, and it
// is also how a broken setup hides. The suite passed for a while against a
// harness that never registered Blaze at all, so the first test here exists to
// make that failure loud rather than silent.

it('is registered and enabled in the package test environment', function () {
    expect(Blaze::isEnabled())->toBeTrue();
});

it('compiles a component call site into a blaze function call', function (string $template) {
    $compiled = Blade::compileString($template);

    // Blade's own pipeline resolves an AnonymousComponent and calls
    // renderComponent(); Blaze replaces the whole of that with one function call.
    expect($compiled)
        ->toContain('$__blaze')
        ->not->toContain('renderComponent');
})->with([
    '<shape:button>Save</shape:button>',
    '<shape:icon name="check" />',
]);

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

it('does not memoize the icon, whose output depends on config', function () {
    // Memoization keys on the call site alone, while the icon resolves its set,
    // its aliases, and its default size from config. Two identical tags either
    // side of a config change would share one cache entry and the second would
    // answer with the first one's SVG -- which is the promise the icon exists to
    // make. IconComponentTest catches the regression; this names it.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->not->toContain('blaze_memoized_key');
});

it('does not memoize the button, which reads config and has a slot', function () {
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->not->toContain('blaze_memoized_key');
});
