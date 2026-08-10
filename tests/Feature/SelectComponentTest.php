<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<select>` alone, out of the box the wrapper draws around it. Named apart
 * from the sibling suites' extractors because Pest puts every module-level
 * function in one namespace.
 */
function dropdown(string $html): string
{
    preg_match('/<select\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

// Every render here draws a chevron, so the artwork has to be on disk first: an
// icon that is not published is a view that throws rather than a test that fails
// for the reason it meant to.
beforeEach(function () {
    publishRequiredIcons();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('renders a select inside the box that carries its border', function () {
    $html = Blade::render('<shape:select><option>One</option></shape:select>');

    expect($html)
        ->toContain('<select')
        ->toContain('<option>One</option>')
        ->toContain('border-neutral-border')
        ->toContain('bg-surface');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:select />'))
        ->toBe(Blade::render('<shape:select size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:select size="huge" />'))
        ->toBe(Blade::render('<shape:select />'));
});

it('gives each rung its own box, its own type and its own room for the mark', function (string $size, string $box, string $type, string $clear) {
    $html = Blade::render('<shape:select size="'.$size.'" />');

    expect($html)->toContain($box)
        ->and(dropdown($html))->toContain($type)->toContain($clear);
})->with([
    'xs' => ['xs', 'px-2 py-1', 'text-xs', 'pe-4.5'],
    'sm' => ['sm', 'px-3 py-1.5', 'text-sm', 'pe-5.5'],
    'md' => ['md', 'px-4 py-2', 'text-sm', 'pe-7'],
    'lg' => ['lg', 'px-5 py-2.5', 'text-base', 'pe-8.5'],
]);

it('leaves exactly the room the mark beside it takes up', function (string $size, string $icon, string $clear) {
    // Not two independent tables that happen to agree: the room left is the mark's
    // own width plus the gap the input's flex rung holds a mark off its value by.
    // Both halves are read here so the claim breaks loudly if either moves.
    $html = Blade::render('<shape:select size="'.$size.'" />');

    expect($html)->toContain($icon)
        ->and(dropdown($html))->toContain($clear);
})->with([
    'xs leaves 14 plus 4' => ['xs', 'size-3.5', 'pe-4.5'],
    'sm leaves 16 plus 6' => ['sm', 'size-4', 'pe-5.5'],
    'md leaves 20 plus 8' => ['md', 'size-5', 'pe-7'],
    'lg leaves 24 plus 10' => ['lg', 'size-6', 'pe-8.5'],
]);

it('takes the input\'s own padding at every rung', function (string $size) {
    preg_match('/(px-[\d.]+ py-[\d.]+)/', Blade::render('<shape:select size="'.$size.'" />'), $select);
    preg_match('/px-([\d.]+) py-([\d.]+)/', Blade::render('<shape:input size="'.$size.'" />'), $input);

    expect($select[1])->toBe('px-'.$input[1].' py-'.$input[2]);
})->with(['xs', 'sm', 'md', 'lg']);

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:select size="lg" icon-set="default" :multiple="false" :invalid="false" />');

    expect($html)
        ->not->toContain('size="lg"')
        ->not->toContain('icon-set=')
        ->not->toContain('invalid=')
        ->not->toContain('multiple');
});

describe('the click target', function () {
    // The whole reason this component does not use the input's flex row. A mark
    // that is a flex sibling owns a column, so a `pointer-events-none` icon leaves
    // the last twenty pixels of the box dead -- which is exactly where everybody
    // clicks to open a select.

    it('stacks the control and the mark in one cell', function () {
        $html = Blade::render('<shape:select />');

        expect($html)->toContain('grid ')->toContain('grid-cols-1')
            ->and(dropdown($html))->toContain('col-start-1 row-start-1');
    });

    it('puts the mark in the same cell as the control rather than beside it', function () {
        // Two elements, one cell, so the control fills the box underneath the mark.
        expect(substr_count(Blade::render('<shape:select />'), 'col-start-1 row-start-1'))->toBe(2);
    });

    it('hands a click on the mark through to the control underneath', function () {
        expect(Blade::render('<shape:select />'))->toContain('pointer-events-none');
    });

    it('centres each mark on itself rather than centring the box', function () {
        // A list box is taller than one line, so `items-center` on the frame would
        // drag the control's own text to the middle of it.
        $html = Blade::render('<shape:select />');

        expect($html)->toContain('self-center')
            ->not->toContain('items-center');
    });
});

describe('the native chrome', function () {
    it('takes the operating system\'s arrow away so ours is the only one', function () {
        expect(dropdown(Blade::render('<shape:select />')))->toContain('appearance-none');
    });

    it('removes the chevron @tailwindcss/forms would paint on', function () {
        // That plugin's `base` mode paints its own chevron as a background-image
        // data URI, in a hardcoded grey that follows no theme and no colour scheme.
        // A consumer who has it installed for the rest of their application would
        // otherwise get two chevrons here, one of them the wrong colour.
        expect(dropdown(Blade::render('<shape:select />')))
            ->toContain('bg-none')
            ->toContain('bg-transparent')
            ->toContain('border-0');
    });

    it('does not rest its padding on Tailwind\'s utility sort', function () {
        // Four explicit sides rather than `p-0` plus `pe-*`. Both would apply and
        // the sort would resolve it correctly today, which is not the same as
        // reading correctly.
        expect(dropdown(Blade::render('<shape:select />')))
            ->toContain('py-0')
            ->not->toContain(' p-0');
    });
});

describe('marks', function () {
    it('draws the chevron from the semantic name rather than a glyph', function () {
        // The two libraries Shape can install do not agree on it: `chevrons-up-down`
        // in Lucide, `chevron-up-down` in Heroicons.
        expect(Blade::render('<shape:select />'))->toContain('<svg');
    });

    it('sizes the chevron to the rung the field resolved', function () {
        expect(Blade::render('<shape:select size="xs" />'))->toContain('size-3.5')
            ->and(Blade::render('<shape:select size="lg" />'))->toContain('size-6');
    });

    it('keeps the chevron out of the accessibility tree', function () {
        // It is a picture of what the element already announces itself to be.
        expect(Blade::render('<shape:select />'))->toContain('aria-hidden="true"');
    });

    it('puts a leading mark before the control and leaves room for it', function () {
        $this->artisan('shape:icon:add', ['name' => ['globe'], '--no-clear' => true])->run();

        $html = Blade::render('<shape:select icon="globe" />');

        expect(strpos($html, '<svg'))->toBeLessThan(strpos($html, '<select'))
            ->and(dropdown($html))->toContain('ps-7');
    });

    it('leaves no leading room when there is no leading mark', function () {
        expect(dropdown(Blade::render('<shape:select />')))->toContain('ps-0');
    });

    it('ignores a bare icon attribute rather than looking for an icon named 1', function () {
        expect(Blade::render('<shape:select icon />'))->toBe(Blade::render('<shape:select />'));
    });

    it('takes a leading mark from a named set and leaves the chevron alone', function () {
        // `icon-set` names the set for the leading mark only. The chevron is
        // Shape's own and resolves through `default`, the way the button's spinner
        // and the message's mark do -- which is the only set `shape:install`
        // publishes the required names into. Handed the call site's set instead, a
        // `<shape:select icon-set="solid">` would throw on the chevron in every
        // application that had not published one into `solid/` by hand.
        config()->set('shape.icons.sets', ['fixture' => 'fixture']);

        $this->artisan('shape:icon:add', [
            'name' => ['check'], '--set' => 'fixture', '--no-clear' => true,
        ])->run();

        expect(Blade::render('<shape:select icon="check" icon-set="fixture" />'))
            ->toContain('data-fixture="check"')
            ->toContain('m7 15 5 5 5-5');
    });

    it('has no icon-trailing prop, so one lands on the element to be noticed', function () {
        // The chevron owns that side. Declared and ignored, the attribute would
        // vanish without a word; undeclared, it lands on the `<select>` where a
        // reader or a validator will point at it.
        expect(dropdown(Blade::render('<shape:select icon-trailing="x" />')))
            ->toContain('icon-trailing="x"');
    });
});

describe('multiple', function () {
    it('reaches the element', function () {
        expect(dropdown(Blade::render('<shape:select multiple />')))->toContain('multiple');
    });

    it('reads a written-out attribute as present', function () {
        // `filter_var` alone would read `multiple="multiple"` as false, so the
        // attribute would be there and the component would think it absent.
        expect(Blade::render('<shape:select multiple="multiple" />'))
            ->toBe(Blade::render('<shape:select multiple />'));
    });

    it('draws no chevron, because nothing opens', function () {
        expect(Blade::render('<shape:select multiple />'))->not->toContain('<svg');
    });

    it('leaves no room for a mark it does not draw', function () {
        expect(dropdown(Blade::render('<shape:select multiple />')))
            ->not->toContain('pe-7')
            ->toContain('p-0');
    });

    it('keeps the operating system\'s own rendering of a list box', function () {
        expect(dropdown(Blade::render('<shape:select multiple />')))
            ->not->toContain('appearance-none');
    });

    it('is a dropdown again when the call site says so outright', function () {
        expect(Blade::render('<shape:select :multiple="false" />'))
            ->toBe(Blade::render('<shape:select />'));
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.select', ['size' => 'lg']);

        expect(Blade::render('<shape:select />'))
            ->toBe(Blade::render('<shape:select size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:select />');

        config()->set('shape.components.select', null);

        expect(Blade::render('<shape:select />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unstyled field', function (mixed $configured) {
        config()->set('shape.components.select', $configured);

        expect(Blade::render('<shape:select />'))->toContain('px-4 py-2');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);
});

describe('attributes', function () {
    it('puts the class on the box and everything else on the control', function () {
        $html = Blade::render('<shape:select class="max-w-sm" name="plan" required />');

        expect($html)->toContain('max-w-sm')
            ->and(dropdown($html))
            ->not->toContain('max-w-sm')
            ->toContain('name="plan"')
            ->toContain('required');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(dropdown(Blade::render('<shape:select '.$binding.'="plan" />')))
            ->toContain($binding.'="plan"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render('<shape:select name="plan" />');

        expect($html)->toContain('border-danger-border')
            ->and(dropdown($html))->toContain('aria-invalid="true"');
    });

    it('finds the field name in the Livewire binding when there is no name attribute', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        expect(Blade::render('<shape:select wire:model.live="plan" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(Blade::render('<shape:select name="plan" :invalid="true" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        // `aria-invalid` is the whole of the signal now: the danger classes ride in
        // every control's class list behind an `invalid:` variant, so what says a
        // field is wrong is the attribute the variant matches on. Asserting the
        // absence of a class name here would assert nothing -- it is always there.
        expect(Blade::render('<shape:select name="plan" :invalid="false" />'))
            ->toContain('border-neutral-border')
            ->not->toContain('aria-invalid');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(Blade::render('<shape:select name="plan" />'))->toContain('border-neutral-border');
    });
});

describe('shorthand', function () {
    it('expands a label into a field, a control and a message', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render('<shape:select label="Plan" name="plan"><option>Pro</option></shape:select>');

        expect($html)
            ->toContain('<label')
            ->toContain('Plan')
            ->toContain('<select')
            ->toContain('<option>Pro</option>')
            ->toContain('Pick a plan.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:select label="Plan" name="billing.plan" />');

        expect($html)
            ->toContain('for="billing-plan"')
            ->and(dropdown($html))->toContain('id="billing-plan"');
    });

    it('describes the control with the ids it actually rendered', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render('<shape:select label="Plan" description="Change it whenever." name="plan" />');

        expect(dropdown($html))->toContain('aria-describedby="plan-description plan-error"');
    });

    it('carries the options and every prop through to the control', function () {
        // The shorthand is this component calling itself, so anything it does not
        // hand down is silently lost.
        $html = Blade::render('<shape:select label="Plans" name="plans" multiple size="sm"><option>Pro</option></shape:select>');

        expect($html)->toContain('<option>Pro</option>')
            ->and(dropdown($html))->toContain('multiple')->toContain('text-sm');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:select name="plan" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });
});
