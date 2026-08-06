<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input>` alone, out of the box the wrapper draws around it. Asserting on
 * this rather than on the whole render is the difference between "the class is in
 * there somewhere" and "the class is on the element that answers to it".
 */
function control(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a text input inside the box that carries its border', function () {
    $html = Blade::render('<shape:input />');

    expect($html)
        ->toContain('<input')
        ->toContain('type="text"')
        ->toContain('border-neutral-border')
        ->toContain('bg-surface');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:input />'))
        ->toBe(Blade::render('<shape:input size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    // A closed set, like the button's: four rungs, no way to add a fifth, so an
    // unknown one is a typo rather than an extension point.
    expect(Blade::render('<shape:input size="huge" />'))
        ->toBe(Blade::render('<shape:input />'));
});

it('gives each rung its own box and its own type', function (string $size, string $box, string $type) {
    $html = Blade::render('<shape:input size="'.$size.'" />');

    expect($html)->toContain($box)
        ->and(control($html))->toContain($type);
})->with([
    'xs is what a table row can afford' => ['xs', 'gap-1 px-2 py-1', 'text-xs'],
    'sm is a toolbar' => ['sm', 'gap-1.5 px-3 py-1.5', 'text-sm'],
    'md is the form field' => ['md', 'gap-2 px-4 py-2', 'text-sm'],
    'lg is a screen with one question on it' => ['lg', 'gap-2.5 px-5 py-2.5', 'text-base'],
]);

it('stands on the padding its button rung stands on', function (string $size) {
    // Not a coincidence to be re-derived later: the rungs are the button's own, so
    // an input and an outline button of the same size sit level in one row. Both
    // halves of the pair are read here so the claim breaks loudly if either moves.
    preg_match('/(py-[\d.]+)/', Blade::render('<shape:input size="'.$size.'" />'), $input);
    preg_match('/(py-[\d.]+)/', Blade::render('<shape:button size="'.$size.'">Go</shape:button>'), $button);

    expect($input[1])->toBe($button[1]);
})->with(['xs', 'sm', 'md', 'lg']);

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:input size="lg" icon-set="fixture" :invalid="false" />');

    expect($html)
        ->not->toContain('size="lg"')
        ->not->toContain('icon-set=')
        ->not->toContain('invalid=');
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.input', ['size' => 'lg']);

        expect(Blade::render('<shape:input />'))
            ->toBe(Blade::render('<shape:input size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:input />');

        config()->set('shape.components.input', null);

        expect(Blade::render('<shape:input />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unstyled field', function (mixed $configured) {
        config()->set('shape.components.input', $configured);

        expect(Blade::render('<shape:input />'))->toContain('gap-2 px-4 py-2');
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
        // The one rule this shape costs. `max-w-sm` is something you are saying
        // about the box you can see; `required` is something only the control can
        // act on, and neither would work on the other element.
        $html = Blade::render('<shape:input class="max-w-sm" name="email" required placeholder="you@example.com" />');

        expect($html)->toContain('max-w-sm')
            ->and(control($html))
            ->not->toContain('max-w-sm')
            ->toContain('name="email"')
            ->toContain('required')
            ->toContain('placeholder="you@example.com"');
    });

    it('defaults to a text input without taking the choice away', function () {
        expect(control(Blade::render('<shape:input type="email" />')))
            ->toContain('type="email"')
            ->not->toContain('type="text"');
    });

    it('tames the native picker on a date field', function (string $type) {
        // The one piece of native chrome left in this component. `picker` is a
        // variant shape.css declares, because Tailwind names no such
        // pseudo-element.
        expect(control(Blade::render('<shape:input type="'.$type.'" />')))
            ->toContain('picker:cursor-pointer')
            ->toContain('picker:opacity-60');
    })->with(['date', 'datetime-local', 'month', 'time', 'week']);

    it('leaves the picker classes off a field that has no picker', function () {
        // They are inert on a text input, which is not a reason to ship them.
        expect(Blade::render('<shape:input type="email" />'))->not->toContain('picker:');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(control(Blade::render('<shape:input '.$binding.'="email" />')))
            ->toContain($binding.'="email"');
    })->with([
        'the plain binding' => ['wire:model'],
        'deferred until an event' => ['wire:model.blur'],
        'live, with modifiers stacked on the name' => ['wire:model.live.debounce.300ms'],
    ]);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['email' => ['The email field is required.']]);

        $html = Blade::render('<shape:input name="email" />');

        expect($html)->toContain('border-danger-border')
            ->and(control($html))->toContain('aria-invalid="true"');
    });

    it('finds the field name in the Livewire binding when there is no name attribute', function (string $binding) {
        // The modifiers ride on the attribute name rather than its value, so it is
        // the prefix that has to be matched -- and a Livewire form is the case
        // where there is no `name` at all.
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:input '.$binding.'="email" />'))
            ->toContain('border-danger-border');
    })->with(['wire:model', 'wire:model.live.debounce.300ms']);

    it('resolves a nested field name the way the validator spells it', function () {
        seedErrors(['user.email' => ['The user.email field is required.']]);

        expect(Blade::render('<shape:input name="user.email" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(Blade::render('<shape:input name="email" :invalid="true" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:input name="email" :invalid="false" />'))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        // No session middleware ran, so `$errors` was never shared. A component
        // that invented an empty bag would report every field as valid; one that
        // read an absent variable would take the page down.
        expect(Blade::render('<shape:input name="email" />'))
            ->toContain('border-neutral-border');
    });

    it('styles a bare input but leaves the message to the markup around it', function () {
        // There is no field here to put a sentence in, and an application writing
        // its own label is almost certainly writing its own error too.
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:input name="email" />'))
            ->toContain('aria-invalid="true"')
            ->not->toContain('The email field is required.');
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
        seedErrors(['email' => ['The email field is required.']]);

        $html = Blade::render('<shape:input label="Email" name="email" />');

        expect($html)
            ->toContain('<label')
            ->toContain('Email')
            ->toContain('<input')
            ->toContain('The email field is required.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:input label="Email" name="user.email" />');

        expect($html)
            ->toContain('for="user-email"')
            ->and(control($html))->toContain('id="user-email"');
    });

    it('describes the control with the ids it actually rendered', function () {
        seedErrors(['email' => ['The email field is required.']]);

        $html = Blade::render('<shape:input label="Email" description="We never share it." description-trailing="Work addresses only." name="email" />');

        expect(control($html))
            ->toContain('aria-describedby="email-description email-description-trailing email-error"')
            ->and($html)
            ->toContain('id="email-description"')
            ->toContain('id="email-description-trailing"')
            ->toContain('id="email-error"');
    });

    it('names no id it did not render', function () {
        // A reference to an element that is not on the page is an audit finding,
        // not a courtesy: with no message and no trailing text, only the
        // description is there to point at.
        $html = Blade::render('<shape:input label="Email" description="We never share it." name="email" />');

        expect(control($html))->toContain('aria-describedby="email-description"');
    });

    it('gives a labelled control an id even when nothing named it', function () {
        // A <label> pointing at nothing is worse than no label at all.
        $html = Blade::render('<shape:input label="Email" />');

        preg_match('/for="([^"]+)"/', $html, $for);

        expect($for[1] ?? '')->not->toBe('')
            ->and(control($html))->toContain('id="'.$for[1].'"');
    });

    it('follows an explicit id rather than the one it would have derived', function () {
        // Which is what an id is for: a name that collides with something else on
        // the page has to be able to say so.
        $html = Blade::render('<shape:input label="Email" name="email" id="signup-email" />');

        expect($html)
            ->toContain('for="signup-email"')
            ->and(control($html))->toContain('id="signup-email"');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:input name="email" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });
});

describe('icon', function () {
    beforeEach(function () {
        File::deleteDirectory(TestCase::iconPath());

        $this->artisan('shape:icon:add', [
            'name' => ['search', 'at-sign'],
            '--no-clear' => true,
        ])->run();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('puts the mark before the control', function () {
        $html = Blade::render('<shape:input icon="search" />');

        expect(strpos($html, '<svg'))->toBeLessThan(strpos($html, '<input'));
    });

    it('puts a trailing mark after it', function () {
        $html = Blade::render('<shape:input icon-trailing="at-sign" />');

        expect(strpos($html, '<input'))->toBeLessThan(strpos($html, '<svg'));
    });

    it('sizes the mark to the rung the field resolved', function () {
        // The whole reason this is a prop rather than a nested component: the pair
        // cannot drift apart, because the call site only says the size once.
        expect(Blade::render('<shape:input size="xs" icon="search" />'))->toContain('size-3.5')
            ->and(Blade::render('<shape:input size="lg" icon="search" />'))->toContain('size-6');
    });

    it('keeps the mark out of the accessibility tree', function () {
        // It decorates a control that its label already named.
        expect(Blade::render('<shape:input icon="search" />'))->toContain('aria-hidden="true"');
    });

    it('takes both marks from a named set', function () {
        config()->set('shape.icons.sets', ['fixture' => 'fixture']);

        $this->artisan('shape:icon:add', [
            'name' => ['check', 'cross'], '--set' => 'fixture', '--no-clear' => true,
        ])->run();

        $html = Blade::render('<shape:input icon="check" icon-trailing="cross" icon-set="fixture" />');

        expect($html)
            ->toContain('data-fixture="check"')
            ->toContain('data-fixture="cross"');
    });

    it('ignores a bare icon attribute rather than looking for an icon named 1', function () {
        expect(Blade::render('<shape:input icon />'))
            ->toBe(Blade::render('<shape:input />'));
    });
});
