<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input>` alone, out of the cell its thumb shares with it. Named apart from
 * the sibling suites' extractors because Pest puts every module-level function in
 * one namespace.
 */
function toggle(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

// The switch draws nothing itself, but standing alone it prints its own message,
// and the message draws `error`. The suite publishes for those tests; the one that
// asserts a fresh install renders clears the directory itself.
beforeEach(function () {
    publishRequiredIcons();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('renders a checkbox that announces itself as a switch', function () {
    // Underneath there is no other element that carries a boolean into a form. The
    // role is the whole of the difference, which is why it is asserted alongside
    // the type rather than on its own.
    $html = Blade::render('<shape:switch />');

    expect(toggle($html))
        ->toContain('type="checkbox"')
        ->toContain('role="switch"')
        ->toContain('peer');
});

it('draws the track itself rather than leaving the operating system\'s', function () {
    expect(toggle(Blade::render('<shape:switch />')))->toContain('appearance-none');
});

it('draws no icon at all', function () {
    // The thumb is a shape rather than a glyph, so asking an icon set for one would
    // be asking for the filled circle Heroicons does not ship.
    expect(Blade::render('<shape:switch />'))->not->toContain('<svg');
});

it('needs no icon published to render', function () {
    // The practical half of that, and the radio's promise: a switch works on a
    // fresh install with nothing published at all.
    File::deleteDirectory(TestCase::iconPath());

    expect(Blade::render('<shape:switch label="Notify me" />'))->toContain('role="switch"');
});

it('has no indeterminate state, because a switch is on or it is not', function () {
    expect(Blade::render('<shape:switch />'))->not->toContain('indeterminate');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:switch />'))->toBe(Blade::render('<shape:switch size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:switch size="huge" />'))->toBe(Blade::render('<shape:switch />'));
});

it('pairs each rung\'s track with a cell, a thumb and a travel', function (string $size, string $cell, string $track, string $thumb, string $travel) {
    // The table this component is built on, executable. Pick a 2px inset and the
    // rest follows: travel is the track's width minus its height, and travel is
    // also the thumb -- a thumb clears its own width and stops. Asserted together
    // so the four tables cannot drift apart.
    $html = Blade::render('<shape:switch label="Notify me" size="'.$size.'" />');

    expect($html)
        ->toContain('items-center '.$cell)
        ->toContain($thumb)
        ->toContain($travel)
        ->and(toggle($html))->toContain($track);
})->with([
    'xs travels 12 in a 28 by 16 track' => ['xs', 'h-4', 'h-4 w-7', 'size-3', 'peer-checked:translate-x-3'],
    'sm travels 14 in a 32 by 18 track' => ['sm', 'h-5', 'h-4.5 w-8', 'size-3.5', 'peer-checked:translate-x-3.5'],
    'md travels 16 in a 36 by 20 track' => ['md', 'h-5', 'h-5 w-9', 'size-4', 'peer-checked:translate-x-4'],
    'lg travels 20 in a 44 by 24 track' => ['lg', 'h-6', 'h-6 w-11', 'size-5', 'peer-checked:translate-x-5'],
]);

it('stands as tall as the checkbox it sits beside', function (string $size, string $track) {
    // The track heights are the checkbox's box sizes, and that is the point: a
    // switch, a box and a radio down one column are three selection rules on one
    // control, and a reader comparing them should find them the same height.
    expect(toggle(Blade::render('<shape:switch size="'.$size.'" />')))->toContain($track);
})->with([
    'xs' => ['xs', 'h-4'],
    'sm' => ['sm', 'h-4.5'],
    'md' => ['md', 'h-5'],
    'lg' => ['lg', 'h-6'],
]);

it('keeps the track from shrinking when the label is long', function () {
    expect(Blade::render('<shape:switch label="A rather long label" />'))->toContain('shrink-0');
});

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:switch size="lg" :invalid="false" />');

    expect($html)->not->toContain('size="lg"')->not->toContain('invalid=');
});

describe('the thumb', function () {
    it('parks at the left inset rather than centred', function () {
        // The grid cell is the track's width, so a centred thumb would have nowhere
        // to travel from.
        expect(Blade::render('<shape:switch />'))
            ->toContain('justify-self-start')
            ->toContain('ml-0.5');
    });

    it('slides on the control\'s checked state', function () {
        expect(Blade::render('<shape:switch />'))->toContain('peer-checked:translate-x-4');
    });

    it('takes the mark colour once it is sitting on the fill', function () {
        // On, it is the radio's dot exactly: the token for a mark drawn on a fill.
        expect(Blade::render('<shape:switch />'))->toContain('peer-checked:bg-primary-on-fill');
    });

    it('stays legible off, where there is no fill under it', function () {
        // `primary-on-fill` would be invisible on `surface` and `neutral-border`
        // would be a hairline, so off it takes the muted ink instead.
        expect(Blade::render('<shape:switch />'))->toContain('bg-ink-muted');
    });

    it('moves its colour and its position together', function () {
        // Both change at the same moment, and half a transition reads as a bug.
        expect(Blade::render('<shape:switch />'))->toContain('rounded-full bg-ink-muted transition ');
    });

    it('hands a click on it through to the control underneath', function () {
        expect(Blade::render('<shape:switch />'))->toContain('pointer-events-none');
    });
});

describe('the track', function () {
    it('fills solid when checked, border and all', function () {
        // The checkbox's colours exactly, so a form carrying both reads as one set
        // of controls rather than two.
        expect(toggle(Blade::render('<shape:switch />')))
            ->toContain('checked:bg-primary-fill')
            ->toContain('checked:border-primary-fill');
    });

    it('is a pill rather than a box', function () {
        expect(toggle(Blade::render('<shape:switch />')))
            ->toContain('rounded-full')
            ->not->toContain('rounded-sm');
    });

    it('rings on the keyboard rather than on every click', function () {
        // The control *is* the track, so `focus-within` would just be `focus` -- and
        // a switch left on wearing a permanent ring is noise.
        expect(toggle(Blade::render('<shape:switch />')))
            ->toContain('focus-visible:outline-neutral-ring')
            ->not->toContain('focus-within:');
    });

    it('fades when disabled rather than restating its colours', function () {
        expect(toggle(Blade::render('<shape:switch />')))
            ->toContain('disabled:opacity-50')
            ->toContain('disabled:cursor-not-allowed');
    });
});

describe('standing alone', function () {
    it('is the whole field, so it says what went wrong itself', function () {
        // Nothing around it owns the message. A switch is the checkbox's case here
        // rather than the radio's: one control, one question.
        seedErrors(['notify' => ['We need somewhere to send it.']]);

        expect(Blade::render('<shape:switch label="Email me" name="notify" />'))
            ->toContain('We need somewhere to send it.');
    });

    it('leaves the message to the field around it when there is one', function () {
        seedErrors(['notify' => ['Pick one.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="notify">
                <shape:switch label="Email me" />
            </shape:field>
        BLADE);

        expect($html)->not->toContain('Pick one.');
    });

    it('prints no message when the validator has nothing to say', function () {
        expect(Blade::render('<shape:switch label="Email me" name="notify" />'))
            ->not->toContain('text-danger-on-tint');
    });

    it('reads as a row rather than a column', function () {
        // The label names the thing beside it rather than the field above it, and
        // the control comes first so a switch and a box line up down one left edge.
        expect(Blade::render('<shape:switch label="Email me" />'))
            ->toContain('flex items-start')
            ->not->toContain('flex flex-col gap-1.5');
    });

    it('wraps a long label under its own first line', function () {
        expect(Blade::render('<shape:switch label="Email me" />'))->toContain('items-start');
    });

    it('scales the words with the rung', function (string $size, string $type) {
        expect(Blade::render('<shape:switch label="Email me" size="'.$size.'" />'))
            ->toContain($type.' font-medium');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('scopes its help text by the field it names', function () {
        $html = Blade::render('<shape:switch label="Email me" description="About once a month." name="notify" />');

        expect($html)->toContain('id="notify-description"')
            ->and(toggle($html))->toContain('aria-describedby="notify-description"');
    });
});

describe('the value', function () {
    // The one place this component departs from the checkbox it is built on.

    it('reaches the element without also becoming the id', function () {
        // The discriminator exists for controls that share a name, and a switch is
        // never one of a set. `notify-1` would be uglier than `notify` in exchange
        // for nothing.
        expect(toggle(Blade::render('<shape:switch name="notify" value="1" />')))
            ->toContain('value="1"')
            ->toContain('id="notify"')
            ->not->toContain('id="notify-1"');
    });

    it('points a label at the field rather than at an option of it', function () {
        expect(Blade::render('<shape:switch label="Email me" name="notify" value="1" />'))
            ->toContain('for="notify"')
            ->not->toContain('for="notify-1"');
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.switch', ['size' => 'lg']);

        expect(Blade::render('<shape:switch />'))->toBe(Blade::render('<shape:switch size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:switch />');

        config()->set('shape.components.switch', null);

        expect(Blade::render('<shape:switch />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unsized track', function (mixed $configured) {
        config()->set('shape.components.switch', $configured);

        expect(toggle(Blade::render('<shape:switch />')))->toContain('h-5 w-9');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);

    it('takes its rung from its own key rather than the checkbox\'s', function () {
        // Listed separately so an application can run its forms dense and its
        // settings pages roomy.
        config()->set('shape.components.checkbox', ['size' => 'xs']);

        expect(Blade::render('<shape:switch />'))->toBe(Blade::render('<shape:switch size="md" />'));
    });
});

describe('attributes', function () {
    it('puts the class on the cell and everything else on the control', function () {
        $html = Blade::render('<shape:switch class="mt-1" name="notify" required />');

        expect($html)->toContain('mt-1')
            ->and(toggle($html))
            ->not->toContain('mt-1')
            ->toContain('name="notify"')
            ->toContain('required');
    });

    it('lets the call site say the switch is on', function () {
        expect(toggle(Blade::render('<shape:switch checked />')))->toContain('checked');
    });

    it('lets the call site mean something else by the role', function () {
        expect(toggle(Blade::render('<shape:switch role="checkbox" />')))
            ->toContain('role="checkbox"')
            ->not->toContain('role="switch"');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(toggle(Blade::render('<shape:switch '.$binding.'="notify" />')))
            ->toContain($binding.'="notify"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['notify' => ['Required.']]);

        expect(toggle(Blade::render('<shape:switch name="notify" />')))
            ->toContain('border-danger-border')
            ->toContain('aria-invalid="true"');
    });

    it('rings in danger as well as bordering in it', function () {
        seedErrors(['notify' => ['Required.']]);

        expect(toggle(Blade::render('<shape:switch name="notify" />')))
            ->toContain('focus-visible:outline-danger-ring');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(toggle(Blade::render('<shape:switch name="notify" :invalid="true" />')))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['notify' => ['Required.']]);

        expect(toggle(Blade::render('<shape:switch name="notify" :invalid="false" />')))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(toggle(Blade::render('<shape:switch name="notify" />')))->toContain('border-neutral-border');
    });
});
