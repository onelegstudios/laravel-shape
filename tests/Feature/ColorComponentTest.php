<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input>` alone, out of the field the shorthand builds around it. Named apart
 * from the sibling suites' extractors because Pest puts every module-level function
 * in one namespace.
 */
function swatch(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a colour input that is its own box', function () {
    // It keeps the input's frame -- a pale colour on a pale page has no boundary of
    // its own -- but there is no wrapper around it to carry one.
    $html = Blade::render('<shape:color />');

    expect($html)
        ->toContain('<input')
        ->toContain('type="color"')
        ->not->toContain('<div');

    expect(swatch($html))
        ->toContain('rounded-md')
        ->toContain('border-neutral-border')
        ->toContain('bg-surface');
});

it('draws the swatch itself rather than leaving the operating system\'s well', function () {
    expect(swatch(Blade::render('<shape:color />')))->toContain('appearance-none');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:color />'))->toBe(Blade::render('<shape:color size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:color size="huge" />'))->toBe(Blade::render('<shape:color />'));
});

it('gives each rung a square at the height of the field beside it', function (string $size, string $square) {
    expect(swatch(Blade::render('<shape:color size="'.$size.'" />')))->toContain($square);
})->with([
    'xs is 26, what a table row can afford' => ['xs', 'size-6.5'],
    'sm is 34, a toolbar' => ['sm', 'size-8.5'],
    'md is 38, the form field' => ['md', 'size-9.5'],
    'lg is 46, a screen with one question on it' => ['lg', 'size-11.5'],
]);

it('stands as tall as the field beside it', function (string $size) {
    // The same claim the slider makes, and the same arithmetic behind it. Everything
    // is in spacing units, Tailwind's 4px: an input's height is its padding plus its
    // line box plus two borders, and a swatch states the total outright.
    $lines = ['text-xs' => 4, 'text-sm' => 5, 'text-base' => 6];
    $border = 0.5;

    $field = Blade::render('<shape:input size="'.$size.'" />');

    preg_match('/\bpy-([\d.]+)\b/', $field, $padding);
    preg_match('/\btext-(?:xs|sm|base)\b/', $field, $type);
    preg_match('/\bsize-([\d.]+)\b/', swatch(Blade::render('<shape:color size="'.$size.'" />')), $chip);

    expect((float) $chip[1])->toBe((float) $padding[1] * 2 + $lines[$type[0]] + $border);
})->with(['xs', 'sm', 'md', 'lg']);

it('is square rather than stretched across the form', function () {
    // A field stretches because what it holds has no length you can predict; this
    // one holds a colour, which has no length at all. Stretched, it is a band of
    // saturated colour carrying more weight than the question it answers.
    expect(swatch(Blade::render('<shape:color />')))->not->toContain('w-full');
});

it('clears the padding Chromium puts on both the input and the wrapper inside it', function () {
    // Two separate insets, and both have to go for the colour to meet the border.
    // `p-0` on the input does not reach `::-webkit-color-swatch-wrapper`.
    expect(swatch(Blade::render('<shape:color />')))
        ->toContain(' p-0')
        ->toContain('swatch-wrapper:p-0');
});

it('clears the hairline both engines draw around the colour itself', function () {
    // A second edge inside the one this component already gives it.
    expect(swatch(Blade::render('<shape:color />')))->toContain('swatch:border-0');
});

it('rounds the colour one step tighter than the box around it', function () {
    // So the border stays visible in the corners instead of being swallowed by a
    // colour rounded to the same radius.
    expect(swatch(Blade::render('<shape:color />')))
        ->toContain('rounded-md')
        ->toContain('swatch:rounded ');
});

it('shows no hex beside the colour', function () {
    // Reading the value back into text takes JavaScript, and this library ships
    // none. A `<shape:input>` bound to the same model is the way to show it, which
    // is a call site's decision rather than this component's.
    expect(Blade::render('<shape:color value="#4f46e5" />'))
        ->not->toContain('>#4f46e5<')
        ->and(swatch(Blade::render('<shape:color value="#4f46e5" />')))->toContain('value="#4f46e5"');
});

it('draws no icon, because the control is already a mark', function () {
    // A swatch with a glyph beside it inside the same box would be two things
    // claiming to say what the field holds.
    File::deleteDirectory(TestCase::iconPath());

    expect(Blade::render('<shape:color />'))
        ->not->toContain('<svg')
        ->toContain('type="color"');
});

it('does not leak the styling props onto the rendered element', function () {
    $html = Blade::render('<shape:color size="lg" :invalid="false" />');

    expect(swatch($html))
        ->not->toContain('size="lg"')
        ->not->toContain('invalid');
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.color', ['size' => 'lg']);

        expect(Blade::render('<shape:color />'))->toBe(Blade::render('<shape:color size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:color />');

        config()->set('shape.components.color', null);

        expect(Blade::render('<shape:color />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unsized swatch', function (mixed $configured) {
        config()->set('shape.components.color', $configured);

        expect(swatch(Blade::render('<shape:color />')))->toContain('size-9.5');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);

    it('takes its rung from its own key rather than the input\'s', function () {
        config()->set('shape.components.input', ['size' => 'xs']);

        expect(Blade::render('<shape:color />'))->toBe(Blade::render('<shape:color size="md" />'));
    });
});

describe('attributes', function () {
    it('puts the class on the control, because the control is the box', function () {
        expect(swatch(Blade::render('<shape:color class="mt-1" />')))->toContain('mt-1');
    });

    it('hands everything else to the control untouched', function () {
        expect(swatch(Blade::render('<shape:color name="brand" required list="palette" />')))
            ->toContain('name="brand"')
            ->toContain('required')
            ->toContain('list="palette"');
    });

    it('lets the call site mean something else by the type', function () {
        expect(swatch(Blade::render('<shape:color type="text" />')))
            ->toContain('type="text"')
            ->not->toContain('type="color"');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(swatch(Blade::render('<shape:color '.$binding.'="brand" />')))
            ->toContain($binding.'="brand"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so on the border', function () {
        seedErrors(['brand' => ['Pick a colour.']]);

        expect(swatch(Blade::render('<shape:color name="brand" />')))
            ->toContain('border-danger-border')
            ->toContain('aria-invalid="true"');
    });

    it('rings in danger as well as bordering in it', function () {
        seedErrors(['brand' => ['Pick a colour.']]);

        expect(swatch(Blade::render('<shape:color name="brand" />')))
            ->toContain('focus-visible:outline-danger-ring');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(swatch(Blade::render('<shape:color name="brand" :invalid="true" />')))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['brand' => ['Pick a colour.']]);

        expect(swatch(Blade::render('<shape:color name="brand" :invalid="false" />')))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(swatch(Blade::render('<shape:color name="brand" />')))->toContain('border-neutral-border');
    });

    it('styles a bare swatch but leaves the message to the markup around it', function () {
        seedErrors(['brand' => ['Pick a colour.']]);

        expect(Blade::render('<shape:color name="brand" />'))
            ->toContain('aria-invalid="true"')
            ->not->toContain('Pick a colour.');
    });
});

describe('shorthand', function () {
    // The shorthand renders the message, and the message renders a mark.
    beforeEach(function () {
        publishRequiredIcons();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('expands a label into a field, a control and a message', function () {
        seedErrors(['brand' => ['Pick a colour.']]);

        $html = Blade::render('<shape:color label="Brand colour" name="brand" />');

        expect($html)
            ->toContain('<label')
            ->toContain('Brand colour')
            ->toContain('type="color"')
            ->toContain('Pick a colour.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:color label="Brand" name="theme.brand" />');

        expect($html)
            ->toContain('for="theme-brand"')
            ->and(swatch($html))->toContain('id="theme-brand"');
    });

    it('describes the control with the ids it actually rendered', function () {
        $html = Blade::render('<shape:color label="Brand" description="Used for buttons and links." name="brand" />');

        expect(swatch($html))->toContain('aria-describedby="brand-description"')
            ->and($html)->toContain('id="brand-description"');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:color name="brand" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });

    it('takes its name from the field around it', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="brand">
                <shape:label>Brand</shape:label>
                <shape:color />
            </shape:field>
        BLADE);

        expect($html)->toContain('for="brand"')
            ->and(swatch($html))->toContain('id="brand"');
    });
});

describe('disabled', function () {
    it('dims the swatch and the box together', function () {
        expect(swatch(Blade::render('<shape:color disabled />')))
            ->toContain('disabled')
            ->toContain('disabled:opacity-50')
            ->toContain('disabled:cursor-not-allowed');
    });

    it('offers the pointer the rest of the time', function () {
        expect(swatch(Blade::render('<shape:color />')))->toContain('cursor-pointer');
    });

    it('rings on the keyboard rather than on every click', function () {
        // The control is the box, and there is nothing here to type into -- so
        // `focus-visible` rather than the input's `focus-within`. Same call the
        // switch makes.
        expect(swatch(Blade::render('<shape:color />')))
            ->toContain('focus-visible:outline-2')
            ->not->toContain('focus-within:');
    });
});
