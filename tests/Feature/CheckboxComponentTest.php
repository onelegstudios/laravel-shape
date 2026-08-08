<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Icons\Libraries;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input type="checkbox">` alone, out of the cell the marks share with it.
 * Named apart from the sibling suites' extractors because Pest puts every
 * module-level function in one namespace.
 */
function box(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

// Every render draws two marks, so the artwork has to be on disk first.
beforeEach(function () {
    publishRequiredIcons();
});

afterEach(function () {
    File::deleteDirectory(TestCase::iconPath());
});

it('renders a checkbox with the marks stacked on it', function () {
    $html = Blade::render('<shape:checkbox />');

    expect(box($html))->toContain('type="checkbox"')->toContain('peer')
        ->and(substr_count($html, '<svg'))->toBe(2);
});

it('draws the box itself rather than leaving the operating system\'s', function () {
    expect(box(Blade::render('<shape:checkbox />')))->toContain('appearance-none');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:checkbox />'))->toBe(Blade::render('<shape:checkbox size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:checkbox size="huge" />'))->toBe(Blade::render('<shape:checkbox />'));
});

it('pairs each rung\'s box with a cell, a mark and a gap', function (string $size, string $cell, string $box, string $mark) {
    // The table this component is built on. The cell is the label's own line box,
    // which is what centres the box on the first line of the words with no `mt-*`
    // to re-derive when the type moves.
    $html = Blade::render('<shape:checkbox label="Agree" size="'.$size.'" />');

    expect($html)->toContain($cell)->toContain($mark)
        ->and(box($html))->toContain($box);
})->with([
    'xs holds the 16px floor' => ['xs', 'h-4', 'size-4', 'size-3.5'],
    'sm is 18 in a 20px line' => ['sm', 'h-5', 'size-4.5', 'size-3.5'],
    'md is exactly a text-sm line box' => ['md', 'h-5', 'size-5', 'size-4'],
    'lg is the only rung clearing 24px' => ['lg', 'h-6', 'size-6', 'size-5'],
]);

it('does not shrink the box below a target anybody can hit', function () {
    // `xs` keeps its smallness in the type and the gap. 14px with a 12px mark
    // inside it is unhittable, and there is no icon rung below `xs` to put in it.
    expect(box(Blade::render('<shape:checkbox size="xs" />')))
        ->toContain('size-4')
        ->not->toContain('size-3.5');
});

it('keeps the box from shrinking when the label is long', function () {
    expect(Blade::render('<shape:checkbox label="A rather long label" />'))->toContain('shrink-0');
});

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:checkbox size="lg" :invalid="false" />');

    expect($html)->not->toContain('size="lg"')->not->toContain('invalid=');
});

describe('the marks', function () {
    it('shows the tick only when the box is checked', function () {
        expect(Blade::render('<shape:checkbox />'))->toContain('peer-checked:block');
    });

    it('shows the bar only when the box is indeterminate', function () {
        expect(Blade::render('<shape:checkbox />'))->toContain('peer-indeterminate:block');
    });

    it('hides the tick behind the bar without relying on Tailwind\'s sort order', function () {
        // A box can be checked *and* indeterminate at once -- setting
        // `el.indeterminate` does not clear `checked` -- so both selectors match.
        // Two `peer-*` variants of `display` are equal weight, so `hidden` versus
        // `block` would come down to sort order. Opacity touches a different
        // property, so there is nothing to resolve.
        expect(Blade::render('<shape:checkbox />'))
            ->toContain('peer-indeterminate:opacity-0')
            ->not->toContain('peer-indeterminate:hidden');
    });

    it('draws both marks on the checked fill', function () {
        expect(Blade::render('<shape:checkbox />'))->toContain('text-primary-on-fill');
    });

    it('hands a click on a mark through to the control underneath', function () {
        expect(Blade::render('<shape:checkbox />'))->toContain('pointer-events-none');
    });

    it('keeps both marks out of the accessibility tree', function () {
        // What a reader is told is the control's checked state; the mark is a
        // picture of it.
        expect(substr_count(Blade::render('<shape:checkbox />'), 'aria-hidden="true"'))->toBe(2);
    });

    it('draws them from names the package promises to publish', function () {
        // Which is the whole reason they are in the alias table rather than written
        // into this component as `check` and `minus`: being there is what makes
        // `shape:install` publish them, `shape:icon:check` report them missing, and
        // `shape:icon:remove` hold them back without `--force`.
        expect(Libraries::required())
            ->toContain('checkbox-check')
            ->toContain('checkbox-indeterminate');
    });
});

describe('the box', function () {
    it('fills solid when checked, border and all', function () {
        // A *fill* token pointed at a border on purpose: the checked box is solid,
        // and its border is the fill. `border-transparent` would leave the surface
        // showing through as a hairline.
        expect(box(Blade::render('<shape:checkbox />')))
            ->toContain('checked:bg-primary-fill')
            ->toContain('checked:border-primary-fill');
    });

    it('fills the same way when indeterminate', function () {
        expect(box(Blade::render('<shape:checkbox />')))
            ->toContain('indeterminate:bg-primary-fill')
            ->toContain('indeterminate:border-primary-fill');
    });

    it('rings on the keyboard rather than on every click', function () {
        // The one place in the family that differs. The control *is* the box, so
        // `focus-within` would just be `focus` -- and a checked box wearing a
        // permanent ring is noise.
        expect(box(Blade::render('<shape:checkbox />')))
            ->toContain('focus-visible:outline-neutral-ring')
            ->not->toContain('focus-within:');
    });

    it('fades when disabled rather than restating its colours', function () {
        // Restated colours would have to beat `checked:`, which they do only
        // because `disabled:` sorts later. Opacity touches a property nothing else
        // here touches.
        expect(box(Blade::render('<shape:checkbox />')))
            ->toContain('disabled:opacity-50')
            ->toContain('disabled:cursor-not-allowed');
    });

    it('is square with a small radius rather than round', function () {
        // Round is a radio, and the shape is the only thing telling them apart.
        expect(box(Blade::render('<shape:checkbox />')))
            ->toContain('rounded-sm')
            ->not->toContain('rounded-full');
    });
});

describe('a group', function () {
    // Three boxes bound to one array are one field and three controls. Without a
    // discriminator all three would answer to one id and all three labels would
    // click through to the first.

    it('gives every option an id of its own', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags" legend="Tags">
                <shape:checkbox value="php" label="PHP" />
                <shape:checkbox value="laravel" label="Laravel" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('id="tags-php"')
            ->toContain('id="tags-laravel"')
            ->toContain('for="tags-php"')
            ->toContain('for="tags-laravel"');
    });

    it('scopes each help text by the option rather than the field', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags">
                <shape:checkbox value="php" label="PHP" description="The language." />
            </shape:field>
        BLADE);

        expect($html)->toContain('id="tags-php-description"')
            ->and(box($html))->toContain('aria-describedby="tags-php-description"');
    });

    it('submits every box under the one field name', function () {
        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags">
                <shape:checkbox value="php" label="PHP" />
            </shape:field>
        BLADE);

        expect(box($html))->toContain('name="tags"');
    });

    it('prints the message once, from the field rather than per box', function () {
        // A validator has one opinion per name however many controls carry it, and
        // three copies of one sentence is not a message.
        seedErrors(['tags' => ['Pick at least one.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags" legend="Tags">
                <shape:checkbox value="php" label="PHP" />
                <shape:checkbox value="laravel" label="Laravel" />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'Pick at least one.'))->toBe(1);
    });

    it('is announced as a group rather than as loose boxes', function () {
        // The radio suite says the same thing for the same reason: without the
        // element, a set wired by `name` reads as unrelated controls plus a
        // floating label -- and that label would be a `for="tags"` pointing at an
        // id no box carries.
        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags" legend="Tags">
                <shape:checkbox value="php" label="PHP" />
                <shape:checkbox value="laravel" label="Laravel" />
            </shape:field>
        BLADE);

        expect($html)
            ->toContain('<fieldset')
            ->toContain('>Tags</legend>')
            ->not->toContain('for="tags"');
    });

    it('discriminates by value even for a name that looks like one field', function () {
        // The ordinary Livewire spelling puts three boxes on `wire:model="tags"`
        // with no brackets anywhere, so sniffing for a `[]` suffix would be
        // silently wrong exactly where it matters most.
        $html = Blade::render('<shape:checkbox wire:model="tags" value="php" label="PHP" />');

        expect($html)->toContain('id="tags-php"');
    });
});

describe('standing alone', function () {
    it('is the whole field, so it says what went wrong itself', function () {
        // Nothing around it owns the message, and a consent checkbox that fails
        // validation silently is the bug this covers.
        seedErrors(['terms' => ['You must accept the terms.']]);

        expect(Blade::render('<shape:checkbox label="I agree" name="terms" value="1" />'))
            ->toContain('You must accept the terms.');
    });

    it('leaves the message to the field around it when there is one', function () {
        // An enclosing field is what named this box, which means it is one of a
        // group -- and the field prints the sentence itself.
        seedErrors(['tags' => ['Pick at least one.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags">
                <shape:checkbox value="php" label="PHP" />
            </shape:field>
        BLADE);

        expect($html)->not->toContain('Pick at least one.');
    });

    it('prints no message when the validator has nothing to say', function () {
        expect(Blade::render('<shape:checkbox label="I agree" name="terms" />'))
            ->not->toContain('text-danger-on-tint');
    });

    it('reads as a row rather than a column', function () {
        // A checkbox's label names the option beside it rather than the field above
        // it, and reading them in the other order is reading a different sentence.
        expect(Blade::render('<shape:checkbox label="I agree" />'))
            ->toContain('flex items-start')
            ->not->toContain('flex flex-col gap-1.5');
    });

    it('wraps a long label under its own first line', function () {
        expect(Blade::render('<shape:checkbox label="I agree" />'))->toContain('items-start');
    });

    it('scales the words with the rung', function (string $size, string $type) {
        // Which is why `size` exists on the label at all: here the label shares a
        // line with the box, so the row's line box is whatever the words set.
        expect(Blade::render('<shape:checkbox label="Agree" size="'.$size.'" />'))
            ->toContain($type.' font-medium');
    })->with([
        'xs' => ['xs', 'text-xs'],
        'sm' => ['sm', 'text-sm'],
        'md' => ['md', 'text-sm'],
        'lg' => ['lg', 'text-base'],
    ]);

    it('has no trailing description, because a row has one side for words', function () {
        // A field stacks its parts, so "before" and "after" are two places. A
        // checkbox is a row: a second block under the first is the first block's
        // second paragraph.
        expect(box(Blade::render('<shape:checkbox description-trailing="Extra" />')))
            ->toContain('description-trailing="Extra"');
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.checkbox', ['size' => 'lg']);

        expect(Blade::render('<shape:checkbox />'))->toBe(Blade::render('<shape:checkbox size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:checkbox />');

        config()->set('shape.components.checkbox', null);

        expect(Blade::render('<shape:checkbox />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unsized box', function (mixed $configured) {
        config()->set('shape.components.checkbox', $configured);

        expect(box(Blade::render('<shape:checkbox />')))->toContain('size-5');
    })->with([
        'the whole block removed' => [null],
        'an empty block' => [[]],
        'a block missing every key' => [['unrelated' => 'value']],
        'a value of the wrong type' => [['size' => ['lg']]],
        'a block that is not an array' => ['lg'],
    ]);
});

describe('attributes', function () {
    it('puts the class on the cell and everything else on the control', function () {
        $html = Blade::render('<shape:checkbox class="mt-1" name="terms" value="1" required />');

        expect($html)->toContain('mt-1')
            ->and(box($html))
            ->not->toContain('mt-1')
            ->toContain('name="terms"')
            ->toContain('value="1"')
            ->toContain('required');
    });

    it('keeps the value on the element as well as using it for the id', function () {
        // It has to reach the element for the form to submit anything, so reading it
        // is a read rather than a claim.
        expect(box(Blade::render('<shape:checkbox name="terms" value="1" />')))
            ->toContain('value="1"')
            ->toContain('id="terms-1"');
    });

    it('lets the call site say the box is checked', function () {
        expect(box(Blade::render('<shape:checkbox checked />')))->toContain('checked');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(box(Blade::render('<shape:checkbox '.$binding.'="terms" />')))
            ->toContain($binding.'="terms"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['terms' => ['You must accept the terms.']]);

        expect(box(Blade::render('<shape:checkbox name="terms" />')))
            ->toContain('border-danger-border')
            ->toContain('aria-invalid="true"');
    });

    it('rings in danger as well as bordering in it', function () {
        seedErrors(['terms' => ['Required.']]);

        expect(box(Blade::render('<shape:checkbox name="terms" />')))
            ->toContain('focus-visible:outline-danger-ring');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(box(Blade::render('<shape:checkbox name="terms" :invalid="true" />')))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['terms' => ['Required.']]);

        expect(box(Blade::render('<shape:checkbox name="terms" :invalid="false" />')))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(box(Blade::render('<shape:checkbox name="terms" />')))->toContain('border-neutral-border');
    });

    it('marks every box in a group the validator minds', function () {
        seedErrors(['tags' => ['Pick at least one.']]);

        $html = Blade::render(<<<'BLADE'
            <shape:field name="tags">
                <shape:checkbox value="php" />
                <shape:checkbox value="laravel" />
            </shape:field>
        BLADE);

        expect(substr_count($html, 'aria-invalid="true"'))->toBe(2);
    });
});
