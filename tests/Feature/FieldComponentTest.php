<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

// The message draws an icon, so every test that renders one needs the artwork on
// disk. Published for the whole file rather than per describe: the two blocks that
// need it are most of it, and a message that failed to resolve its mark would
// throw rather than quietly render without one.
beforeEach(function () {
    publishRequiredIcons();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('stacks the parts of a field in one column', function () {
    $html = Blade::render('<shape:field><shape:input /></shape:field>');

    expect($html)
        ->toContain('flex flex-col gap-1.5')
        ->toContain('<input');
});

it('renders nothing but the slot when no chrome prop was named', function () {
    // The composed form says what it wants; a field that added a label or a
    // message of its own would be answering a question nobody asked.
    $html = Blade::render('<shape:field name="email"><shape:input /></shape:field>');

    expect($html)
        ->not->toContain('<label')
        ->not->toContain('<p');
});

describe('name', function () {
    it('carries the field name down to parts that cannot see each other', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="email">
                <shape:label>Email</shape:label>
                <shape:description>We never share it.</shape:description>
                <shape:input />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('for="email"')
            ->toContain('id="email-description"')
            ->toContain('id="email"');
    });

    it('lets a part name itself over the field that encloses it', function () {
        // The nearer answer is the one the author meant. @aware assigns
        // unconditionally while @props only fills a null, so a component declaring
        // the same key in both would have this precedence exactly backwards.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="outer">
                <shape:error name="inner">Something went wrong</shape:error>
                <shape:label for="elsewhere">Label</shape:label>
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('id="inner-error"')
            ->toContain('for="elsewhere"')
            ->not->toContain('outer');
    });

    it('names the control it wraps, so the field submits something', function () {
        // Pinned because it is quietly fragile: `@props` ends by unsetting every
        // variable matching an attribute it did not claim, and `name` is not a
        // prop on the input -- so an `@aware` written above `@props` there loses
        // the inherited name the moment a call site passes one of its own.
        $html = Blade::render('<shape:field name="email"><shape:input /></shape:field>');

        expect($html)->toContain('name="email"');
    });

    it('leaves a bound control alone rather than naming it twice', function () {
        // Livewire has no use for the attribute, and the binding already said
        // which field this is.
        $html = Blade::render('<shape:field name="email"><shape:input wire:model="email" /></shape:field>');

        expect($html)->not->toContain('name="email"');
    });

    it('lends its name to the control for the error bag lookup', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:field name="email"><shape:input /></shape:field>'))
            ->toContain('border-danger-border');
    });

    it('resolves an id from a name spelled the way the validator spells it', function () {
        $html = Blade::render('<shape:field name="items[0].qty"><shape:label>Qty</shape:label></shape:field>');

        expect($html)->toContain('for="items-0-qty"');
    });
});

describe('shorthand', function () {
    it('writes the label, the help text and the message itself', function () {
        seedErrors(['email' => ['The email field is required.']]);

        $html = Blade::render('<shape:field name="email" label="Email" description="We never share it."><shape:input /></shape:field>');

        expect($html)
            ->toContain('>Email</label>')
            ->toContain('We never share it.')
            ->toContain('The email field is required.');
    });

    it('puts trailing help text after the control', function () {
        $html = Blade::render('<shape:field name="email" description-trailing="Work addresses only."><shape:input /></shape:field>');

        expect(strpos($html, '<input'))->toBeLessThan(strpos($html, 'Work addresses only.'));
    });

    it('gives the trailing help text an id of its own', function () {
        // Two elements cannot answer to the same aria-describedby token, and the
        // leading description has already claimed the name-derived default.
        $html = Blade::render('<shape:field name="email" description="One" description-trailing="Two"><shape:input /></shape:field>');

        expect($html)
            ->toContain('id="email-description"')
            ->toContain('id="email-description-trailing"');
    });

    it('prints the message once when the field already writes its own', function () {
        // The composed form carries its own <shape:error>, and a field that added
        // a second would print the same sentence twice.
        seedErrors(['email' => ['The email field is required.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="email">
                <shape:input />
                <shape:error />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'The email field is required.'))->toBe(1);
    });
});

describe('label', function () {
    it('carries the pair on weight rather than size', function () {
        expect(Blade::render('<shape:label>Email</shape:label>'))
            ->toContain('text-sm font-medium text-ink');
    });

    it('points at nothing when there is nothing to point at', function () {
        // Outside a field, or inside one that was never named. A label with no
        // pair is still a label -- the markup around it is the call site's own.
        expect(Blade::render('<shape:label>Email</shape:label>'))->not->toContain('for=');
    });

    it('takes classes from the call site without losing its own', function () {
        expect(Blade::render('<shape:label class="uppercase">Email</shape:label>'))
            ->toContain('uppercase')
            ->toContain('font-medium');
    });

    it('follows a rung when one is named', function (string $size, string $type) {
        // The checkbox is what names one: its label sits on the same line as the
        // box, so the row's line box is whatever the label sets.
        expect(Blade::render('<shape:label size="'.$size.'">Email</shape:label>'))
            ->toContain($type.' font-medium');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('stays at the field size when no rung was named', function () {
        // In a field the label is a line of its own and does not follow the
        // control's scale, so unnamed has to render exactly what it always did.
        expect(Blade::render('<shape:label>Email</shape:label>'))
            ->toContain('text-sm font-medium');
    });

    it('falls back to the field size for a rung it does not have', function () {
        expect(Blade::render('<shape:label size="huge">Email</shape:label>'))
            ->toBe(Blade::render('<shape:label>Email</shape:label>'));
    });

    it('does not leak the rung onto the element', function () {
        expect(Blade::render('<shape:label size="lg">Email</shape:label>'))
            ->not->toContain('size=');
    });
});

describe('description', function () {
    it('reads as help text rather than as the value beside it', function () {
        expect(Blade::render('<shape:description>We never share it.</shape:description>'))
            ->toContain('text-sm text-ink-muted');
    });

    it('takes an id from the call site over the one it would derive', function () {
        $html = Blade::render('<shape:field name="email"><shape:description id="mine">Help</shape:description></shape:field>');

        expect($html)->toContain('id="mine"')->not->toContain('id="email-description"');
    });

    it('follows the label\'s rung when one is named', function (string $size, string $type) {
        expect(Blade::render('<shape:description size="'.$size.'">Help</shape:description>'))
            ->toContain($type.' text-ink-muted');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('stays at the field size when no rung was named', function () {
        expect(Blade::render('<shape:description>Help</shape:description>'))
            ->toContain('text-sm text-ink-muted');
    });
});

describe('error', function () {
    it('reads the message out of the bag by name', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email" />'))
            ->toContain('The email field is required.')
            ->toContain('text-danger-on-tint');
    });

    it('renders nothing when the field is clean', function () {
        seedErrors(['other' => ['Something else went wrong.']]);

        expect(trim(Blade::render('<shape:error name="email" />')))->toBe('');
    });

    it('renders nothing when there is no bag to read', function () {
        expect(trim(Blade::render('<shape:error name="email" />')))->toBe('');
    });

    it('lets the slot say something the validator did not', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email">That address is already taken.</shape:error>'))
            ->toContain('That address is already taken.')
            ->not->toContain('The email field is required.');
    });

    it('reports the first message when a field failed more than one rule', function () {
        seedErrors(['email' => ['The email field is required.', 'The email must be valid.']]);

        expect(Blade::render('<shape:error name="email" />'))
            ->toContain('The email field is required.')
            ->not->toContain('The email must be valid.');
    });

    it('does not leave the field name on the element', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email" />'))->not->toContain('name="email"');
    });

    it('says what is wrong in words rather than in colour alone', function () {
        // The sentence is the signal that survives a reader who cannot see the
        // hue, which is what the rule actually asks for. The mark beside it is the
        // second signal rather than the first, which is why it is stripped here:
        // the message has to carry its meaning with the artwork taken away.
        seedErrors(['email' => ['The email field is required.']]);

        expect(strip_tags(Blade::render('<shape:error name="email" />')))
            ->toContain('The email field is required.');
    });

    it('marks the message so a long form shows where it failed', function () {
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email" />'))->toContain('<svg');
    });

    it('keeps the mark out of the accessibility tree', function () {
        // It repeats what the sentence beside it already says.
        seedErrors(['email' => ['The email field is required.']]);

        expect(Blade::render('<shape:error name="email" />'))->toContain('aria-hidden="true"');
    });

    it('draws the mark through the alias every library spells its own way', function () {
        // `error` is a name in Shape's vocabulary, not an icon in anyone's set:
        // Lucide draws it as `circle-alert` and Heroicons as `exclamation-circle`,
        // and the component asks for neither.
        seedErrors(['email' => ['The email field is required.']]);

        config()->set('shape.icons.aliases', ['error' => 'triangle-alert']);

        File::deleteDirectory(TestCase::iconPath());

        test()->artisan('shape:icon:add', ['name' => ['error'], '--no-clear' => true])->run();

        expect(Blade::render('<shape:error name="email" />'))->toContain('<svg');
    });
});
