<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Onelegstudios\Shape\Tests\TestCase;

/**
 * The `<input type="file">` alone, out of the box the wrapper draws around it.
 * Named apart from the sibling suites' extractors because Pest puts every
 * module-level function in one namespace.
 */
function picker(string $html): string
{
    preg_match('/<input\b[^>]*>/', $html, $matches);

    return $matches[0] ?? '';
}

it('renders a file input inside the box that carries its border', function () {
    $html = Blade::render('<shape:file />');

    expect($html)
        ->toContain('<input')
        ->toContain('border-neutral-border')
        ->toContain('bg-surface')
        ->and(picker($html))->toContain('type="file"');
});

it('sizes to md when asked for nothing', function () {
    expect(Blade::render('<shape:file />'))->toBe(Blade::render('<shape:file size="md" />'));
});

it('falls back to the default size when given one it does not have', function () {
    expect(Blade::render('<shape:file size="huge" />'))->toBe(Blade::render('<shape:file />'));
});

it('gives each rung its own box, its own type and its own button offset', function (string $size, string $box, string $type, string $offset) {
    $html = Blade::render('<shape:file size="'.$size.'" />');

    expect($html)->toContain($box)
        ->and(picker($html))->toContain($type)->toContain($offset);
})->with([
    'xs' => ['xs', 'gap-1 px-2 py-1', 'text-xs', 'file:me-1'],
    'sm' => ['sm', 'gap-1.5 px-3 py-1.5', 'text-sm', 'file:me-1.5'],
    'md' => ['md', 'gap-2 px-4 py-2', 'text-sm', 'file:me-2'],
    'lg' => ['lg', 'gap-2.5 px-5 py-2.5', 'text-base', 'file:me-2.5'],
]);

it('holds the button off the filename by the gap the rung holds a mark off a value by', function (string $size) {
    // Not two tables that happen to agree. The button is the control's own
    // pseudo-element rather than a flex sibling, so the box's `gap` has nothing to
    // apply to and the space has to be spelled as a margin -- taking the number
    // from the same place is what keeps the two spacings identical.
    $html = Blade::render('<shape:file size="'.$size.'" />');

    preg_match('/gap-([\d.]+)/', $html, $gap);

    expect(picker($html))->toContain('file:me-'.$gap[1]);
})->with(['xs', 'sm', 'md', 'lg']);

it('stands on the padding its input rung stands on', function (string $size) {
    // Which is what the invisible button buys: with its height reduced to its own
    // line box, a file field and a text field of the same rung sit level in a row.
    preg_match('/(py-[\d.]+)/', Blade::render('<shape:file size="'.$size.'" />'), $file);
    preg_match('/(py-[\d.]+)/', Blade::render('<shape:input size="'.$size.'" />'), $input);

    expect($file[1])->toBe($input[1]);
})->with(['xs', 'sm', 'md', 'lg']);

it('does not leak the styling props onto the rendered elements', function () {
    $html = Blade::render('<shape:file size="lg" icon-set="default" :invalid="false" />');

    expect($html)
        ->not->toContain('size="lg"')
        ->not->toContain('icon-set=')
        ->not->toContain('invalid=');
});

describe('the button', function () {
    it('gives up its own chrome so the field is one frame rather than two', function () {
        // Left with a border and a radius of its own it would be a second frame
        // inside the first, which is two borders and two radii for one field.
        expect(picker(Blade::render('<shape:file />')))
            ->toContain('file:border-0')
            ->toContain('file:bg-transparent')
            ->toContain('file:p-0');
    });

    it('reads as the action inside the field', function () {
        // Weight and colour are what carry that: `primary-on-tint` is the token a
        // `ghost` button takes, which is the right relative -- a link inside a
        // field rather than a filled control sitting in one.
        expect(picker(Blade::render('<shape:file />')))
            ->toContain('file:font-medium')
            ->toContain('file:text-primary-on-tint');
    });

    it('takes a pointer cursor, which nothing else would give it', function () {
        // Nothing here touches `appearance`, so it otherwise inherits the field's.
        expect(picker(Blade::render('<shape:file />')))->toContain('file:cursor-pointer');
    });

    it('spells the disabled variant in the order that matches something', function () {
        // Variants apply left to right: `disabled:file:` is
        // `&:disabled::file-selector-button`, the button of a disabled input.
        // `file:disabled:` would be `::file-selector-button:disabled`, and a
        // pseudo-element never matches `:disabled`.
        expect(picker(Blade::render('<shape:file />')))
            ->toContain('disabled:file:cursor-not-allowed')
            ->not->toContain('file:disabled:');
    });
});

describe('the filename', function () {
    it('reads as a report rather than as a value somebody typed', function () {
        // Which is what `ink-muted` covers, per shape.css's own page surfaces: the
        // placeholder, the help text, and the value you cannot edit.
        expect(picker(Blade::render('<shape:file />')))
            ->toContain('text-ink-muted')
            ->not->toContain(' text-ink ');
    });
});

describe('config', function () {
    it('takes the value of every unnamed prop from config', function () {
        config()->set('shape.components.file', ['size' => 'lg']);

        expect(Blade::render('<shape:file />'))->toBe(Blade::render('<shape:file size="lg" />'));
    });

    it('ships config defaults that match the fallbacks baked into the component', function () {
        $configured = Blade::render('<shape:file />');

        config()->set('shape.components.file', null);

        expect(Blade::render('<shape:file />'))->toBe($configured);
    });

    it('falls back to a packaged default rather than rendering an unstyled field', function (mixed $configured) {
        config()->set('shape.components.file', $configured);

        expect(Blade::render('<shape:file />'))->toContain('gap-2 px-4 py-2');
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
        $html = Blade::render('<shape:file class="max-w-sm" name="avatar" accept="image/*" required />');

        expect($html)->toContain('max-w-sm')
            ->and(picker($html))
            ->not->toContain('max-w-sm')
            ->toContain('name="avatar"')
            ->toContain('accept="image/*"')
            ->toContain('required');
    });

    it('takes more than one file when asked', function () {
        expect(picker(Blade::render('<shape:file multiple />')))->toContain('multiple');
    });

    it('hands the Livewire binding to the control untouched', function (string $binding) {
        expect(picker(Blade::render('<shape:file '.$binding.'="avatar" />')))
            ->toContain($binding.'="avatar"');
    })->with(['wire:model', 'wire:model.live']);
});

describe('invalid', function () {
    it('reads the error bag by name and says so where it matters', function () {
        seedErrors(['avatar' => ['The avatar failed to upload.']]);

        $html = Blade::render('<shape:file name="avatar" />');

        expect($html)->toContain('border-danger-border')
            ->and(picker($html))->toContain('aria-invalid="true"');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(Blade::render('<shape:file name="avatar" :invalid="true" />'))
            ->toContain('border-danger-border');
    });

    it('lets the call site clear a field the validator has', function () {
        seedErrors(['avatar' => ['Too large.']]);

        expect(Blade::render('<shape:file name="avatar" :invalid="false" />'))
            ->toContain('border-neutral-border')
            ->not->toContain('border-danger-border');
    });

    it('stays quiet when there is no bag to read', function () {
        expect(Blade::render('<shape:file name="avatar" />'))->toContain('border-neutral-border');
    });
});

describe('icon', function () {
    beforeEach(function () {
        File::deleteDirectory(TestCase::iconPath());

        $this->artisan('shape:icon:add', ['name' => ['paperclip'], '--no-clear' => true])->run();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('puts a leading mark before the control', function () {
        $html = Blade::render('<shape:file icon="paperclip" />');

        expect(strpos($html, '<svg'))->toBeLessThan(strpos($html, '<input'));
    });

    it('sizes the mark to the rung the field resolved', function () {
        expect(Blade::render('<shape:file size="xs" icon="paperclip" />'))->toContain('size-3.5')
            ->and(Blade::render('<shape:file size="lg" icon="paperclip" />'))->toContain('size-6');
    });

    it('keeps the mark out of the accessibility tree', function () {
        expect(Blade::render('<shape:file icon="paperclip" />'))->toContain('aria-hidden="true"');
    });

    it('ignores a bare icon attribute rather than looking for an icon named 1', function () {
        expect(Blade::render('<shape:file icon />'))->toBe(Blade::render('<shape:file />'));
    });

    it('has no trailing mark, because the filename owns that end of the box', function () {
        // A filename has no fixed length and every reason to be the thing that
        // wraps or truncates, so a mark pinned after it would be a mark that moves.
        expect(Blade::render('<shape:file icon-trailing="paperclip" />'))->not->toContain('<svg');
    });
});

describe('shorthand', function () {
    beforeEach(function () {
        publishRequiredIcons();
    });

    afterEach(function () {
        File::deleteDirectory(TestCase::iconPath());
    });

    it('expands a label into a field, a control and a message', function () {
        seedErrors(['avatar' => ['The avatar failed to upload.']]);

        $html = Blade::render('<shape:file label="Avatar" name="avatar" />');

        expect($html)
            ->toContain('<label')
            ->toContain('Avatar')
            ->toContain('type="file"')
            ->toContain('The avatar failed to upload.');
    });

    it('points the label at the control it labels', function () {
        $html = Blade::render('<shape:file label="Avatar" name="profile.avatar" />');

        expect($html)
            ->toContain('for="profile-avatar"')
            ->and(picker($html))->toContain('id="profile-avatar"');
    });

    it('describes the control with the ids it actually rendered', function () {
        $html = Blade::render('<shape:file label="Avatar" description="PNG or JPG." name="avatar" />');

        expect(picker($html))->toContain('aria-describedby="avatar-description"');
    });

    it('renders no chrome at all when no chrome prop was named', function () {
        expect(Blade::render('<shape:file name="avatar" />'))
            ->not->toContain('<label')
            ->not->toContain('flex flex-col');
    });
});
