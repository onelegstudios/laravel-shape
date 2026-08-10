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

/**
 * The `<fieldset>` opening tag alone, out of the group it wraps.
 *
 * Named apart from the radio and checkbox suites' `dial()` and `box()` because
 * Pest puts every module-level function in one namespace, however many files
 * they were written in.
 */
function fence(string $html): string
{
    preg_match('/<fieldset\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

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

describe('a group', function () {
    it('opens a fieldset when a legend is named', function () {
        // The whole point: a set of radios called `plan` is a group, and nothing
        // but the element says so.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('<fieldset')
            ->toContain('>Plan</legend>');
    });

    it('stays a plain div when only a label was named', function () {
        // A field around one control is a label and a column: naming `label` draws
        // a plain div, and only `legend` opens a fieldset.
        $html = Blade::render('<shape:field name="email" label="Email"><shape:input /></shape:field>');

        expect($html)
            ->toContain('for="email"')
            ->toContain('flex flex-col gap-1.5')
            ->not->toContain('<fieldset');
    });

    it('keeps the column off the fieldset, because a legend is not laid out in it', function () {
        // A rendered <legend> is painted into the border box rather than placed as
        // a child, so a `gap` out here would space every part except the one that
        // needs it. The wrapper inside carries the column, and it comes after the
        // legend.
        $html = Blade::render('<shape:field name="plan" legend="Plan"><shape:radio value="free" /></shape:field>');

        expect(fence($html))
            ->toContain('min-w-0')
            ->not->toContain('flex flex-col');

        expect(strpos($html, '</legend>'))->toBeLessThan(strpos($html, 'flex flex-col gap-1.5'));
    });

    it('lets a group shrink inside a flex row', function () {
        // `fieldset` ships a UA `min-inline-size: min-content` that Preflight does
        // not reset, which is the one way it behaves unlike the <div> it replaces.
        expect(fence(Blade::render('<shape:field name="plan" legend="Plan" />')))
            ->toContain('min-w-0');
    });

    it('draws the legend rather than a label when both were named', function () {
        // A <label for="plan"> inside a fieldset points at an id no element renders
        // -- the options are `plan-free` and `plan-pro` -- which is the finding this
        // mode exists to remove. Precedence rather than an exception: nothing in
        // this directory throws.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan" label="Plan">
                <shape:radio value="free" label="Free" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('>Plan</legend>')
            ->not->toContain('for="plan"');
    });

    it('names the group description on the fieldset itself', function () {
        // The field drew the sentence, so it knows the id exists. No option claims
        // this one: a radio's own description is scoped by value.
        $html = Blade::render('<shape:field name="plan" legend="Plan" description="Change it whenever." />');

        expect(fence($html))->toContain('aria-describedby="plan-description"');
        expect($html)->toContain('id="plan-description"');
    });

    it('names the trailing description after the leading one', function () {
        $html = Blade::render('<shape:field name="plan" legend="Plan" description="One" description-trailing="Two" />');

        expect(fence($html))
            ->toContain('aria-describedby="plan-description plan-description-trailing"');
    });

    it('names no description it did not draw', function () {
        // A composed group writes its own `aria-describedby`, exactly as a composed
        // field does: an anonymous component cannot see which of its children drew
        // something, and naming an id that was never rendered is the finding.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:description>Change it whenever.</shape:description>
            </shape:field>
        BLADE);

        expect(fence($html))->not->toContain('aria-describedby');
    });

    it('leaves the message off the fieldset, because every option already carries it', function () {
        // Named here too, the sentence would be read on entering the group and
        // again on the first option.
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect(fence($html))->not->toContain('plan-error');

        // Two options describing it, and the message's own id. A fourth would be
        // the fieldset.
        expect(substr_count($html, 'plan-error'))->toBe(3);
    });

    it('prints the group message once', function () {
        seedErrors(['plan' => ['Pick a plan.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="plan" legend="Plan">
                <shape:radio value="free" label="Free" />
                <shape:radio value="pro" label="Pro" />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'Pick a plan.'))->toBe(1);
    });

    it('takes an aria-describedby from the call site over the one it derives', function () {
        $html = Blade::render('<shape:field name="plan" legend="Plan" description="Help" aria-describedby="mine" />');

        expect(fence($html))
            ->toContain('aria-describedby="mine"')
            ->not->toContain('plan-description');
    });

    it('puts the call site\'s classes on the box rather than on the column', function () {
        // `max-w-sm` and a border of your own are things said about the element you
        // can see. The cost is that `gap-4` retunes a plain field and not a group.
        expect(fence(Blade::render('<shape:field name="plan" legend="Plan" class="max-w-sm" />')))
            ->toContain('min-w-0')
            ->toContain('max-w-sm');
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
        // control's scale, so a label with no rung named renders at the field's.
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

describe('legend', function () {
    it('carries the group name at the label\'s weight and size', function () {
        // A group's name and a field's name are the same line of type in two
        // elements, and they should measure the same down a column.
        expect(Blade::render('<shape:legend>Plan</shape:legend>'))
            ->toContain('text-sm font-medium text-ink');
    });

    it('owns its own bottom margin, because no gap can reach it', function () {
        // The one place this family puts spacing on a part rather than on the
        // parent. A rendered <legend> is out of its fieldset's formatting context,
        // so a `gap` on any ancestor misses it.
        expect(Blade::render('<shape:legend>Plan</shape:legend>'))->toContain('mb-1.5');
    });

    it('points at nothing, because it has nothing to point at', function () {
        // A legend names the fieldset it opens by sitting in it. There is no `for`
        // to resolve and no name to inherit, which is why this is its own component
        // rather than the label wearing a different tag.
        expect(Blade::render('<shape:field name="plan"><shape:legend>Plan</shape:legend></shape:field>'))
            ->not->toContain('for=');
    });

    it('follows a rung when one is named', function (string $size, string $type) {
        // A `size="lg"` group's options render `text-base`, and a `text-sm` name
        // above them is visibly the wrong size.
        expect(Blade::render('<shape:legend size="'.$size.'">Plan</shape:legend>'))
            ->toContain($type.' font-medium');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('stays at the field size when no rung was named', function () {
        expect(Blade::render('<shape:legend>Plan</shape:legend>'))
            ->toContain('text-sm font-medium');
    });

    it('falls back to the field size for a rung it does not have', function () {
        expect(Blade::render('<shape:legend size="huge">Plan</shape:legend>'))
            ->toBe(Blade::render('<shape:legend>Plan</shape:legend>'));
    });

    it('does not leak the rung onto the element', function () {
        expect(Blade::render('<shape:legend size="lg">Plan</shape:legend>'))
            ->not->toContain('size=');
    });

    it('takes classes from the call site without losing its own', function () {
        expect(Blade::render('<shape:legend class="uppercase">Plan</shape:legend>'))
            ->toContain('uppercase')
            ->toContain('font-medium');
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

describe('the aware boundary', function () {
    // Blaze compiles `@aware` differently from Blade, and this family is on Blaze.
    // Two differences matter, and both are invisible in the markup: the value each
    // implementation hands back, and what each leaves on `$attributes`.
    //
    // The value turns out to agree. Blade's `getConsumableComponentData()` checks
    // the component's own data before walking ancestors, and Blaze only walks
    // ancestors -- but its compiler pushes the call site's own attributes onto that
    // stack before the child runs, so the component's own value is on top either
    // way. The cases above cover that for `for` and `name`.
    //
    // What does not agree is the bag. Blaze's AwareCompiler unsets each consumed
    // key from `$attributes`; Blade's leaves it there. Every file in this family
    // saves the bag and puts it back around the directive, and these are the two
    // reads that break the moment somebody takes that out.

    it('reads the error bag under a name written on the message rather than the field', function () {
        // The precedence case above proves the *id* comes from the nearer name. This
        // one proves the lookup does too, which the slot spelling cannot show: an
        // error given slot content renders that content whatever name it resolved.
        seedErrors([
            'outer' => ['The outer one.'],
            'inner' => ['The inner one.'],
        ]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="outer">
                <shape:error name="inner" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('The inner one.')
            ->not->toContain('The outer one.');
    });

    it('tells a name written on a control from one it inherited', function () {
        // `Control::resolve()` draws that line by asking whether the bag still
        // carries the name, and it is what decides whether a standalone control
        // prints its own message or leaves it to an enclosing field. Under Blaze the
        // key is gone from the bag by the time it looks, unless it was put back --
        // and every named control then reads as inherited, silently.
        seedErrors(['terms' => ['You must accept the terms.']]);

        $standalone = Blade::render('<shape:checkbox label="I agree" name="terms" value="1" />');

        $enclosed = Blade::render(<<<'BLADE'
            <shape:field name="terms" legend="Terms">
                <shape:checkbox label="I agree" value="1" />
            </shape:field>
        BLADE);

        expect($standalone)->toContain('You must accept the terms.');

        // Once, from the field, rather than once from the field and once per box.
        expect(substr_count($enclosed, 'You must accept the terms.'))->toBe(1);
    });

    it('keeps a consumed key on the element when the element is the whole component', function () {
        // The hidden input echoes the bag whole -- there is no box to draw and no
        // label to point at -- so a `name` consumed by `@aware` and not put back
        // would leave the form submitting nothing at all.
        expect(Blade::render('<shape:input type="hidden" name="token" value="abc" />'))
            ->toContain('name="token"');
    });
});
