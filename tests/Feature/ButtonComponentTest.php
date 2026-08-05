<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * What the button put inside the wrapper it hides while loading -- its label and any
 * icons, but not the spinner, which is the wrapper's sibling. Asserting on this
 * rather than on the whole button is the difference between "the spinner rendered"
 * and "the spinner is the only thing still visible".
 */
function labelWrapper(string $html): string
{
    preg_match('/<span class="contents[^"]*">(.*?)<\/span>/s', $html, $matches);

    return $matches[1] ?? '';
}

it('defaults to the quiet button so the primary action stays opt-in', function () {
    $html = Blade::render('<shape:button>Cancel</shape:button>');

    expect($html)
        ->toContain('border-neutral-border')
        ->not->toContain('bg-primary-fill');
});

it('renders each step of the emphasis ladder differently', function (string $variant, string $expected) {
    $html = Blade::render('<shape:button variant="'.$variant.'" color="primary">Save</shape:button>');

    expect($html)->toContain($expected);
})->with([
    'solid fills the surface' => ['solid', 'bg-primary-fill text-primary-on-fill'],
    'soft tints it' => ['soft', 'bg-primary-tint text-primary-on-tint'],
    'ghost stays transparent' => ['ghost', 'text-primary-on-tint hover:bg-primary-tint'],
    'outline is the only bordered step' => ['outline', 'border-primary-border'],
]);

it('renders each rung of the size scale differently', function (string $size, string $expected) {
    $html = Blade::render('<shape:button size="'.$size.'">Save</shape:button>');

    expect($html)->toContain($expected);
})->with([
    'xs is the densest rung' => ['xs', 'gap-1 px-2 py-1 text-xs'],
    'sm suits a toolbar' => ['sm', 'gap-1.5 px-3 py-1.5 text-sm'],
    'md is the form default' => ['md', 'gap-2 px-4 py-2 text-sm'],
    'lg is the only one that grows the text' => ['lg', 'gap-2.5 px-5 py-2.5 text-base'],
]);

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:button>Save</shape:button>'))
        ->toBe(Blade::render('<shape:button size="md">Save</shape:button>'));
});

it('falls back to the default size when given one it does not have', function () {
    // Sizes are a closed set for the same reason variants are: four rungs, no way
    // to add a fifth, so an unknown one is a typo rather than an extension point.
    expect(Blade::render('<shape:button size="huge">Save</shape:button>'))
        ->toBe(Blade::render('<shape:button>Save</shape:button>'));
});

it('composes size with variant and colour', function () {
    // The three axes are independent: nothing about being small should make a
    // button quieter, and nothing about being loud should make it bigger.
    $html = Blade::render('<shape:button size="xs" variant="solid" color="danger">Delete</shape:button>');

    expect($html)
        ->toContain('px-2 py-1 text-xs')
        ->toContain('bg-danger-fill')
        ->toContain('font-semibold');
});

it('keeps the radius out of the size scale so one token restyles every rung', function (string $size) {
    // Radius is the component's shape, not its size -- a consumer overriding
    // --radius-md gets all four rungs, not the one that happened to name it.
    expect(Blade::render('<shape:button size="'.$size.'">Save</shape:button>'))
        ->toContain('rounded-md');
})->with(['xs', 'sm', 'md', 'lg']);

it('names only semantic colour roles, never raw palette hues', function (string $color) {
    $html = Blade::render('<shape:button color="'.$color.'">Save</shape:button>');

    expect($html)->toContain($color.'-');
})->with(['primary', 'success', 'warning', 'danger', 'info', 'neutral']);

it('composes variant and colour independently', function () {
    $html = Blade::render('<shape:button variant="outline" color="danger">Delete</shape:button>');

    expect($html)
        ->toContain('border-danger-border')
        ->toContain('text-danger-on-tint')
        ->not->toContain('bg-danger-fill');
});

it('falls back to the default variant when given one it does not have', function () {
    // Variants are a closed set -- there are four rungs on the ladder and no way
    // for a consumer to add a fifth -- so an unknown one is a typo.
    expect(Blade::render('<shape:button variant="nope">Save</shape:button>'))
        ->toBe(Blade::render('<shape:button>Save</shape:button>'));
});

it('styles a colour role the package does not ship', function () {
    // The point of the open colour set: a consumer defines `accent` (or anything
    // else) in their own theme and the component follows without being taught it.
    $html = Blade::render('<shape:button variant="solid" color="accent">Save</shape:button>');

    expect($html)
        ->toContain('bg-accent-fill')
        ->toContain('text-accent-on-fill')
        ->toContain('hover:bg-accent-fill-hover')
        ->toContain('focus-visible:outline-accent-ring');
});

it('refuses a colour that is not shaped like a CSS identifier', function (string $color) {
    // Nothing here can know which roles a consumer defined, so the only guard left
    // is the shape of the name -- which is what stops an interpolated value from
    // smuggling extra classes onto the element.
    expect(Blade::render('<shape:button color="'.$color.'">Save</shape:button>'))
        ->toBe(Blade::render('<shape:button>Save</shape:button>'));
})->with([
    'a smuggled class' => ['neutral hidden'],
    'an arbitrary value' => ['[#bada55]'],
    'a variant prefix' => ['primary md:bg-red-500'],
    'empty' => [''],
    'uppercase' => ['Primary'],
]);

it('merges consumer classes alongside the recipe', function () {
    $html = Blade::render('<shape:button variant="soft" color="primary" class="w-full">Save</shape:button>');

    expect($html)
        ->toContain('bg-primary-tint')
        ->toContain('w-full');
});

it('does not leak the styling props onto the rendered element', function () {
    $html = Blade::render('<shape:button variant="ghost" color="info" size="lg">Save</shape:button>');

    expect($html)
        ->not->toContain('variant="ghost"')
        ->not->toContain('color="info"')
        ->not->toContain('size="lg"');
});

it('still honours a caller-supplied type attribute', function () {
    $html = Blade::render('<shape:button type="submit">Save</shape:button>');

    expect($html)
        ->toContain('type="submit"')
        ->not->toContain('type="button"');
});

it('takes the value of every unnamed prop from config', function () {
    // The point of configurable defaults: an application states its house style
    // once and an unadorned button follows, without a call site being touched.
    config()->set('shape.components.button', [
        'variant' => 'solid',
        'color' => 'primary',
        'size' => 'lg',
    ]);

    expect(Blade::render('<shape:button>Save</shape:button>'))
        ->toBe(Blade::render('<shape:button variant="solid" color="primary" size="lg">Save</shape:button>'));
});

it('lets a call site override the configured default', function () {
    // Config moves the starting point; it does not take the choice away.
    config()->set('shape.components.button', [
        'variant' => 'solid',
        'color' => 'primary',
        'size' => 'lg',
    ]);

    expect(Blade::render('<shape:button variant="ghost" color="danger" size="xs">Delete</shape:button>'))
        ->toContain('text-danger-on-tint')
        ->toContain('px-2 py-1 text-xs')
        ->not->toContain('bg-primary-fill');
});

it('ships config defaults that match the fallbacks baked into the component', function () {
    // The component repeats the packaged defaults as a floor, which makes them
    // two copies of one fact -- and two copies drift. Rendering with the shipped
    // config and then with no config at all has to come out identical.
    $configured = Blade::render('<shape:button>Save</shape:button>');

    config()->set('shape.components.button', null);

    expect(Blade::render('<shape:button>Save</shape:button>'))->toBe($configured);
});

it('falls back to a packaged default rather than rendering an unstyled button', function (mixed $configured) {
    // Laravel merges package config one level deep, so a published file that has
    // gone stale is not a hypothetical: drop a key and nothing restores it. What
    // must not happen is a prop resolving to nothing and a class landing empty.
    config()->set('shape.components.button', $configured);

    expect(Blade::render('<shape:button>Save</shape:button>'))
        ->toContain('border-neutral-border')
        ->toContain('gap-2 px-4 py-2 text-sm');
})->with([
    'the whole block removed' => [null],
    'an empty block' => [[]],
    'a block missing every key' => [['unrelated' => 'value']],
    'a partial block' => [['variant' => 'outline']],
    'a value of the wrong type' => [['variant' => ['solid'], 'color' => 3, 'size' => ['lg']]],
    'a block that is not an array' => ['solid'],
]);

it('renders nothing extra when the loading prop is absent', function () {
    // The state is the exception, not the shape of every button: an ordinary call
    // site should come out of this component exactly as it did before it existed,
    // and should not need an icon published to render at all.
    expect(Blade::render('<shape:button>Save</shape:button>'))
        ->not->toContain('aria-busy')
        ->not->toContain('relative')
        ->toContain('disabled:opacity-50');
});

describe('loading', function () {
    // The spinner is a published icon rather than an SVG this package reads, so
    // it has to exist on disk before the loading branch can render -- the same
    // setup the icon component's own tests do, scoped here so the rest of the
    // file does not pay for it.
    beforeEach(function () {
        File::deleteDirectory(TestCase::iconPath());

        $this->artisan('shape:icon:add', ['name' => ['spinner'], '--no-clear' => true])->run();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('shows a spinning icon in place of the label', function () {
        $html = Blade::render('<shape:button loading>Save changes</shape:button>');

        expect($html)
            ->toContain('<svg')
            ->toContain('animate-spin');
    });

    it('keeps the label in the layout so the button does not change width', function () {
        // Hidden rather than removed: a button that shrank to its spinner would
        // reflow the row it sits in at the exact moment a form was submitting.
        // `contents` is what makes that exact -- a wrapper that generated a box
        // would swallow the rung's gap and cost the button those few pixels.
        $html = Blade::render('<shape:button loading>Save changes</shape:button>');

        expect($html)->toContain('class="contents invisible"');

        expect(labelWrapper($html))->toContain('Save changes');
    });

    it('disables itself and says why', function () {
        $html = Blade::render('<shape:button loading>Save changes</shape:button>');

        expect($html)
            ->toContain('disabled="disabled"')
            ->toContain('aria-busy="true"');
    });

    it('announces a name once the label is hidden from assistive tech', function () {
        // `visibility: hidden` takes the label out of the accessibility tree, so
        // without this the button would announce as nothing at all.
        expect(Blade::render('<shape:button loading>Save changes</shape:button>'))
            ->toContain('aria-label="Loading"');
    });

    it('keeps the spinner at full contrast rather than taking the disabled fade', function () {
        // The button is disabled while it loads, but a half-transparent spinner
        // reads as a component that has given up rather than one that is working.
        expect(Blade::render('<shape:button loading>Save changes</shape:button>'))
            ->not->toContain('disabled:opacity-50');
    });

    it('sizes the spinner to the rung the button resolved', function (string $size, string $expected) {
        expect(Blade::render('<shape:button size="'.$size.'" loading>Save</shape:button>'))
            ->toContain($expected);
    })->with([
        'xs takes the smallest icon' => ['xs', 'size-3.5'],
        'sm matches a toolbar' => ['sm', 'size-4'],
        'md is the form default' => ['md', 'size-5'],
        'lg is the largest' => ['lg', 'size-6'],
        'a size the scale does not have falls back with the button' => ['huge', 'size-5'],
    ]);

    it('reads a stringified false as not loading', function () {
        // A call site passing a variable through a template can hand this a string
        // rather than a boolean, and "false" must not read as busy.
        expect(Blade::render('<shape:button loading="false">Save</shape:button>'))
            ->toBe(Blade::render('<shape:button>Save</shape:button>'));
    });

    it('does not leak the loading prop onto the rendered element', function () {
        expect(Blade::render('<shape:button loading>Save</shape:button>'))
            ->not->toContain('loading="');
    });

    it('composes with the styling props', function () {
        // Loading is a state, not a fourth axis: it does not make a button quieter,
        // louder, or a different size.
        $html = Blade::render('<shape:button variant="solid" color="danger" size="sm" loading>Delete</shape:button>');

        expect($html)
            ->toContain('bg-danger-fill')
            ->toContain('px-3 py-1.5 text-sm')
            ->toContain('animate-spin');
    });
});

describe('icon', function () {
    // Same setup as the loading block, and for the same reason: an icon prop names
    // a published component, so the artwork has to be on disk before any of this
    // renders. `spinner` is here too, for the tests where the two features meet.
    beforeEach(function () {
        File::deleteDirectory(TestCase::iconPath());

        $this->artisan('shape:icon:add', [
            'name' => ['check', 'chevron-down', 'spinner'],
            '--no-clear' => true,
        ])->run();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('puts the icon before the label', function () {
        // Position is asserted rather than presence: an icon after the words it
        // introduces is a different component from the one this prop describes.
        $html = Blade::render('<shape:button icon="check">Save</shape:button>');

        expect(strpos($html, '<svg'))->toBeLessThan(strpos($html, 'Save'));
    });

    it('puts a trailing icon after the label', function () {
        $html = Blade::render('<shape:button icon-trailing="chevron-down">More</shape:button>');

        expect(strpos($html, '<svg'))->toBeGreaterThan(strpos($html, 'More'));
    });

    it('carries a leading and a trailing icon at once', function () {
        // The two props are independent, which is what a split button or a menu
        // trigger with a mark of its own needs.
        $html = Blade::render('<shape:button icon="check" icon-trailing="chevron-down">New</shape:button>');

        expect(substr_count($html, '<svg'))->toBe(2);
    });

    it('sizes the icon to the rung the button resolved', function (string $size, string $expected) {
        // The whole reason this is a prop: the rung is resolved once and the icon
        // is handed it, so a call site cannot change one and forget the other.
        expect(Blade::render('<shape:button size="'.$size.'" icon="check">Save</shape:button>'))
            ->toContain($expected);
    })->with([
        'xs takes the smallest icon' => ['xs', 'size-3.5'],
        'sm matches a toolbar' => ['sm', 'size-4'],
        'md is the form default' => ['md', 'size-5'],
        'lg is the largest' => ['lg', 'size-6'],
        'a size the scale does not have falls back with the button' => ['huge', 'size-5'],
    ]);

    it('leaves the icon on the button colour rather than giving it one', function () {
        // No colour class means `currentColor`, which is how one recipe dresses the
        // icon correctly in every variant without the icon knowing which it is in.
        expect(Blade::render('<shape:button variant="solid" color="danger" icon="check">Delete</shape:button>'))
            ->not->toContain('text-danger-on-tint');
    });

    it('hides the icon from assistive tech when a label sits beside it', function () {
        // The words already say what the button does; announcing the mark as well
        // would say it twice.
        expect(Blade::render('<shape:button icon="check">Save</shape:button>'))
            ->toContain('aria-hidden="true"')
            ->not->toContain('role="img"');
    });

    it('takes both icons from a named set', function () {
        config()->set('shape.icons.sets', ['fixture' => 'fixture']);

        $this->artisan('shape:icon:add', [
            'name' => ['check', 'cross'],
            '--set' => 'fixture',
            '--no-clear' => true,
        ])->run();

        $html = Blade::render('<shape:button icon="check" icon-trailing="cross" icon-set="fixture">Save</shape:button>');

        expect($html)
            ->toContain('data-fixture="check"')
            ->toContain('data-fixture="cross"');
    });

    it('reads the kebab-cased props a call site actually writes', function () {
        // Blade camelizes anonymous-component data and Blaze's props compiler reads
        // both spellings, but the button is compiled by Blaze rather than Blade, so
        // the path that matters here is the one no other prop on this component
        // exercises -- every other prop is a single word.
        expect(Blade::render('<shape:button icon-trailing="chevron-down">More</shape:button>'))
            ->toContain('<svg');
    });

    it('does not leak the icon props onto the rendered element', function () {
        $html = Blade::render('<shape:button icon="check" icon-trailing="chevron-down" icon-set="default">Save</shape:button>');

        expect($html)
            ->not->toContain('icon="')
            ->not->toContain('icon-trailing="')
            ->not->toContain('icon-set="');
    });

    it('ignores an icon prop that does not name one', function (string $tag) {
        // A bare `<shape:button icon>` arrives as `true` and would send the icon
        // component looking for artwork named "1", failing loudly for a call site
        // that plainly meant nothing by it.
        expect(Blade::render($tag))->toBe(Blade::render('<shape:button>Save</shape:button>'));
    })->with([
        'a valueless attribute' => ['<shape:button icon>Save</shape:button>'],
        'an empty name' => ['<shape:button icon="">Save</shape:button>'],
        'an empty trailing name' => ['<shape:button icon-trailing="">Save</shape:button>'],
    ]);

    it('hides an icon with the label rather than leaving it behind the spinner', function () {
        // Label and icons share the wrapper the loading state hides, so the button
        // keeps the width its icon and gap gave it and the spinner is alone.
        $html = Blade::render('<shape:button loading icon="check">Save</shape:button>');

        expect(labelWrapper($html))
            ->toContain('Save')
            ->toContain('<svg');

        expect($html)->toContain('animate-spin');
    });

    describe('with no label', function () {
        it('squares up to the height the labelled rung already stands at', function (string $size, string $expected) {
            // An icon button and a text button of the same rung line up in a
            // toolbar row, which is the only thing this second scale buys. The
            // padding is not the rung's own `py`: it closes the gap between the
            // icon's height and the line-height a label would have had.
            expect(Blade::render('<shape:button size="'.$size.'" icon="check" />'))
                ->toContain($expected);
        })->with([
            'xs holds the WCAG 2.5.8 floor at 24px' => ['xs', 'p-1.25'],
            'sm suits a toolbar at 32px' => ['sm', 'p-2'],
            'md is the form default at 36px' => ['md', 'p-2'],
            'lg is a thumb target at 44px' => ['lg', 'p-2.5'],
        ]);

        it('pads on both axes so the button comes out square', function () {
            // A one-sided `py` here would leave the icon in a wide pill, which is
            // the shape this branch exists to avoid.
            $html = Blade::render('<shape:button icon="check" />');

            expect($html)
                ->not->toContain('px-4')
                ->not->toContain('py-2')
                ->not->toContain('gap-2');
        });

        it('reads a slot of nothing but whitespace as no label', function () {
            // Which is how anyone who indents their Blade writes one.
            $html = Blade::render("<shape:button icon=\"check\">\n    \n</shape:button>");

            expect($html)->toContain('p-2');
        });

        it('keeps the padded scale as soon as there is a label to pad', function () {
            expect(Blade::render('<shape:button icon="check">Save</shape:button>'))
                ->toContain('px-4 py-2');
        });

        it('stays padded for a button with no icon and no label', function () {
            // An empty button is not an icon button, and squaring one up would be
            // this component guessing at a call site that has said nothing.
            expect(Blade::render('<shape:button />'))
                ->toContain('px-4 py-2');
        });

        it('carries the name the call site gave it', function () {
            // The accessible name belongs on the button rather than on the mark
            // inside it, so it arrives the way every other attribute does.
            expect(Blade::render('<shape:button icon="check" aria-label="Save" />'))
                ->toContain('aria-label="Save"');
        });
    });
});
