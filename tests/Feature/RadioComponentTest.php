<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input type="radio">` alone, out of the cell its dot shares with it. Named
 * apart from the sibling suites' extractors because Pest puts every module-level
 * function in one namespace.
 */
function dial(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a radio with its dot stacked on it', function () {
    $html = Blade::render('<shape:radio />');

    expect(dial($html))->toContain('type="radio"')->toContain('peer')
        ->and($html)->toContain('peer-checked:block');
});

it('draws no icon at all', function () {
    // The dot is CSS rather than artwork: Heroicons ships no `circle` and no `dot`,
    // so an alias would point at a glyph one of the two libraries Shape can install
    // does not have. Two tokens here beat a broken promise there.
    expect(Blade::render('<shape:radio />'))->not->toContain('<svg');
});

it('needs no icon published to render', function () {
    // Which is the practical half of that decision, and worth pinning: unlike the
    // checkbox, a radio works on a fresh install with nothing published at all.
    File::deleteDirectory(TestCase::iconPath());

    expect(Blade::render('<shape:radio label="Free" />'))->toContain('type="radio"');
});

it('is round rather than square', function () {
    // The only thing telling a reader this set is one-of-many rather than
    // any-of-many, which is why the shape is not configurable.
    expect(dial(Blade::render('<shape:radio />')))
        ->toContain('rounded-full')
        ->not->toContain('rounded-sm');
});

it('has no indeterminate state', function () {
    // A radio is on or it is not.
    expect(Blade::render('<shape:radio />'))->not->toContain('indeterminate');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:radio />'))->toBe(Blade::render('<shape:radio size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:radio size="huge" />'))->toBe(Blade::render('<shape:radio />'));
});

it('pairs each rung\'s box with a cell and a dot', function (string $size, string $cell, string $box, string $dot) {
    $html = Blade::render('<shape:radio label="Free" size="'.$size.'" />');

    expect($html)->toContain($cell)->toContain($dot)
        ->and(dial($html))->toContain($box);
})->with([
    'xs' => ['xs', 'h-4', 'size-4', 'size-1.5'],
    'sm' => ['sm', 'h-5', 'size-4.5', 'size-1.5'],
    'md' => ['md', 'h-5', 'size-5', 'size-2'],
    'lg' => ['lg', 'h-6', 'size-6', 'size-2.5'],
]);

it('keeps every dot on an even number of pixels', function (string $size) {
    // An odd dot centred in an even box lands on a half pixel and renders soft on a
    // 1x display, which is why `size-1.75` is deliberately not in the table even
    // though it would smooth the ramp between `xs` and `md`.
    //
    // Tailwind's spacing scale is quarter-rem, so a `size-N` is 4N pixels at the
    // default root size.
    preg_match('/rounded-full bg-primary-on-fill peer-checked:block size-([\d.]+)/', Blade::render('<shape:radio size="'.$size.'" />'), $dot);

    $pixels = (int) round(((float) $dot[1]) * 4);

    expect($pixels % 2)->toBe(0)->and($pixels)->toBeGreaterThan(0);
})->with(['xs', 'sm', 'md', 'lg']);

it('takes the checkbox\'s box at every rung', function (string $size) {
    // A radio and a checkbox in one form are the same control with two selection
    // rules, and a reader comparing them down a column should find them the same
    // size. Both halves are read here so the claim breaks loudly if either moves.
    //
    // The checkbox draws two marks, so its artwork has to be on disk for this one.
    publishRequiredIcons();

    preg_match('/(size-[\d.]+) border-neutral/', Blade::render('<shape:radio size="'.$size.'" />'), $radio);
    preg_match('/(size-[\d.]+) border-neutral/', Blade::render('<shape:checkbox size="'.$size.'" />'), $checkbox);

    expect($radio[1])->toBe($checkbox[1]);

    File::deleteDirectory(TestCase::iconPath());
})->with(['xs', 'sm', 'md', 'lg']);

it('does not leak the styling props onto the rendered elements', function () {
    expect(Blade::render('<shape:radio size="lg" :invalid="false" />'))
        ->not->toContain('size="lg"')
        ->not->toContain('invalid=');
});

describe('the dot', function () {
    it('draws only when the control is checked', function () {
        expect(Blade::render('<shape:radio />'))
            ->toContain('hidden')
            ->toContain('peer-checked:block');
    });

    it('sits on the checked fill', function () {
        // One token rather than `bg-current` plus a text colour, and it says exactly
        // what this is: the mark drawn on top of a filled surface.
        expect(Blade::render('<shape:radio />'))->toContain('bg-primary-on-fill');
    });

    it('hands a click on it through to the control underneath', function () {
        expect(Blade::render('<shape:radio />'))->toContain('pointer-events-none');
    });

    it('is round, like the box it sits in', function () {
        expect(substr_count(Blade::render('<shape:radio />'), 'rounded-full'))->toBe(2);
    });
});

describe('the box', function () {
    it('fills solid when checked, border and all', function () {
        expect(dial(Blade::render('<shape:radio />')))
            ->toContain('checked:bg-primary-fill')
            ->toContain('checked:border-primary-fill');
    });

    it('rings on the keyboard rather than on every click', function () {
        expect(dial(Blade::render('<shape:radio />')))
            ->toContain('focus-visible:outline-neutral-ring')
            ->not->toContain('focus-within:');
    });

    it('fades when disabled rather than restating its colours', function () {
        expect(dial(Blade::render('<shape:radio />')))
            ->toContain('disabled:opacity-50')
            ->toContain('disabled:cursor-not-allowed');
    });

    it('draws the box itself rather than leaving the operating system\'s', function () {
        expect(dial(Blade::render('<shape:radio />')))->toContain('appearance-none');
    });
});

describe('a group', function () {
    // The case the discriminator was built for. Three controls called `plan` are
    // three ids and three labels that each click through to their own.

    beforeEach(function () {
        publishRequiredIcons();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('gives every option an id of its own', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('id="plan-free"')
            ->toContain('id="plan-pro"')
            ->toContain('for="plan-free"')
            ->toContain('for="plan-pro"');
    });

    it('submits every option under the one field name', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan">
                <shape:radio value="free" label="Free" />
            </shape:field>
        BLADE);

        expect(dial($html))->toContain('name="plan"')->toContain('value="free"');
    });

    it('scopes each help text by the option rather than the field', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan">
                <shape:radio value="free" label="Free" description="One project." />
            </shape:field>
        BLADE);

        expect($html)->toContain('id="plan-free-description"')
            ->and(dial($html))->toContain('aria-describedby="plan-free-description"');
    });

    it('prints the message once, from the field', function () {
        // A radio is always one option of one field, so the sentence belongs to the
        // group and three copies of it is not a message.
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'Pick a plan.'))->toBe(1);
    });

    it('points every option at the one message', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        // Two options describing it, and the message's own id. The count is also
        // the guard on the other side of that: the fieldset around them
        // deliberately does not name `plan-error` too, because a sentence read on
        // entering the group and again on the first option is the same sentence
        // twice. A fourth here would be that regression.
        expect(substr_count($html, 'plan-error'))->toBe(3)
            ->and($html)->toContain('id="plan-error"');
    });

    it('is announced as a group rather than as loose options', function () {
        // Three radios wired by `name` are visually a set and, without this, three
        // unrelated controls to anything reading the page. The element is the only
        // thing that says otherwise.
        //
        // `for="plan"` is the bug this guards against, stated as an assertion: a
        // group named by a <label> points at an id nothing renders. Safe to match
        // on, because `for="plan-free"` does not contain the closing quote.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('<fieldset')
            ->toContain('>Plan</legend>')
            ->not->toContain('for="plan"');
    });

    it('marks every option the validator minds', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan">
                <shape:radio value="free" />
                <shape:radio value="pro" />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'aria-invalid="true"'))->toBe(2)
            ->and(substr_count($html, 'border-danger-border'))->toBe(2);
    });
});

describe('standing alone', function () {
    it('never prints a message of its own', function () {
        // Unlike a standalone checkbox, which *is* a whole field -- one box, one
        // question. One option of a set the user cannot choose from is a bug in the
        // markup rather than a state to style, so the sentence always belongs to a
        // group.
        seedErrors(['plan' => ['Pick a plan.']]);

        expect(Blade::render('<shape:radio label="Free" name="plan" value="free" />'))
            ->not->toContain('Pick a plan.');
    });

    it('still takes the invalid styling', function () {
        // The message belongs to the group; the styling belongs to the control.
        seedErrors(['plan' => ['Pick a plan.']]);

        expect(dial(Blade::render('<shape:radio name="plan" value="free" />')))
            ->toContain('border-danger-border')
            ->toContain('aria-invalid="true"');
    });

    it('reads as a row rather than a column', function () {
        expect(Blade::render('<shape:radio label="Free" />'))
            ->toContain('flex items-start')
            ->not->toContain('flex flex-col gap-1.5');
    });

    it('scales the words with the rung', function (string $size, string $type) {
        expect(Blade::render('<shape:radio label="Free" size="'.$size.'" />'))
            ->toContain($type.' font-medium');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.radio', ['size' => 'lg']);

        expect(Blade::render('<shape:radio />'))->toBe(Blade::render('<shape:radio size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:radio />');

        config()->set('shape.components.radio', null);

        expect(Blade::render('<shape:radio />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unsized box', function (mixed $configured) {
        config()->set('shape.components.radio', $configured);

        expect(dial(Blade::render('<shape:radio />')))->toContain('size-5');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);

    it('takes its own rung rather than the checkbox\'s', function () {
        // Listed separately in config so a dense form can put its boxes on one rung
        // and its dials on another.
        config()->set('shape.components.radio', ['size' => 'lg']);
        config()->set('shape.components.checkbox', ['size' => 'xs']);

        expect(dial(Blade::render('<shape:radio />')))->toContain('size-6');
    });
});

describe('attributes', function () {
    it('puts the class on the cell and everything else on the control', function () {
        $html = Blade::render('<shape:radio class="mt-1" name="plan" value="pro" required />');

        expect($html)->toContain('mt-1')
            ->and(dial($html))
            ->not->toContain('mt-1')
            ->toContain('name="plan"')
            ->toContain('value="pro"')
            ->toContain('required');
    });

    it('lets the call site say which option is selected', function () {
        expect(dial(Blade::render('<shape:radio value="pro" checked />')))->toContain('checked');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(dial(Blade::render('<shape:radio '.$binding.'="plan" value="pro" />')))
            ->toContain($binding.'="plan"');
    })->with(['wire:model', 'wire:model.live']);

    it('scopes its id by the value even when it is bound rather than named', function () {
        expect(dial(Blade::render('<shape:radio wire:model="plan" value="pro" />')))
            ->toContain('id="plan-pro"');
    });
});
