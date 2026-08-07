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

    $this->artisan('shape:icon:add', ['name' => ['check'], '--no-clear' => true])->run();
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

it('keeps a folded icon folded when it is nested in a button', function () {
    // The slot is compiled in the calling template, so an icon written there still
    // names itself at compile time and still disappears. Passing a button a slot
    // costs its icon nothing.
    expect(Blade::compileString('<shape:button><shape:icon name="check" />Save</shape:button>'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg');
});

it('cannot fold the icon a button takes as a prop', function () {
    // The name crosses a component boundary: inside the button it is a variable,
    // which is the dynamic `:name` case the icon documents as declining to fold.
    // Asserted rather than left implicit because the call site does not look
    // dynamic -- `icon="check"` is a literal, and the folding it loses is real.
    //
    // What it must not do is fall all the way back to Blade's component pipeline,
    // which is what this pins: the icon is still reached by a Blaze function call.
    $compiled = Blade::compileString('<shape:button icon="check">Save</shape:button>');

    expect($compiled)
        ->not->toContain('[BlazeFolded]')
        ->toContain('$__blaze')
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

it('leaves the field components on blade\'s own pipeline', function (string $tag) {
    // The one family that opts out, and not as an oversight. Blaze compiles
    // `@aware` against its own data stack while Blade compiles it against the
    // component stack, and the two disagree twice over: BlazeRuntime walks only
    // the ancestors where ManagesComponents checks the component's own data
    // first, and Blaze's AwareCompiler also unsets the key from $attributes on
    // the way past. A field compiled by one and a label by the other would read
    // two different names, which is the single thing these components exist to
    // get right.
    //
    // Asserted rather than left to a comment because the cost of getting it wrong
    // is invisible: the markup still renders, it just wires itself to the wrong
    // field.
    expect(Blade::compileString($tag))
        ->toContain('renderComponent')
        ->not->toContain('$__blaze');
})->with([
    'the input' => ['<shape:input name="email" />'],
    'the select' => ['<shape:select name="plan" />'],
    'the textarea' => ['<shape:textarea name="bio" />'],
    'the file input' => ['<shape:file name="avatar" />'],
    'the checkbox' => ['<shape:checkbox name="terms" value="1" />'],
    'the radio' => ['<shape:radio name="plan" value="pro" />'],
    'the switch' => ['<shape:switch name="notify" />'],
    'the field' => ['<shape:field name="email"><shape:input /></shape:field>'],
    'the field as a group' => ['<shape:field name="plan" legend="Plan"><shape:radio value="free" /></shape:field>'],
    'the label' => ['<shape:label>Email</shape:label>'],
    // The one member with no `@aware` of its own, so opting out is a choice rather
    // than a constraint -- pinned here so it stays one somebody made on purpose.
    'the legend' => ['<shape:legend>Plan</shape:legend>'],
    'the description' => ['<shape:description>Help</shape:description>'],
    'the error' => ['<shape:error name="email" />'],
]);

it('memoizes neither component', function () {
    // Memoization caches rendered output against the call site alone. The icon has
    // no need of it -- folding removes the render entirely -- and the button cannot
    // have it, because its output depends on config the cache key cannot see.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->not->toContain('blaze_memoized_key');

    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->not->toContain('blaze_memoized_key');
});
