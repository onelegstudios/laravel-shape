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

it('compiles the rest of the field family through blaze', function (string $tag) {
    // The family moves as a unit, which is the part that has to hold: `@aware`
    // reads the render stack, so a field compiled by one pipeline and a label by
    // the other would read two different names. Every member is asserted here
    // rather than a representative few, because the failure mode of a single file
    // slipping out is invisible -- the markup still renders, it just wires itself
    // to the wrong field.
    //
    // `[BlazeFolded]` is asserted absent on every case as well. Folding a control
    // is a separate decision with its own hazards -- `Control::$sequence` bakes its
    // counter, so unnamed fields in a loop would share an id, and the error bag is
    // read straight into the class on the box -- and this is what stops `fold: true`
    // arriving on one of these files without that argument being made. See
    // docs/performance.md.
    //
    // Three members have left this list rather than being exempted inside it: the
    // message, the field and the legend each have a describe block of their own
    // further down. Nothing else may join them without landing there too.
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
    'the label' => ['<shape:label>Email</shape:label>'],
    'the description' => ['<shape:description>Help</shape:description>'],
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
]);

describe('the error message', function () {
    // The one member of the field family that folds, and the only component in the
    // package whose correctness turns on an island rather than on an optimisation
    // the island happens to protect. The button's translated label would have been
    // wrong in one locale; a baked message is wrong in every request that has one.
    //
    // These render as well as compile, so the mark has to be on disk.
    beforeEach(fn () => publishRequiredIcons());

    it('folds the call site and leaves the bag read at render time', function () {
        $compiled = Blade::compileString('<shape:error name="email" />');

        expect($compiled)
            ->toContain('[BlazeFolded]:{shape::error}')
            ->not->toContain('renderComponent')
            // What the fold settled: the name it resolved and the id derived from
            // it, written into the compiled view as literals.
            ->toContain("'field' => 'email'")
            ->toContain('id="email-error"')
            // And what it did not: the lookup itself, which survives as a call.
            ->toContain('$errors->has($scope[\'field\'])');
    });

    it('reads the bag again on every render of a message it has already folded', function () {
        // The test above proves the call survived compilation. This one proves it is
        // still being made, which is the claim that actually matters and the one a
        // single render cannot support: a baked message satisfies every assertion
        // about the first render and fails only on the second. The button's locale
        // test is the same shape, for the same reason.
        seedErrors(['email' => ['The email field is required.']]);

        $first = Blade::render('<shape:error name="email" />');

        seedErrors(['email' => ['That address is already taken.']]);

        expect($first)->toContain('The email field is required.')
            ->and(Blade::render('<shape:error name="email" />'))
            ->toContain('That address is already taken.')
            ->not->toContain('The email field is required.');
    });

    it('renders nothing for a clean field it has already folded', function () {
        // The other half of the same claim, and the one a baked message passes by
        // accident: an element that folded to nothing would satisfy this whatever
        // the bag said.
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email" />'))->toContain('<p');

        seedErrors(['other' => ['Something else went wrong.']]);

        expect(trim(Blade::render('<shape:error name="email" />')))->toBe('');
    });

    it('folds a message that inherits its name from a field in the same template', function () {
        // `@aware` reads the render stack, which does not exist when a fold runs.
        // What Blaze consults instead is the enclosing component *nodes* of the
        // template it is compiling -- so an inherited name folds correctly exactly
        // when it is written where the compiler can see it, which is what this pins.
        expect(Blade::compileString('<shape:field name="email"><shape:error /></shape:field>'))
            ->toContain('[BlazeFolded]:{shape::error}')
            ->toContain("'field' => 'email'");
    });

    it('leaves the message a field draws for itself unfolded', function () {
        // The other side of the rule above, and the reason field.blade.php binds
        // `:name` on a message it renders itself. Inside that file the message sits
        // in plain markup with no component node above it, so a bare tag would fold
        // against a name of null and every composed field would carry a message that
        // had decided at compile time it had nothing to say. Bound, it declines.
        //
        // Asserted on the component's own source rather than through a call site,
        // because that is where the hazard is: the fold would happen when this file
        // is compiled, not when something calls it.
        expect(Blade::compileString(shapeFile('resources/views/components/field.blade.php')))
            ->not->toContain('[BlazeFolded]:{shape::error}');
    });

    it('declines to fold a message that says something of its own', function () {
        // A call site with words of its own never reads the bag, so it could fold in
        // principle. What stops it is that the test telling the two apart is
        // `trim((string) $slot)`, and a folded slot is a placeholder standing in for
        // content that has not been restored yet -- so the blank case below would
        // read as written and print an empty red row on every render. That is the
        // divergence the button's `square` prop exists for, and `unsafe: ['*']` is
        // what keeps this file from needing one: it counts children rather than
        // weighing them, which a narrower `unsafe: ['slot']` does not.
        $written = Blade::compileString('<shape:error name="email">That address is taken.</shape:error>');

        $blank = Blade::compileString("<shape:error name=\"email\">\n</shape:error>");

        expect($written)->not->toContain('[BlazeFolded]:{shape::error}')
            ->and($blank)->not->toContain('[BlazeFolded]:{shape::error}');

        // Both still render what they always did: the words for one, the validator's
        // sentence for the other.
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email">That address is taken.</shape:error>'))
            ->toContain('That address is taken.')
            ->not->toContain('The email field is required.');

        expect(Blade::render("<shape:error name=\"email\">\n</shape:error>"))
            ->toContain('The email field is required.');
    });

    it('declines to fold a message whose name is bound', function () {
        // `name` is read off the bag rather than declared as a prop, because a name
        // written here has to beat the field's -- so Blaze sees pass-through and
        // would otherwise let a bound one fold. It would not survive: `Fields::id()`
        // rewrites the placeholder's underscores on the way to the id, and what
        // reaches the browser is a mangled placeholder sitting in an `id`.
        $compiled = Blade::compileString('<shape:error :name="$field" />');

        expect($compiled)->not->toContain('[BlazeFolded]:{shape::error}')
            ->not->toContain('BLAZE-PLACEHOLDER')
            ->toContain('$__blaze');
    });

    it('folds a message with no name to nothing at all', function () {
        // Correct rather than merely cheap: a message that cannot name a field has
        // nothing to look up, and renders nothing today for the same reason.
        //
        // It is also the shape of the one limitation folding this component adds. An
        // inherited name has to be visible to the compiler, so a bare tag whose
        // field lives in another template -- across an `@include`, say -- folds to
        // this rather than to the message it would have found at render. See
        // docs/performance.md.
        $compiled = Blade::compileString('<shape:error />');

        expect($compiled)->toContain('[BlazeFolded]:{shape::error}')
            ->not->toContain('<p');
    });
});

describe('the field', function () {
    // The wrapper folds; the controls do not. What makes that safe is the thing
    // about folding that is easiest to get backwards -- a fold *renders* the
    // component, so everything the field calls is executed rather than left
    // standing, and while it runs BladeRenderer has the field's attributes on the
    // runtime data stack. The label and the description resolve their `@aware`
    // against that stack and come out as literal HTML, without either file carrying
    // `fold: true` of its own. Giving one of them the directive is the unsafe edit,
    // which is why they are still in the list above.
    beforeEach(fn () => publishRequiredIcons());

    it('folds a call site whose attributes are all literals', function () {
        $compiled = Blade::compileString(
            '<shape:field name="email" label="Email" description="We never share it."><shape:input /></shape:field>',
        );

        expect($compiled)
            ->toContain('[BlazeFolded]:{shape::field}')
            // The wrapper, and the two parts the field drew for itself, baked.
            ->toContain('<div class="flex flex-col gap-1.5">')
            ->toContain('<label class="text-sm font-medium text-ink" for="email">Email</label>')
            ->toContain('id="email-description"');
    });

    it('resolves the label it bakes against the field it is standing in', function () {
        // The claim the assertion above rests on, stated on its own because it is
        // the whole reason the label may not fold by itself. `for="email"` is not in
        // the markup anywhere -- the label derived it from a name it read off the
        // stack, at compile time, inside the fold.
        expect(Blade::render('<shape:field name="email" label="Email"><shape:input /></shape:field>'))
            ->toContain('for="email"')
            ->toContain('id="email"');
    });

    it('leaves the control in its slot to render', function () {
        // A slot is restored rather than evaluated, so the control inside a folded
        // field is still a call -- which is what it has to be: it reads the error
        // bag and may spend a generated id.
        $compiled = Blade::compileString('<shape:field name="email"><shape:input /></shape:field>');

        expect($compiled)->toContain('[BlazeFolded]:{shape::field}')
            ->toContain('input.blade.php')
            // And the wrapper pushes its data back, so the control's `@aware` finds
            // the name at render even though the component around it is gone.
            ->toContain("pushConsumableComponentData(['name' => 'email'");
    });

    it('carries a name through the fold to a control that never wrote one', function () {
        // The rendered half of the line above, and the one that would fail silently:
        // a folded wrapper that dropped its data would leave the control unnamed and
        // the form submitting nothing.
        expect(Blade::render('<shape:field name="email"><shape:input /></shape:field>'))
            ->toContain('name="email"');
    });

    it('reads the bag on every render of a field it has already folded', function () {
        // The message inside a folded field is the island stage 1 put there, and
        // this is the case it was built for: the field around it is gone, so the
        // island is now inline in the calling view.
        seedErrors(['email' => ['The email field is required.']]);

        $first = Blade::render('<shape:field name="email" label="Email"><shape:input /></shape:field>');

        seedErrors(['email' => ['That address is already taken.']]);

        expect($first)->toContain('The email field is required.')
            ->and(Blade::render('<shape:field name="email" label="Email"><shape:input /></shape:field>'))
            ->toContain('That address is already taken.')
            ->not->toContain('The email field is required.');
    });

    it('declines to fold the field the shorthand builds', function () {
        // The path that matters most and folds least. `<shape:input label="...">`
        // expands into a field, and every attribute it hands over is settled by
        // `Control::resolve()` at render -- so the call is bound, the fold declines,
        // and the sequence behind a generated id is spent per row as it always was.
        // Stated here because it is the ceiling on what folding the wrapper buys.
        expect(Blade::compileString('<shape:input name="email" label="Email" />'))
            ->not->toContain('[BlazeFolded]:{shape::field}')
            ->toContain('$__blaze');
    });

    it('declines to fold a field whose props are bound', function () {
        expect(Blade::compileString('<shape:field :name="$field" label="Email"><shape:input /></shape:field>'))
            ->not->toContain('[BlazeFolded]:{shape::field}');
    });

    it('pairs the label and the control in every iteration of a folded field', function () {
        // A folded wrapper must not leave the control inside it pointing somewhere
        // else. The ids repeat across the rows -- the label they derive from repeats
        // too, which the loop block at the foot of this file sets out -- so what is
        // asserted here is the pairing rather than the count.
        $html = Blade::render(
            '@foreach ([1, 2, 3] as $row)<shape:field><shape:input label="Email" /></shape:field>@endforeach',
        );

        preg_match_all('/for="([^"]+)"/', $html, $labels);
        preg_match_all('/id="([^"]+)"/', $html, $controls);

        expect($labels[1])->toHaveCount(3)
            ->and($labels[1])->toBe($controls[1]);
    });
});

describe('the header family', function () {
    // All four fold, which makes this the one `@aware` component in the package
    // allowed to -- and the difference from the controls is the whole reason it is
    // allowed. Folding resolves `@aware` by merging the inherited value into the
    // component's own attribute bag, so a folded item can no longer tell a rung
    // written on its tag from one that came down from the header. It does not need
    // to: both mean the same rung, and `except('size')` takes the key off the bag
    // before anything renders. A control asks the same question to decide whether it
    // owns its own validation message, and is refused the fold for it.

    it('folds every member', function (string $tag, string $marker) {
        expect(Blade::compileString($tag))
            ->toContain('[BlazeFolded]:{'.$marker.'}')
            ->not->toContain('renderComponent');
    })->with([
        'the header' => ['<shape:header><shape:header.item href="/">Docs</shape:header.item></shape:header>', 'shape::header'],
        'the brand' => ['<shape:header.brand href="/">Acme</shape:header.brand>', 'shape::header.brand'],
        'the nav' => ['<shape:header.nav>Links</shape:header.nav>', 'shape::header.nav'],
        'the item' => ['<shape:header.item href="/">Docs</shape:header.item>', 'shape::header.item'],
    ]);

    it('carries the rung through the fold to an item that never wrote one', function () {
        // `@aware` reads the render stack and folding runs before there is one, so
        // what stands in for it is Blaze wrapping a folded call site in a `pushData()`
        // of the attributes written on the tag. Without it the bar would still render
        // -- every item just taking the configured rung rather than the one asked
        // for, which is the invisible failure this family has always been pinned
        // against.
        expect(Blade::render('<shape:header size="lg"><shape:header.item href="/">Docs</shape:header.item></shape:header>'))
            ->toContain('px-3.5 py-2 text-base');
    });

    it('declines to fold an item whose rung is bound, at either end', function () {
        // A placeholder would pass the `is_string` test in the item, miss the rung
        // table and render an `md` item -- silently, and only for the call sites that
        // bound a value. `size` is an `@aware` key and Blaze treats those as unsafe,
        // which covers the header above as well as the item itself.
        expect(Blade::compileString('<shape:header.item :size="$rung" href="/">Docs</shape:header.item>'))
            ->not->toContain('[BlazeFolded]:{shape::header.item}');

        expect(Blade::compileString('<shape:header :size="$rung"><shape:header.item href="/">Docs</shape:header.item></shape:header>'))
            ->not->toContain('[BlazeFolded]:{shape::header.item}');
    });

    it('folds the nav without baking its translated landmark name', function () {
        // The one read in this family that no invalidation could repair, and the
        // button's problem exactly: `__()` resolves against the request's locale, so
        // a folded call would serve whichever locale compiled the view to everyone
        // after it. The island is what keeps the lookup.
        $compiled = Blade::compileString('<shape:header.nav>Links</shape:header.nav>');

        expect($compiled)
            ->toContain('[BlazeFolded]:{shape::header.nav}')
            ->toContain("__('shape::messages.header.nav')")
            ->not->toContain(__('shape::messages.header.nav'));
    });

    it('follows a locale change on a nav it has already folded', function () {
        // The claim a single render cannot support: a frozen label satisfies every
        // assertion about the first render and fails only on the second.
        app('translator')->addLines(['messages.header.nav' => 'Navigation principale'], 'fr', 'shape');

        $english = Blade::render('<shape:header.nav>Links</shape:header.nav>');

        app()->setLocale('fr');

        expect($english)->toContain('aria-label="Main"')
            ->and(Blade::render('<shape:header.nav>Links</shape:header.nav>'))
            ->toContain('aria-label="Navigation principale"');
    });

    it('leaves the landmark name where it was for a nav that named its own', function () {
        // The package's default and a call site's own value no longer reach the
        // element by the same route -- theirs is baked with the bag, ours is looked
        // up per render -- so the attribute's position had to be put back by hand.
        // Asserted because the alternative is a silent diff in the markup of every
        // application that names one.
        expect(Blade::render('<shape:header.nav aria-label="Utilities">Links</shape:header.nav>'))
            ->toContain('<nav aria-label="Utilities" class=');
    });
});

it('folds the legend, which inherits nothing and so needs no field to see it', function () {
    // The one member of the family with no `@aware` at all, which is what lets it
    // fold standing on its own rather than only inside a folded field. Everything it
    // decides comes from one prop and a closed table.
    expect(Blade::compileString('<shape:legend>Plan</shape:legend>'))
        ->toContain('[BlazeFolded]:{shape::legend}')
        ->toContain('<legend class="mb-1.5 text-sm font-medium text-ink">')
        ->not->toContain('renderComponent');
});

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
    // This block used to assert the opposite of what it asserts now, and the
    // inversion is the point rather than a regression to be embarrassed about. A
    // labelled control that nothing named took the next number off a process-wide
    // counter, which gave a loop three ids -- but only because the component was
    // re-entered per row. Folded, the counter is read once and every row carries the
    // same number, and *which* number depends on what the application happened to
    // compile first. So the counter went, and the id is derived from the label.
    //
    // What is asserted here is what that bought and what it cost, in that order.

    // These render rather than compile, and some of these controls draw a mark of
    // their own -- the select its chevron, the checkbox its tick. The file's own
    // hook publishes only what the folding tests need, so this asks for the set the
    // components require, which is read from the alias table rather than listed
    // here: a control that starts drawing something new arrives in this test on its
    // own.
    beforeEach(fn () => publishRequiredIcons());

    it('derives the same id for every iteration', function (string $tag) {
        // The cost, stated plainly. Three unnamed controls with one label between
        // them are one id, which is what three controls *named* the same way have
        // always been -- and a labelled control with no name, no binding and no id
        // submits nothing whether or not its id is unique. See docs/performance.md.
        $html = Blade::render('@foreach ([1, 2, 3] as $row)'.$tag.'@endforeach');

        preg_match_all('/id="(shape-field-[\w-]+)"/', $html, $matches);

        expect($matches[1])->toHaveCount(3)
            ->and(array_unique($matches[1]))->toHaveCount(1);
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

    it('gives every iteration of a named control an id of its own', function () {
        // The case that matters and the one that did not change: a control with a
        // name derives from the name, and a loop of *different* names is a loop of
        // different ids however the component was compiled.
        $html = Blade::render('@foreach ([1, 2, 3] as $row)<shape:input label="Email" name="row-{{ $row }}" />@endforeach');

        preg_match_all('/id="([^"]+)"/', $html, $matches);

        expect($matches[1])->toBe(['row-1', 'row-2', 'row-3']);
    });

    it('points every label at a control that is on the page', function () {
        // The id exists so a `<label>` has something to name, so the pairing is the
        // part worth asserting whichever way the ids come out: three `for` values,
        // each naming an id something rendered.
        $html = Blade::render('@foreach ([1, 2, 3] as $row)<shape:input label="Email" />@endforeach');

        preg_match_all('/for="([^"]+)"/', $html, $labels);
        preg_match_all('/id="([^"]+)"/', $html, $controls);

        expect($labels[1])->toHaveCount(3)
            ->and($labels[1])->toBe($controls[1]);
    });

    it('answers the same whether the row folded or not', function () {
        // What deriving bought. The counter made a compiled view depend on the order
        // the application compiled its templates in; a label does not, so the folded
        // call site and the one that declined agree -- which is also what makes the
        // benchmark's byte-for-byte comparison across modes mean anything.
        $folded = Blade::render('<shape:input label="Email" />');
        $declined = Blade::render('<shape:input :label="\'Email\'" />');

        preg_match('/id="([^"]+)"/', $folded, $a);
        preg_match('/id="([^"]+)"/', $declined, $b);

        expect($a[1])->toBe('shape-field-email')->and($b[1])->toBe($a[1]);
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

    // Snapshotted before the flush and put back after it, which is the difference
    // between simulating a cold process and poisoning the one this suite is running
    // in. `BladeRenderer::render()` reaches its scratch file with `require_once`, so
    // a path included once in a process keeps the function body it was included
    // with -- and the hook above deletes that directory between tests, which makes
    // every later fold recompile the file, mint fresh tokens, and then run the old
    // body emitting the old ones. Those still resolve while the map holds them.
    // Flushed and left flushed, they do not, and every island in the file starts
    // declining depending on the order the tests happened to run in.
    $replacements = Unblaze::$unblazeReplacements;
    $scopes = Unblaze::$unblazeScopes;

    // What a new process sees: the scratch file still on disk, the token map empty.
    Unblaze::flushState();

    expect(Blade::compileString('<shape:button loading>Publish</shape:button>'))
        ->not->toContain('[BlazeFolded]')
        ->toContain('$__blaze');

    // The button with no island in its output is untouched by any of it.
    expect(Blade::compileString('<shape:button>Save</shape:button>'))
        ->toContain('[BlazeFolded]');

    Unblaze::$unblazeReplacements = $replacements;
    Unblaze::$unblazeScopes = $scopes;
});
