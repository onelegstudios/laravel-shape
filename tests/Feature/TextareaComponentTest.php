<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<textarea>` alone, out of the box the wrapper draws around it. Named apart
 * from the input suite's extractor because Pest puts every module-level function
 * in one namespace, and a redeclaration is a fatal error rather than a failure.
 */
function multiline(string $html): string
{
    preg_match('/<textarea\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a textarea inside the box that carries its border', function () {
    $html = Blade::render('<shape:textarea />');

    expect($html)
        ->toContain('<textarea')
        ->toContain('border-neutral-border')
        ->toContain('bg-surface');
});

it('does not centre the control on a line it does not have', function () {
    // `items-center` centres a line box, which is right for a control one line
    // tall and wrong for one that is five: a centred textarea would leave its
    // padding above and below the words rather than around them.
    expect(Blade::render('<shape:textarea />'))->not->toContain('items-center');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:textarea />'))
        ->toBe(Blade::render('<shape:textarea size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:textarea size="huge" />'))
        ->toBe(Blade::render('<shape:textarea />'));
});

it('gives each rung its own box, its own type and its own leading', function (string $size, string $box, string $type, string $leading) {
    // Leading is stated per rung rather than left to the type scale, because a
    // paragraph and a single line want different things from one font size.
    $html = Blade::render('<shape:textarea size="'.$size.'" />');

    expect($html)->toContain($box)
        ->and(multiline($html))->toContain($type)->toContain($leading);
})->with([
    'xs is what a dense form can afford' => ['xs', 'px-2 py-1', 'text-xs', 'leading-5'],
    'sm' => ['sm', 'px-3 py-1.5', 'text-sm', 'leading-6'],
    'md is the form field' => ['md', 'px-4 py-2', 'text-sm', 'leading-6'],
    'lg' => ['lg', 'px-5 py-2.5', 'text-base', 'leading-7'],
]);

it('takes the input\'s own padding at every rung', function (string $size) {
    // Not a coincidence to be re-derived later: a textarea and the input above it
    // line up down their left edge because the numbers are the same ones. Both
    // halves are read here so the claim breaks loudly if either moves.
    preg_match('/(px-[\d.]+ py-[\d.]+)/', Blade::render('<shape:textarea size="'.$size.'" />'), $textarea);
    preg_match('/px-([\d.]+) py-([\d.]+)/', Blade::render('<shape:input size="'.$size.'" />'), $input);

    expect($textarea[1])->toBe('px-'.$input[1].' py-'.$input[2]);
})->with(['xs', 'sm', 'md', 'lg']);

it('holds nothing off anything, because there is nothing in the box but the control', function () {
    // The input holds a mark off its value with a `gap`. There is no mark here, so
    // a gap would be a class that never applies to anything.
    expect(Blade::render('<shape:textarea />'))->not->toContain('gap-');
});

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:textarea size="lg" :autosize="false" :invalid="false" />');

    expect($html)
        ->not->toContain('size="lg"')
        ->not->toContain('autosize')
        ->not->toContain('invalid=');
});

describe('height', function () {
    it('starts three lines tall rather than the browser\'s two', function () {
        // Two is a box so short it reads as broken.
        expect(multiline(Blade::render('<shape:textarea />')))->toContain('rows="3"');
    });

    it('lets the call site say how tall', function () {
        // Which is why `rows` is a merged default rather than a prop: it is a plain
        // HTML attribute that already reaches the control.
        expect(multiline(Blade::render('<shape:textarea rows="8" />')))
            ->toContain('rows="8"')
            ->not->toContain('rows="3"');
    });

    it('resizes vertically only', function () {
        // Horizontal resize inside a `w-full` box does nothing useful, and
        // Safari's default `both` lets a reader drag a textarea out of its box.
        expect(multiline(Blade::render('<shape:textarea />')))
            ->toContain('resize-y')
            ->toContain('disabled:resize-none');
    });

    it('follows its content when asked to', function () {
        // Opt-in rather than default: it lands in Chromium and not everywhere
        // else, and a control that reflows under the cursor in one engine and sits
        // still in another is a design decision rather than a default.
        expect(multiline(Blade::render('<shape:textarea autosize />')))
            ->toContain('field-sizing-content')
            ->toContain('resize-none')
            ->not->toContain('resize-y');
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.textarea', ['size' => 'lg']);

        expect(Blade::render('<shape:textarea />'))
            ->toBe(Blade::render('<shape:textarea size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:textarea />');

        config()->set('shape.components.textarea', null);

        expect(Blade::render('<shape:textarea />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unstyled field', function (mixed $configured) {
        config()->set('shape.components.textarea', $configured);

        expect(Blade::render('<shape:textarea />'))->toContain('px-4 py-2');
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
        $html = Blade::render('<shape:textarea class="max-w-sm" name="bio" required placeholder="About you" />');

        expect($html)->toContain('max-w-sm')
            ->and(multiline($html))
            ->not->toContain('max-w-sm')
            ->toContain('name="bio"')
            ->toContain('required')
            ->toContain('placeholder="About you"');
    });

    it('takes its value from the slot, the way a textarea does', function () {
        // A textarea has no `value` attribute; its content is its value.
        expect(Blade::render('<shape:textarea>Existing text</shape:textarea>'))
            ->toContain('>Existing text</textarea>');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(multiline(Blade::render('<shape:textarea '.$binding.'="bio" />')))
            ->toContain($binding.'="bio"');
    })->with(['wire:model', 'wire:model.blur', 'wire:model.live.debounce.300ms']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['bio' => ['The bio field is required.']]);

        $html = Blade::render('<shape:textarea name="bio" />');

        expect($html)->toContain('border-danger-border')
            ->and(multiline($html))->toContain('aria-invalid="true"');
    });

    it('finds the field name in the Livewire binding when there is no name attribute', function () {
        seedErrors(['bio' => ['Required.']]);

        expect(Blade::render('<shape:textarea wire:model.live="bio" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(Blade::render('<shape:textarea name="bio" :invalid="true" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['bio' => ['Required.']]);

        expect(Blade::render('<shape:textarea name="bio" :invalid="false" />'))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(Blade::render('<shape:textarea name="bio" />'))
            ->toContain('border-neutral-border');
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
        seedErrors(['bio' => ['The bio field is required.']]);

        $html = Blade::render('<shape:textarea label="Bio" name="bio" />');

        expect($html)
            ->toContain('<label')
            ->toContain('Bio')
            ->toContain('<textarea')
            ->toContain('The bio field is required.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:textarea label="Bio" name="user.bio" />');

        expect($html)
            ->toContain('for="user-bio"')
            ->and(multiline($html))->toContain('id="user-bio"');
    });

    it('describes the control with the ids it actually rendered', function () {
        seedErrors(['bio' => ['Required.']]);

        $html = Blade::render('<shape:textarea label="Bio" description="Keep it short." description-trailing="Markdown works." name="bio" />');

        expect(multiline($html))
            ->toContain('aria-describedby="bio-description bio-description-trailing bio-error"');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:textarea name="bio" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });

    it('carries the height props through to the control', function () {
        // The shorthand is this component calling itself, so anything it does not
        // hand down is silently lost.
        expect(Blade::render('<shape:textarea label="Bio" name="bio" autosize rows="8" />'))
            ->toContain('field-sizing-content')
            ->toContain('rows="8"');
    });
});
