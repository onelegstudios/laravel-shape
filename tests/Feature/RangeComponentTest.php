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
function slider(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a range input with no box around it', function () {
    // Every other control in the family is an element inside a wrapper Shape draws.
    // This one is its own box: the track is the only thing there is to see, and a
    // border around it would be a second horizontal line saying nothing.
    $html = Blade::render('<shape:range />');

    expect($html)
        ->toContain('<input')
        ->toContain('type="range"')
        ->not->toContain('<div');
});

it('draws the track and the thumb itself rather than leaving the browser\'s', function () {
    // `appearance-none` is what makes any of the rest apply. Left native, the
    // browser draws both and ignores every class below it.
    expect(slider(Blade::render('<shape:range />')))
        ->toContain('appearance-none')
        ->toContain('thumb:appearance-none');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:range />'))->toBe(Blade::render('<shape:range size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    // A closed set, like the input's: four rungs, no way to add a fifth, so an
    // unknown one is a typo rather than an extension point.
    expect(Blade::render('<shape:range size="huge" />'))->toBe(Blade::render('<shape:range />'));
});

it('pairs each rung\'s box with a track, a thumb and the offset that centres it', function (string $size, string $box, string $track, string $thumb, string $offset) {
    // The table this component is built on, executable. Asserted together so the
    // four cannot drift apart -- a thumb resized without its offset is a thumb that
    // sits off the track in Chromium, which no other assertion here would catch.
    expect(slider(Blade::render('<shape:range size="'.$size.'" />')))
        ->toContain($box)
        ->toContain($track)
        ->toContain($thumb)
        ->toContain($offset);
})->with([
    'xs seats a 12 thumb on a 4 track' => ['xs', 'h-6.5', 'track:h-1', 'thumb:size-3', 'webkit-thumb:-mt-1'],
    'sm seats a 14 thumb on a 6 track' => ['sm', 'h-8.5', 'track:h-1.5', 'thumb:size-3.5', 'webkit-thumb:-mt-1'],
    'md seats a 16 thumb on an 8 track' => ['md', 'h-9.5', 'track:h-2', 'thumb:size-4', 'webkit-thumb:-mt-1'],
    'lg seats a 20 thumb on an 8 track' => ['lg', 'h-11.5', 'track:h-2', 'thumb:size-5', 'webkit-thumb:-mt-1.5'],
]);

it('offsets the thumb by half the difference between it and its track', function (string $size, int $track, int $thumb, string $offset) {
    // The identity behind the table above, checked rather than trusted: Chromium
    // seats the thumb on the top edge of the track's box, so centring it is
    // (track - thumb) / 2. Every one of the four has to land on the spacing scale,
    // which is the constraint that picked the track heights in the first place --
    // an arbitrary value here would be the only one in the library.
    $rendered = (float) str_replace('webkit-thumb:-mt-', '', $offset) * -4;

    expect($rendered)->toBe(($track - $thumb) / 2.0)
        ->and(slider(Blade::render('<shape:range size="'.$size.'" />')))->toContain($offset);
})->with([
    'xs' => ['xs', 4, 12, 'webkit-thumb:-mt-1'],
    'sm' => ['sm', 6, 14, 'webkit-thumb:-mt-1'],
    'md' => ['md', 8, 16, 'webkit-thumb:-mt-1'],
    'lg' => ['lg', 8, 20, 'webkit-thumb:-mt-1.5'],
]);

it('keeps the offset off the thumb Firefox centres itself', function () {
    // `webkit-thumb` rather than `thumb`, and this is the whole reason shape.css
    // declares the two separately. Firefox centres `::-moz-range-thumb` on the
    // track and would read the same margin as a shove upwards.
    expect(slider(Blade::render('<shape:range />')))->not->toMatch('/(?<![-\w])thumb:-mt-/');
});

it('stands as tall as the field beside it', function (string $size) {
    // Not a coincidence to be re-derived later: the heights are the input's own
    // outer heights, so a slider and a text field of the same rung sit level in one
    // row. Both halves are read here so the claim breaks loudly if either moves.
    //
    // Everything is in spacing units, Tailwind's 4px. An input's height is what its
    // padding, its line box and its two borders come to; a slider has none of the
    // three, so it states the total outright and this is where the two are compared.
    $lines = ['text-xs' => 4, 'text-sm' => 5, 'text-base' => 6];
    $border = 0.5;

    $field = Blade::render('<shape:input size="'.$size.'" />');

    preg_match('/\bpy-([\d.]+)\b/', $field, $padding);
    preg_match('/\btext-(?:xs|sm|base)\b/', $field, $type);
    preg_match('/\bh-([\d.]+)\b/', slider(Blade::render('<shape:range size="'.$size.'" />')), $slider);

    expect((float) $slider[1])->toBe((float) $padding[1] * 2 + $lines[$type[0]] + $border);
})->with(['xs', 'sm', 'md', 'lg']);

it('takes the switch\'s thumb, so the two read as one set of controls', function (string $size, string $thumb) {
    expect(slider(Blade::render('<shape:range size="'.$size.'" />')))->toContain('thumb:'.$thumb)
        ->and(Blade::render('<shape:switch size="'.$size.'" />'))->toContain($thumb);
})->with([
    'xs' => ['xs', 'size-3'],
    'sm' => ['sm', 'size-3.5'],
    'md' => ['md', 'size-4'],
    'lg' => ['lg', 'size-5'],
]);

it('rounds both parts, because a square thumb on a square track is neither', function () {
    expect(slider(Blade::render('<shape:range />')))
        ->toContain('track:rounded-full')
        ->toContain('thumb:rounded-full');
});

it('clears the border Firefox gives the thumb', function () {
    // `::-moz-range-thumb` arrives with one, and no amount of background hides it.
    expect(slider(Blade::render('<shape:range />')))->toContain('thumb:border-0');
});

it('paints no filled portion, in any browser', function () {
    // CSS cannot read a slider's value, so the part left of the thumb can only be
    // painted through `::-moz-range-progress` -- Firefox's alone. Taking it would
    // make one browser's slider deliberately different from the rest, which is the
    // thing the date field's documentation says the library declines to do.
    expect(Blade::render('<shape:range />'))->not->toContain('progress');
});

it('draws no icon, because there is nowhere in a track to put one', function () {
    // A track runs the full width -- a slider that stopped short of its own box
    // would be a slider whose maximum is unreachable -- so there is no space an
    // input would have left for a mark. A mark belongs beside this control, which
    // makes it the call site's to place.
    File::deleteDirectory(TestCase::iconPath());

    expect(Blade::render('<shape:range />'))
        ->not->toContain('<svg')
        ->toContain('type="range"');
});

it('does not leak the styling props onto the rendered element', function () {
    $html = Blade::render('<shape:range size="lg" :invalid="false" />');

    expect(slider($html))
        ->not->toContain('size="lg"')
        ->not->toContain('invalid');
});

describe('the ring', function () {
    it('puts the ring on the thumb rather than around the whole control', function () {
        // An outline around the full 38px box of something whose visible part is a
        // 2px bar reads as a stray rectangle.
        expect(slider(Blade::render('<shape:range />')))
            ->toContain('focus:outline-none')
            ->toContain('focus-visible:thumb:outline-2')
            ->toContain('focus-visible:thumb:outline-offset-2');
    });

    it('orders the prefixes state-first', function () {
        // The same trap the file input documents for `disabled:file:`. Variants
        // apply left to right, so this compiles to
        // `&:focus-visible::-webkit-slider-thumb` -- the thumb of a focused slider.
        // The other order asks a pseudo-element to match `:focus-visible`, which
        // never happens, and the markup renders identically either way.
        expect(slider(Blade::render('<shape:range />')))->not->toContain('thumb:focus-visible:');
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.range', ['size' => 'lg']);

        expect(Blade::render('<shape:range />'))->toBe(Blade::render('<shape:range size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:range />');

        config()->set('shape.components.range', null);

        expect(Blade::render('<shape:range />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an uncentred thumb', function (mixed $configured) {
        config()->set('shape.components.range', $configured);

        expect(slider(Blade::render('<shape:range />')))
            ->toContain('track:h-2')
            ->toContain('thumb:size-4');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);

    it('takes its rung from its own key rather than the input\'s', function () {
        config()->set('shape.components.input', ['size' => 'xs']);

        expect(Blade::render('<shape:range />'))->toBe(Blade::render('<shape:range size="md" />'));
    });
});

describe('attributes', function () {
    it('puts the class on the control, because the control is the box', function () {
        // The one rule the input's shape costs does not apply here: there is no
        // wrapper to be talking about instead.
        expect(slider(Blade::render('<shape:range class="max-w-3xs" />')))->toContain('max-w-3xs');
    });

    it('hands the range attributes to the control untouched', function () {
        expect(slider(Blade::render('<shape:range name="volume" min="0" max="11" step="0.5" value="7" />')))
            ->toContain('name="volume"')
            ->toContain('min="0"')
            ->toContain('max="11"')
            ->toContain('step="0.5"')
            ->toContain('value="7"');
    });

    it('lets the call site mean something else by the type', function () {
        expect(slider(Blade::render('<shape:range type="number" />')))
            ->toContain('type="number"')
            ->not->toContain('type="range"');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(slider(Blade::render('<shape:range '.$binding.'="volume" />')))
            ->toContain($binding.'="volume"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so on the track', function () {
        // There is no border here to say it on, so the track carries it.
        seedErrors(['volume' => ['Pick a level.']]);

        expect(slider(Blade::render('<shape:range name="volume" />')))
            ->toContain('track:bg-danger-tint')
            ->toContain('aria-invalid="true"');
    });

    it('rings in danger as well as tinting in it', function () {
        seedErrors(['volume' => ['Pick a level.']]);

        expect(slider(Blade::render('<shape:range name="volume" />')))
            ->toContain('focus-visible:thumb:outline-danger-ring');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(slider(Blade::render('<shape:range name="volume" :invalid="true" />')))
            ->toContain('track:bg-danger-tint');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['volume' => ['Pick a level.']]);

        expect(slider(Blade::render('<shape:range name="volume" :invalid="false" />')))
            ->toContain('track:bg-neutral-tint')
            ->not->toContain('track:bg-danger-tint');
    });

    it('stays quiet when there is no bag to read', function () {
        // No session middleware ran, so `$errors` was never shared. A component that
        // invented an empty bag would report every field as valid; one that read an
        // absent variable would take the page down.
        expect(slider(Blade::render('<shape:range name="volume" />')))->toContain('track:bg-neutral-tint');
    });

    it('styles a bare slider but leaves the message to the markup around it', function () {
        seedErrors(['volume' => ['Pick a level.']]);

        expect(Blade::render('<shape:range name="volume" />'))
            ->toContain('aria-invalid="true"')
            ->not->toContain('Pick a level.');
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
        seedErrors(['volume' => ['Pick a level.']]);

        $html = Blade::render('<shape:range label="Volume" name="volume" />');

        expect($html)
            ->toContain('<label')
            ->toContain('Volume')
            ->toContain('type="range"')
            ->toContain('Pick a level.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:range label="Volume" name="player.volume" />');

        expect($html)
            ->toContain('for="player-volume"')
            ->and(slider($html))->toContain('id="player-volume"');
    });

    it('describes the control with the ids it actually rendered', function () {
        $html = Blade::render('<shape:range label="Volume" description="Applies to previews." name="volume" />');

        expect(slider($html))->toContain('aria-describedby="volume-description"')
            ->and($html)->toContain('id="volume-description"');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:range name="volume" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });

    it('takes its name from the field around it', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="volume">
                <shape:label>Volume</shape:label>
                <shape:range />
            </shape:field>
        BLADE);

        expect($html)->toContain('for="volume"')
            ->and(slider($html))->toContain('id="volume"');
    });
});

describe('disabled', function () {
    it('dims the track and the thumb together', function () {
        // Opacity on the element carries its pseudo-elements, so one class does
        // both and neither state has to sort after the other.
        expect(slider(Blade::render('<shape:range disabled />')))
            ->toContain('disabled')
            ->toContain('disabled:opacity-50')
            ->toContain('disabled:cursor-not-allowed');
    });

    it('offers the drag cursor the rest of the time', function () {
        expect(slider(Blade::render('<shape:range />')))->toContain('cursor-pointer');
    });
});
