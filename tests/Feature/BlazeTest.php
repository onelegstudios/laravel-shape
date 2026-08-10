<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Livewire\Blaze\Blaze;
use Livewire\Blaze\FrontMatter;
use Livewire\Blaze\Unblaze;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The config file the package ships, which every application depends on whether or
 * not it has published one: Laravel merges the package's defaults underneath.
 */
function packagedConfigPath(): string
{
    return (string) realpath(__DIR__.'/../../config/shape.php');
}

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

    // Blaze compiles each foldable component once into a scratch directory beside
    // the compiled views and never cleans it up, so what a fold does here depends
    // on whether an earlier run left that directory warm. Only the island case
    // actually turns on it (see the limitation pinned at the foot of this file),
    // but a suite whose answer changes between the first run and the second is
    // worse than either answer, so every test in this file starts cold.
    File::deleteDirectory(TestCase::compiledViewPath().'/blaze');
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());

    // The skeleton's config directory is read by every boot, so a shape.php left
    // behind by the invalidation test below would follow the rest of the suite
    // around. Deleted unconditionally, since a failing test is the case that most
    // needs it gone.
    File::delete(TestCase::configPath().'/shape.php');
});

it('is registered and enabled in the package test environment', function () {
    expect(Blaze::isEnabled())->toBeTrue();
});

it('folds the button call site away entirely', function () {
    // Blade's own pipeline resolves an AnonymousComponent and calls
    // renderComponent(); Blaze replaces the whole of that with one function call,
    // and folding removes even that -- the markup is written into the calling
    // template at compile time. The rendered `<button>` tag in the output is what
    // says so: nothing resolves it at render.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toContain('[BlazeFolded]')
        ->toContain('<button')
        ->not->toContain('renderComponent');
});

it('stamps the config file as a dependency of anything it folded into', function () {
    // The button reads `config('shape.components.button')`, and folding evaluates
    // that once. What keeps an edit to the config file from being ignored until
    // someone clears the view cache by hand is this marker: Blaze recompiles a view
    // whose listed dependencies are newer than the compile. The path has to be the
    // config file rather than the component, which is the whole of what
    // ShapeServiceProvider adds.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toContain('[BlazeFolded]:{shape-config}:{'.packagedConfigPath().'}');
});

it('stamps the config once per compilation rather than once per folded component', function () {
    // Every Shape component that folds passes through the same listener, so without
    // a guard a page of two hundred icons would carry two hundred identical
    // markers -- each one a file_exists() and a filemtime() on every render, which
    // is a slice of what folding just bought back.
    $compiled = Blade::compileString(implode("\n", [
        '<shape:button>Save</shape:button>',
        '<shape:button variant="solid">Publish</shape:button>',
        '<shape:icon name="check" />',
    ]));

    // Counted against the packaged path rather than the marker name, because an
    // application that has published its config is stamped for both files and a
    // bare count could not tell that apart from the guard having failed.
    expect(substr_count($compiled, '[BlazeFolded]:{shape-config}:{'.packagedConfigPath().'}'))->toBe(1);
});

it('expires a compiled view when the config file it folded is edited', function () {
    // What the marker is for, and the only part of this that an application would
    // ever notice. Asserted through Blaze's own predicate -- the same one its view
    // composer calls on every render to decide whether to recompile -- rather than
    // through two renders, because the composer memoizes its answer per path for
    // the life of the process and a second render in one test would read the memo
    // rather than the file.
    $config = TestCase::configPath().'/shape.php';

    File::copy(packagedConfigPath(), $config);

    $compiled = TestCase::compiledViewPath().'/config-dependency.php';

    File::put($compiled, Blade::compileString('<shape:button>Save</shape:button>'));

    $frontMatter = new FrontMatter;

    expect($frontMatter->containsExpiredFoldedDependencies($compiled))->toBeFalse();

    // The edit an application makes to restyle every button at once.
    touch($config, time() + 60);
    clearstatcache();

    expect($frontMatter->containsExpiredFoldedDependencies($compiled))->toBeTrue();
});

it('declines to fold a button whose props are dynamic, without falling back to blade', function () {
    // A prop the compiler cannot read is the ordinary reason a fold is skipped, and
    // skipping it is correct -- the value is only known at render. What matters is
    // where it lands: the function compiler, not Blade's component pipeline. A
    // dynamic call site is slower than a folded one and still much faster than an
    // unoptimised one, and this is what pins the difference.
    expect(Blade::compileString('<shape:button :variant="$variant">Save</shape:button>'))
        ->not->toContain('[BlazeFolded]')
        ->toContain('$__blaze')
        ->not->toContain('renderComponent');
});

it('folds a loading button without baking its translated label', function () {
    // The one read in this component that no invalidation could repair. `__()`
    // resolves against the request's locale, so a folded call would serve whichever
    // locale compiled the view to everyone after it. The loading overlay sits in an
    // island for that reason: the button still folds, and the call survives into
    // the compiled view to be made on render.
    $compiled = Blade::compileString('<shape:button loading>Save</shape:button>');

    expect($compiled)
        ->toContain('[BlazeFolded]')
        ->toContain("__('shape::messages.button.loading')")
        ->not->toContain(__('shape::messages.button.loading'));
});

it('follows a locale change on a button it has already folded', function () {
    // The test above proves the call survived compilation. This one proves it is
    // still being made, which is the claim that actually matters and the one a
    // single render cannot support: a frozen label satisfies every assertion about
    // the first render and fails only on the second.
    //
    // The spinner is published here rather than in the hook because this is the only
    // test in the file that renders the island instead of reading it. Folding never
    // resolves that icon -- an island's content is set aside before the compile-time
    // render and put back uncompiled -- so it has to be on disk at render.
    $this->artisan('shape:icon:add', ['name' => ['spinner'], '--no-clear' => true])->run();

    app('translator')->addLines(['messages.button.loading' => 'Chargement'], 'fr', 'shape');

    $english = Blade::render('<shape:button loading>Save</shape:button>');

    app()->setLocale('fr');

    expect($english)->toContain('aria-label="Loading"')
        ->and(Blade::render('<shape:button loading>Save</shape:button>'))
        ->toContain('aria-label="Chargement"');
});

it('folds the icon call site away entirely', function () {
    // For a reason of its own rather than the button's: nothing global is left in
    // the icon once its set and alias table are resolved at publish time, so there
    // is no per-request read to island and no config file to stamp.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg')
        ->not->toContain('renderComponent');
});

it('keeps a folded icon folded when it is nested in a button', function () {
    // An icon written into the slot is compiled in the calling template, so it names
    // itself at compile time and disappears whatever the button around it does.
    // Pinned because it is the one path that was already free before the button
    // folded, and it has to stay free.
    expect(Blade::compileString('<shape:button><shape:icon name="check" />Save</shape:button>'))
        ->toContain('[BlazeFolded]')
        ->toContain('<svg');
});

it('folds the icon a button takes as a prop', function () {
    // The prop used to be the expensive way to put a mark on a button: the name
    // crosses a component boundary, so inside the button it is a variable, and a
    // dynamic `:name` cannot be folded. Folding the button removes the boundary --
    // the icon is resolved in the same compile pass -- so the prop now costs what
    // writing the icon into the slot costs, which is nothing.
    expect(Blade::compileString('<shape:button icon="check">Save</shape:button>'))
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

it('compiles the field and header families through blaze', function (string $tag) {
    // The two families move as a unit, which is the part that has to hold: `@aware`
    // reads the render stack, so a field compiled by one pipeline and a label by
    // the other would read two different names. Every member is asserted here
    // rather than a representative few, because the failure mode of a single file
    // slipping out is invisible -- the markup still renders, it just wires itself
    // to the wrong field.
    //
    // `[BlazeFolded]` is asserted absent on every case as well. Folding this family
    // is a separate decision with its own hazards (Control::$sequence bakes its
    // counter, so unnamed fields in a loop would share an id), and this is what
    // stops `fold: true` arriving on one of these files without that argument being
    // made. See docs/performance.md.
    expect(Blade::compileString($tag))
        ->toContain('$__blaze')
        ->not->toContain('renderComponent')
        ->not->toContain('[BlazeFolded]');
})->with([
    'the input' => ['<shape:input name="email" />'],
    'the select' => ['<shape:select name="plan" />'],
    'the textarea' => ['<shape:textarea name="bio" />'],
    'the file input' => ['<shape:file name="avatar" />'],
    'the checkbox' => ['<shape:checkbox name="terms" value="1" />'],
    'the radio' => ['<shape:radio name="plan" value="pro" />'],
    'the switch' => ['<shape:switch name="notify" />'],
    'the range' => ['<shape:range name="volume" />'],
    'the colour input' => ['<shape:color name="brand" />'],
    'the field' => ['<shape:field name="email"><shape:input /></shape:field>'],
    'the field as a group' => ['<shape:field name="plan" legend="Plan"><shape:radio value="free" /></shape:field>'],
    'the label' => ['<shape:label>Email</shape:label>'],
    // The one member with no `@aware` of its own, so it moves with the family as a
    // choice rather than a constraint -- pinned here so it stays one.
    'the legend' => ['<shape:legend>Plan</shape:legend>'],
    'the description' => ['<shape:description>Help</shape:description>'],
    'the error' => ['<shape:error name="email" />'],
    // The affix pair is the case that had a reason of its own to stay behind, set
    // out at the top of input/prefix.blade.php: the input renders them from inside
    // its own template, where the component stack has already been popped, and only
    // Blade's `getConsumableComponentData` looks at `currentComponentData` before
    // walking ancestors. It does not bite -- the prop path passes both values
    // resolved, and the nested path finds the call site's own `pushData` -- but the
    // rendered behaviour is pinned in InputComponentTest rather than here, because
    // this file can only see which pipeline compiled it.
    'the prefix' => ['<shape:input.prefix>$</shape:input.prefix>'],
    'the suffix' => ['<shape:input.suffix>USD</shape:input.suffix>'],
    // The header family joins for the same reason and not the same shape: only the
    // item reads `@aware`, and the other three move with it because a header
    // compiled by one pipeline and an item by the other would size from two
    // different places. Same invisible failure -- the bar renders, the items just
    // take the configured rung rather than the one written on the header.
    'the header' => ['<shape:header><shape:header.item href="/">Docs</shape:header.item></shape:header>'],
    'the brand' => ['<shape:header.brand href="/">Acme</shape:header.brand>'],
    'the nav' => ['<shape:header.nav>Links</shape:header.nav>'],
    'the item' => ['<shape:header.item href="/">Docs</shape:header.item>'],
]);

it('folds the heading, which is the one component outside both families', function () {
    // Not in the list above, and pinned so the family's save-and-restore idiom does
    // not quietly spread to a file with no `@aware` to need it. It folds for the
    // button's reasons and with the button's trade: the `config()` read is a
    // compile-time input, invalidated by the stamp.
    expect(Blade::compileString('<shape:heading>Title</shape:heading>'))
        ->toContain('[BlazeFolded]:{shape::heading}')
        ->toContain('<h2')
        ->not->toContain('renderComponent');
});

it('declines to fold a heading whose actions slot is written', function () {
    // The hazard the button's `square` prop exists for, and the reason this file
    // does not need one of its own. `$actions` is read in logic -- the heading
    // grows a `<header>` around itself when the slot has content -- and at compile
    // time a slot is a placeholder, so an emptiness test on one answers differently
    // there than at render.
    //
    // Nothing in heading.blade.php guards against that. Blaze does: `actions` is a
    // declared prop, and a declared prop is unsafe for slots as well as for
    // attributes, so any call site that writes the slot declines. Asserted rather
    // than trusted, because it is the whole reason the component can read a slot in
    // logic and still fold -- and because the marker is the only thing that says so.
    $written = Blade::compileString('<shape:heading>Title<x-slot:actions>Go</x-slot:actions></shape:heading>');

    // Whitespace alone is the case that would diverge if it ever did fold: trimmed
    // at render it is no slot at all, and folded it would be a non-empty
    // placeholder.
    $blank = Blade::compileString("<shape:heading>Title<x-slot:actions>\n</x-slot:actions></shape:heading>");

    expect($written)->not->toContain('[BlazeFolded]:{shape::heading}')
        ->and($blank)->not->toContain('[BlazeFolded]:{shape::heading}');

    // And the two still render the shapes they always did.
    expect(Blade::render('<shape:heading>Title<x-slot:actions>Go</x-slot:actions></shape:heading>'))->toContain('<header')
        ->and(Blade::render("<shape:heading>Title<x-slot:actions>\n</x-slot:actions></shape:heading>"))->not->toContain('<header');
});

describe('a control repeated in a loop', function () {
    // Everything else in this file asks what the compiler did. This asks what
    // survives being repeated, which is the question folding makes interesting: a
    // folded component is evaluated once and its markup written into the calling
    // template, so anything derived per render is derived once and then repeated.
    //
    // `Control::resolve()` derives exactly one such thing. A labelled control with
    // no name has nothing to build an id from, so it takes the next number off a
    // process-wide counter -- and a folded loop would take one number and hand it to
    // every row, leaving a page of duplicate ids and a column of labels that all
    // click through to the first control.
    //
    // The family is compiled rather than folded today, so these pass as written.
    // They are here for the day `fold: true` reaches it, because nothing about the
    // markup looks wrong when it breaks: the ids are well-formed, the labels are
    // paired, and only the repetition gives it away.

    // These render rather than compile, and some of these controls draw a mark of
    // their own -- the select its chevron, the checkbox its tick. The file's own
    // hook publishes only what the folding tests need, so this asks for the set the
    // components require, which is read from the alias table rather than listed
    // here: a control that starts drawing something new arrives in this test on its
    // own.
    beforeEach(fn () => publishRequiredIcons());

    it('gives every iteration a generated id of its own', function (string $tag) {
        $html = Blade::render('@foreach ([1, 2, 3] as $row)'.$tag.'@endforeach');

        preg_match_all('/id="(shape-field-\d+)"/', $html, $matches);

        expect($matches[1])->toHaveCount(3)
            ->and(array_unique($matches[1]))->toHaveCount(3);
    })->with([
        'the input' => ['<shape:input label="Email" />'],
        'the select' => ['<shape:select label="Plan" />'],
        'the textarea' => ['<shape:textarea label="Bio" />'],
        'the file input' => ['<shape:file label="Avatar" />'],
        'the checkbox' => ['<shape:checkbox label="I agree" value="1" />'],
        'the radio' => ['<shape:radio label="Pro" value="pro" />'],
        'the switch' => ['<shape:switch label="Notify me" />'],
        'the range' => ['<shape:range label="Volume" />'],
        'the colour input' => ['<shape:color label="Brand" />'],
    ]);

    it('points every label at the control beside it', function () {
        // Distinct ids are only half the claim. The id exists so that a `<label>` has
        // something to name, so the pairing is the part worth asserting: three `for`
        // values in document order, each naming the control in its own iteration
        // rather than all three naming the first.
        //
        // Distinctness is asserted here too, and not as a repeat of the test above.
        // A frozen counter hands the same id to the label and to the control, so the
        // two lists match each other exactly while every row points at the first --
        // pairing alone is satisfied by the failure it is meant to catch.
        $html = Blade::render('@foreach ([1, 2, 3] as $row)<shape:input label="Email" />@endforeach');

        preg_match_all('/for="([^"]+)"/', $html, $labels);
        preg_match_all('/id="([^"]+)"/', $html, $controls);

        expect($labels[1])->toHaveCount(3)
            ->and($labels[1])->toBe($controls[1])
            ->and(array_unique($labels[1]))->toHaveCount(3);
    });
});

it('memoizes neither component', function () {
    // Memoization caches rendered output against the call site alone, and neither
    // component has any use for it: both fold, which removes the render rather than
    // caching it, and memoization only covers components without slots anyway --
    // which a button, being mostly slot, is not.
    expect(Blade::compileString('<shape:icon name="check" />'))
        ->not->toContain('blaze_memoized_key');

    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->not->toContain('blaze_memoized_key');
});

it('gives up the fold on a statically loading button once blaze has a warm scratch cache', function () {
    // A known Blaze limitation, pinned rather than worked around, because the day
    // it is fixed this test is the thing that says so.
    //
    // Blaze compiles a foldable component once into `<compiled>/blaze` and reuses
    // that file for the life of the directory -- but an island's content is keyed
    // in the compiled file by a token minted at random and kept in a static array,
    // which lasts only as long as the process. A second process finds the file,
    // skips the compile that would have refilled the array, and looks up a token
    // nobody remembers. The lookup warns, the warning surfaces as a Throwable, and
    // Folder::fold() answers a failed fold by handing back the unfolded component.
    // Nothing calls the renderer's own deleteTemporaryCacheDirectory(), so the
    // directory outlives every process that writes to it.
    //
    // What it costs is small and worth stating exactly. Only a call site that puts
    // the island in the output is affected, which means `loading` written as a
    // literal; the ordinary `:loading="$saving"` declines to fold anyway, and every
    // other button folds as usual. The output is right either way -- a component
    // that declines to fold is compiled, not broken -- so this is a lost
    // optimisation on a rare call site, not a rendering bug.
    expect(Blade::compileString('<shape:button loading>Save</shape:button>'))
        ->toContain('[BlazeFolded]');

    // What a new process sees: the scratch file still on disk, the token map empty.
    Unblaze::flushState();

    expect(Blade::compileString('<shape:button loading>Publish</shape:button>'))
        ->not->toContain('[BlazeFolded]')
        ->toContain('$__blaze');

    // The button with no island in its output is untouched by any of it.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toContain('[BlazeFolded]');
});
