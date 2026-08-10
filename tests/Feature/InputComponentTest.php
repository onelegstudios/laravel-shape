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

/**
 * The affix spans, out of the box and the control that sit between them. Narrowed
 * for `control()`'s reason and one more: the box and the control render the same
 * classes whether there is an affix or not, so a whole-render assertion would pass
 * on a class that landed on the wrong element -- or on no element at all.
 */
function affixes(string $html): string
{
    preg_match_all('/<span\b[^>]*>.*?<\/span>/s', $html, $matches);

    return implode('', $matches[0]);
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
    $html = Blade::render('<shape:input size="lg" icon-set="fixture" affix="segmented" prefix="$" :invalid="false" />');

    expect($html)
        ->not->toContain('size="lg"')
        ->not->toContain('icon-set=')
        ->not->toContain('affix=')
        ->not->toContain('prefix=')
        ->not->toContain('invalid=');
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.input', ['size' => 'lg', 'affix' => 'segmented']);

        // An affix is on the tag because `affix` has nothing to style without one,
        // which is the only reason the two renders are not bare inputs.
        expect(Blade::render('<shape:input prefix="$" />'))
            ->toBe(Blade::render('<shape:input size="lg" affix="segmented" prefix="$" />'));
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
        // Native chrome this component does not draw itself. `picker` is a variant
        // shape.css declares, because Tailwind names no such pseudo-element.
        expect(control(Blade::render('<shape:input type="'.$type.'" />')))
            ->toContain('picker:cursor-pointer')
            ->toContain('picker:opacity-60');
    })->with(['date', 'datetime-local', 'month', 'time', 'week']);

    it('gives the other two native controls the cursor a button should have', function (string $type, string $class) {
        expect(control(Blade::render('<shape:input type="'.$type.'" />')))->toContain($class);
    })->with([
        'the number field\'s spin buttons' => ['number', 'spinner:cursor-pointer'],
        'the search field\'s cancel button' => ['search', 'cancel:cursor-pointer'],
    ]);

    it('never dims the two parts Chromium hides on its own', function (string $type, string $variant) {
        // The load-bearing test in this file, and the one worth reading the reason
        // for. Chromium keeps the spin buttons and the cancel button hidden until
        // the field is hovered. An author `opacity` on either overrides that rest
        // state and pins the part visible -- so copying the picker's
        // `opacity-60 hover:opacity-100` across, which is the obvious way to make
        // these three branches look symmetrical, would leave a stepper sitting in
        // every number field on the page. Louder than doing nothing, not quieter.
        //
        // The picker earns its knock-back because Chromium draws that glyph at full
        // contrast always. There is no rest state there to preserve.
        expect(control(Blade::render('<shape:input type="'.$type.'" />')))
            ->toContain($variant.':cursor-pointer')
            ->not->toContain($variant.':opacity');
    })->with([
        'the number field\'s spin buttons' => ['number', 'spinner'],
        'the search field\'s cancel button' => ['search', 'cancel'],
    ]);

    it('reads the type the way a browser reads it, whatever the case', function (string $type, string $variant) {
        // HTML matches an input's type case-insensitively, so `type="Date"` is a date
        // field to the browser and has the calendar button to prove it. The four
        // branches in this component that ask what type they are holding lowercase
        // the answer first, so they agree with the browser rather than with the
        // spelling.
        expect(control(Blade::render('<shape:input type="'.$type.'" />')))
            ->toContain($variant.':cursor-pointer');
    })->with([
        'a capitalised date' => ['Date', 'picker'],
        'a shouted number' => ['NUMBER', 'spinner'],
        'a mixed-case search' => ['SeArCh', 'cancel'],
    ]);

    it('renders the type the call site spelled rather than the one it matched', function () {
        // Lowercasing settles the comparison, not the markup. The attribute is the
        // caller's to write.
        expect(control(Blade::render('<shape:input type="Email" />')))->toContain('type="Email"');
    });

    it('leaves the chrome classes off a field that has no chrome', function (string $variant) {
        // They are inert on a text input, which is not a reason to ship them.
        expect(Blade::render('<shape:input type="email" />'))->not->toContain($variant.':');
    })->with(['picker', 'spinner', 'cancel']);

    it('gives each type only its own chrome', function () {
        // Three branches reading one attribute, so the thing worth pinning is that
        // they stay mutually exclusive: a date field has no stepper to style and a
        // number field has no calendar button.
        expect(control(Blade::render('<shape:input type="number" />')))
            ->not->toContain('picker:')
            ->not->toContain('cancel:')
            ->and(control(Blade::render('<shape:input type="date" />')))
            ->not->toContain('spinner:')
            ->not->toContain('cancel:');
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

        // `aria-invalid` is the whole of the signal now: the danger classes ride in
        // every control's class list behind an `invalid:` variant, so what says a
        // field is wrong is the attribute the variant matches on. Asserting the
        // absence of a class name here would assert nothing -- it is always there.
        expect(Blade::render('<shape:input name="email" :invalid="false" />'))
            ->toContain('border-neutral-border')
            ->not->toContain('aria-invalid');
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

describe('hidden', function () {
    it('renders the control alone, with no box around it', function () {
        // The box is the one thing every other type gets. A hidden input is not a
        // control -- it is a value the form carries -- so there is nothing for a
        // border to enclose, and drawing one puts an empty bordered strip in the
        // form's flow.
        $html = Blade::render('<shape:input type="hidden" name="token" />');

        expect($html)
            ->not->toContain('<div')
            ->not->toContain('rounded-md')
            ->not->toContain('border-neutral-border')
            ->not->toContain('bg-surface');
    });

    it('hands the call site its own attributes back untouched', function () {
        expect(trim(Blade::render('<shape:input type="hidden" name="token" value="abc" />')))
            ->toBe('<input type="hidden" name="token" value="abc" />');
    });

    it('draws no chrome even when a chrome prop asked for it', function () {
        // A <label> pointing at a control that cannot be seen or focused is worse
        // than no label, and help text describes something the reader never meets.
        $html = Blade::render('<shape:input type="hidden" name="token" label="Token" description="Carried through." />');

        expect($html)
            ->not->toContain('<label')
            ->not->toContain('<div')
            ->not->toContain('Token')
            ->not->toContain('Carried through.');
    });

    it('derives no id, because nothing is going to point at one', function () {
        expect(control(Blade::render('<shape:input type="hidden" name="token" />')))
            ->not->toContain('id=');
    });

    it('follows an explicit id, which is on the bag like everything else', function () {
        expect(control(Blade::render('<shape:input type="hidden" name="token" id="mine" />')))
            ->toContain('id="mine"');
    });

    it('spends no generated id on a hidden input a label was aimed at', function () {
        // `$chrome` is what makes Control::resolve() take one from the sequence, so
        // the guard has to sit there rather than only on the branch below it. Two
        // renders of the same tag agreeing is what proves nothing was consumed
        // between them -- a burned id would show up as `shape-field-1` then
        // `shape-field-2`.
        $first = Blade::render('<shape:input type="hidden" label="Token" />');

        expect(Blade::render('<shape:input type="hidden" label="Token" />'))->toBe($first)
            ->and($first)->not->toContain('shape-field-');
    });

    it('says nothing about validity, having nothing to announce it to', function () {
        // The error bag has an opinion; a hidden input has no way to present it and
        // no assistive technology reaches it to hear one.
        seedErrors(['token' => ['The token field is required.']]);

        expect(control(Blade::render('<shape:input type="hidden" name="token" />')))
            ->not->toContain('aria-invalid');
    });

    it('skips the box whatever case the type was spelled in', function (string $type) {
        // The one where getting it wrong is visible: a browser reads `type="Hidden"`
        // as a hidden input, and a component that did not would put an empty bordered
        // strip in the form.
        expect(Blade::render('<shape:input type="'.$type.'" name="token" />'))
            ->not->toContain('<div')
            ->and(control(Blade::render('<shape:input type="'.$type.'" name="token" />')))
            ->toContain('type="'.$type.'"');
    })->with(['Hidden', 'HIDDEN', 'hIdDeN']);

    it('takes the box away from no other type', function (string $type) {
        // The guard against a branch that broadens: only this one spelling skips
        // the box, and `type="text"` is the default every unstated field lands on.
        expect(Blade::render('<shape:input type="'.$type.'" name="token" />'))
            ->toContain('border-neutral-border');
    })->with(['text', 'email', 'password', 'search', 'number', 'date']);
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

describe('prefix and suffix', function () {
    it('puts a prefix before the control and a suffix after it', function () {
        $html = Blade::render('<shape:input prefix="$" suffix="USD" />');

        expect($html)->toContain('$')->toContain('USD')
            ->and(strpos($html, '$'))->toBeLessThan(strpos($html, '<input'))
            ->and(strpos($html, '<input'))->toBeLessThan(strpos($html, 'USD'));
    });

    it('escapes a string affix, which came off an attribute', function () {
        expect(Blade::render('<shape:input :prefix="$mark" />', ['mark' => '<b>x</b>']))
            ->toContain('&lt;b&gt;x&lt;/b&gt;')
            ->not->toContain('<b>x</b>');
    });

    it('takes markup a string cannot carry from the nested component', function () {
        // The escape hatch. `<shape:input.prefix>` is a component like every other
        // part of a field, rather than a named slot -- which would be the one place
        // in the package that syntax appeared.
        expect(Blade::render('<shape:input><shape:input.prefix><b>x</b></shape:input.prefix></shape:input>'))
            ->toContain('<b>x</b>');
    });

    it('renders the prop and the nested component identically', function () {
        // The prop is a call into the same component, so there is one recipe rather
        // than two -- and this is the assertion that says so. Only the span is
        // compared: the two land in different places in the DOM on purpose, and the
        // `order-*` class on each is what puts them in the same place on screen.
        expect(affixes(Blade::render('<shape:input><shape:input.suffix>USD</shape:input.suffix></shape:input>')))
            ->toBe(affixes(Blade::render('<shape:input suffix="USD" />')));
    });

    it('draws both where a call site writes a prop and nests one too', function () {
        // Not the nearer one winning, which is what the named slot used to do. An
        // anonymous component cannot see what its children rendered -- the same
        // limitation the composed field's `aria-describedby` runs into -- so the
        // input has no way to know the slot drew a prefix and stand its own down.
        // Pinned so it stays a documented consequence rather than a surprise.
        $html = Blade::render('<shape:input prefix="attribute"><shape:input.prefix>nested</shape:input.prefix></shape:input>');

        expect($html)->toContain('attribute')->toContain('nested')
            ->and(substr_count($html, 'order-first'))->toBe(2);
    });

    it('ignores the named slot the nested component replaced', function () {
        // `<x-slot:prefix>` is not an API any more, and the way it fails is worth
        // pinning: Blade still fills `$prefix` with a `ComponentSlot`, which the
        // string guard drops -- and `@props` consumed the `prefix` attribute on the
        // same pass, so a call site writing both loses both. Nothing shipped with
        // the slot in it, so this is a pin rather than a deprecation.
        expect(affixes(Blade::render('<shape:input prefix="$"><x-slot:prefix>x</x-slot:prefix></shape:input>')))
            ->toBe('');
    });

    it('renders the nested component as a span, which the assertions here rely on', function () {
        // `affixes()` narrows on `<span>`. A `<div>` in either component would leave
        // every `not->toContain` in this block passing against an empty string.
        expect(Blade::render('<shape:input><shape:input.prefix>$</shape:input.prefix></shape:input>'))
            ->toContain('<span')
            ->and(Blade::render('<shape:input><shape:input.suffix>USD</shape:input.suffix></shape:input>'))
            ->toContain('<span');
    });

    it('sizes a nested affix to the field it is standing in', function (string $size, string $type) {
        // `@aware` reaching up to the input, which is the whole reason these are
        // components rather than markup the call site styles itself.
        expect(affixes(Blade::render('<shape:input size="'.$size.'"><shape:input.suffix>USD</shape:input.suffix></shape:input>')))
            ->toContain($type);
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('takes the treatment from the field as well', function () {
        expect(affixes(Blade::render('<shape:input affix="segmented"><shape:input.suffix>USD</shape:input.suffix></shape:input>')))
            ->toContain('self-stretch');
    });

    it('keeps the two values it inherits off the rendered element', function () {
        // Neither is declared a prop -- declaring one would let `@aware` beat a value
        // written on the tag, which is the wrong way round -- so nothing strips them
        // from the bag and the component has to do it by hand.
        expect(affixes(Blade::render('<shape:input><shape:input.suffix size="lg" affix="segmented">USD</shape:input.suffix></shape:input>')))
            ->toContain('text-base')
            ->toContain('self-stretch')
            ->not->toContain('size="lg"')
            ->not->toContain('affix=');
    });

    it('renders on its own outside any field', function () {
        // No input above it, so nothing to inherit and nothing to attach to. It falls
        // back to the configured rung and the `inline` floor, which is what keeps a
        // stray one a plain muted word rather than a plate bleeding out of whatever
        // it landed in.
        expect(Blade::render('<shape:input.prefix>$</shape:input.prefix>'))
            ->toContain('text-ink-muted')
            ->not->toContain('-my-');
    });

    it('draws no nested affix on a hidden input', function () {
        expect(trim(Blade::render('<shape:input type="hidden" name="token"><shape:input.suffix>USD</shape:input.suffix></shape:input>')))
            ->toBe('<input type="hidden" name="token" />');
    });

    it('sets an affix in the same type the value is set in', function (string $size) {
        // The one number this component shares with a table in another file. Read out
        // of both renders rather than restated, the way the input's padding is read
        // against the button's, so the claim breaks loudly if either copy moves.
        $html = Blade::render('<shape:input size="'.$size.'" suffix="USD" />');

        preg_match('/(text-(?:xs|sm|base|lg))/', control($html), $control);
        preg_match('/(text-(?:xs|sm|base|lg))/', affixes($html), $affix);

        expect($affix[1])->toBe($control[1]);
    })->with(['xs', 'sm', 'md', 'lg']);

    it('ignores a bare prefix attribute rather than stamping a 1 on the field', function () {
        // The one that would ship visibly wrong: a bare attribute arrives as `true`,
        // and `(string) true` is "1".
        expect(Blade::render('<shape:input prefix suffix />'))
            ->toBe(Blade::render('<shape:input />'));
    });

    it('keeps a zero, which is an affix like any other', function () {
        // Why the guard is `trim(...) !== ''` and not `empty()`.
        expect(affixes(Blade::render('<shape:input suffix="0" />')))->toContain('>0<');
    });

    it('sizes the affix to the rung the field resolved', function (string $size, string $type) {
        // The bargain the icon props make, made once more: the call site says the
        // size once, so a `$` cannot end up a size larger than the number beside it.
        expect(affixes(Blade::render('<shape:input prefix="$" size="'.$size.'" />')))->toContain($type);
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('draws no field around a control whose only extra is an affix', function () {
        // There is no label, no help text and no message in a `$`, so an affix is
        // not a chrome prop and does not expand the shorthand.
        expect(Blade::render('<shape:input prefix="$" name="amount" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });

    it('carries both through the shorthand', function () {
        // `@props` strips them off the bag, so the field branch has to forward them
        // by name the way it forwards the icons -- and a slot survives the trip as
        // an ordinary prop value.
        expect(Blade::render('<shape:input label="Price" prefix="$" suffix="USD" name="price" />'))
            ->toContain('<label')
            ->and(affixes(Blade::render('<shape:input label="Price" prefix="$" suffix="USD" name="price" />')))
            ->toContain('$')
            ->toContain('USD');
    });

    it('renders no affix on a hidden input', function () {
        // Nothing to hang one on. The box is what an affix sits in, and a hidden
        // input has none -- the same reason it draws no label.
        expect(trim(Blade::render('<shape:input type="hidden" name="token" prefix="$" suffix="USD" />')))
            ->toBe('<input type="hidden" name="token" />');
    });

    describe('segmented', function () {
        it('leaves the box alone in the inline treatment', function () {
            // The seam the wrapper shape was cut for: an inline affix is an ordinary
            // flex sibling, so nothing has to move to make room for it. No plate, no
            // bleed -- `order-*` is not in this list, because it is unconditional.
            expect(Blade::render('<shape:input prefix="$" suffix="USD" />'))
                ->not->toContain('-my-')
                ->not->toContain('-ms-')
                ->not->toContain('-me-')
                ->not->toContain('self-stretch')
                ->not->toContain('border-inherit')
                ->not->toContain('rounded-s-md')
                ->not->toContain('rounded-e-md');
        });

        it('gives the affix a plate against the frame border', function () {
            expect(affixes(Blade::render('<shape:input prefix="$" affix="segmented" />')))
                ->toContain('self-stretch')
                ->toContain('bg-surface-muted')
                ->toContain('border-e')
                ->toContain('rounded-s-md');
        });

        it('centres the plate, which stretching would otherwise not', function () {
            // `self-stretch` blockifies the span, so without these the affix sits at
            // the top of a box a whole `py` taller than the line it belongs on.
            expect(affixes(Blade::render('<shape:input suffix="USD" affix="segmented" />')))
                ->toContain('flex')
                ->toContain('items-center');
        });

        it('bleeds by the rung the field resolved', function (string $size, string $start, string $end) {
            // Every number is the rung's own: the frame's `py` cancelled, its leading
            // padding cancelled, its padding restored on the plate, and one more gap
            // to put the value the same distance from the divider that it sits from
            // the border in a plain field.
            $html = affixes(Blade::render('<shape:input prefix="$" suffix="USD" affix="segmented" size="'.$size.'" />'));

            expect($html)->toContain($start)->toContain($end);
        })->with([
            'xs' => ['xs', '-my-1 -ms-2 px-2 me-1', '-my-1 -me-2 px-2 ms-1'],
            'sm' => ['sm', '-my-1.5 -ms-3 px-3 me-1.5', '-my-1.5 -me-3 px-3 ms-1.5'],
            'md' => ['md', '-my-2 -ms-4 px-4 me-2', '-my-2 -me-4 px-4 ms-2'],
            'lg' => ['lg', '-my-2.5 -ms-5 px-5 me-2.5', '-my-2.5 -me-5 px-5 ms-2.5'],
        ]);

        it('cancels exactly the padding the frame put there, and tops the gap back up', function (string $size) {
            // The most valuable test in this file, now that the plate's table lives
            // in another one. Nothing in `input/prefix.blade.php` names the input's
            // `$rungs`, so these two renders are the only place the relationship is
            // written down: the plate cancels the frame's `px` to reach the border,
            // and adds the frame's `gap` back so the value stands the same distance
            // from the divider that it stands from the border in a plain field.
            //
            // Read out of both renders rather than restated, so the claim breaks
            // loudly if either table is edited alone.
            $plain = Blade::render('<shape:input size="'.$size.'" />');
            $plate = affixes(Blade::render('<shape:input prefix="$" affix="segmented" size="'.$size.'" />'));

            preg_match('/(?<!-)\bpx-([\d.]+)/', $plain, $padding);
            preg_match('/\bgap-([\d.]+)/', $plain, $gap);
            preg_match('/-ms-([\d.]+)/', $plate, $cancel);
            preg_match('/(?<!-)\bme-([\d.]+)/', $plate, $topUp);

            expect($cancel[1])->toBe($padding[1])
                ->and($topUp[1])->toBe($gap[1]);
        })->with(['xs', 'sm', 'md', 'lg']);

        it('orders every affix out to its own edge, in both treatments', function () {
            // Unconditional, and forced rather than chosen: the prop renders this
            // component from inside the box while a call site nests the same one in
            // the slot, so one set of classes has to land it correctly from two
            // different places in the markup. Ordering it to the edge is the only
            // answer that works from both -- which is also why an affix sits outside
            // a leading mark rather than beside the value.
            expect(affixes(Blade::render('<shape:input prefix="$" suffix="USD" />')))
                ->toContain('order-first')
                ->toContain('order-last')
                ->and(affixes(Blade::render('<shape:input prefix="$" suffix="USD" affix="segmented" />')))
                ->toContain('order-first')
                ->toContain('order-last');
        });

        it('inherits the divider colour rather than working it out', function () {
            // The decision that made a nested affix possible at all. The divider has
            // to be the frame's border colour, and a component in the slot cannot
            // work that out -- the input often resolves `invalid` from `wire:model`,
            // and `@aware` cannot read a key that is not a legal PHP variable name.
            //
            // Not optional, either: Preflight sets `border: 0 solid`, and that
            // shorthand resets `border-color` to `currentColor`, so leaving it off
            // draws the divider in the affix's own muted ink rather than not at all.
            seedErrors(['amount' => ['The amount field is required.']]);

            expect(affixes(Blade::render('<shape:input prefix="$" affix="segmented" name="amount" />')))
                ->toContain('border-inherit')
                ->not->toContain('border-danger-border')
                ->and(affixes(Blade::render('<shape:input prefix="$" affix="segmented" name="total" />')))
                ->toContain('border-inherit')
                ->not->toContain('border-neutral-border');
        });

        it('follows a border colour the call site put on the field', function () {
            // What inheriting earns on the way past, and a bug the hardcoded colour
            // it replaced actually had: the frame took the caller's green and the
            // divider stayed the packaged grey.
            $html = Blade::render('<shape:input prefix="$" affix="segmented" class="border-emerald-500" />');

            expect($html)->toContain('border-emerald-500')
                ->and(affixes($html))->toContain('border-inherit');
        });

        it('never puts the ring on the plate, only on the box', function () {
            expect(affixes(Blade::render('<shape:input prefix="$" affix="segmented" />')))
                ->not->toContain('focus-within:');
        });

        it('takes its treatment from config', function () {
            config()->set('shape.components.input', ['affix' => 'segmented']);

            expect(Blade::render('<shape:input prefix="$" />'))
                ->toBe(Blade::render('<shape:input prefix="$" affix="segmented" />'));
        });

        it('ships a config default that matches the fallback baked into the component', function () {
            $configured = Blade::render('<shape:input prefix="$" />');

            config()->set('shape.components.input', null);

            expect(Blade::render('<shape:input prefix="$" />'))->toBe($configured);
        });

        it('falls back to inline when given a treatment it does not have', function (string $affix) {
            // A closed set of two. `affix` is the one prop here whose name reads like
            // it takes the affix itself, so `affix="$"` is a plausible slip and lands
            // on a plain field rather than an unstyled plate.
            expect(Blade::render('<shape:input prefix="$" affix="'.$affix.'" />'))
                ->toBe(Blade::render('<shape:input prefix="$" />'));
        })->with([
            'a treatment that does not exist' => ['plate'],
            'the affix itself, reaching for the wrong prop' => ['$'],
            'a boolean that was stringified on the way in' => ['true'],
        ]);
    });
});
