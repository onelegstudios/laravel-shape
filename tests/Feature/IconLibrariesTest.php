<?php

declare(strict_types=1);

use BladeUI\Icons\Factory;
use Onelegstudios\Shape\Icons\Libraries;

// The registry is the one place in the package that claims to know what another
// library calls something, which is exactly the kind of claim that rots. These
// resolve the names against the installed packages rather than against the
// table, so a set that renames an icon fails here instead of at somebody's
// install.

it('knows which package installs each set', function () {
    expect(Libraries::package('lucide'))->toBe('mallardduck/blade-lucide-icons')
        ->and(Libraries::package('outline'))->toBe('blade-ui-kit/blade-heroicons')
        ->and(Libraries::package('micro'))->toBe('blade-ui-kit/blade-heroicons');
});

it('maps every set it knows to a prefix', function () {
    expect(Libraries::sets())->toBe([
        'lucide' => 'lucide',
        'outline' => 'heroicon-o',
        'solid' => 'heroicon-s',
        'mini' => 'heroicon-m',
        'micro' => 'heroicon-c',
    ]);
});

it('gives every weight of a library the same names', function () {
    // The aliases sit on the library rather than the set because Heroicons draws
    // one icon list four ways. A table that repeated itself four times would be
    // three chances to let the copies drift.
    expect(Libraries::aliases('outline'))
        ->toBe(Libraries::aliases('solid'))
        ->toBe(Libraries::aliases('mini'))
        ->toBe(Libraries::aliases('micro'));
});

it('answers with nothing for a set it does not know', function () {
    expect(Libraries::prefix('fixture'))->toBeNull()
        ->and(Libraries::package('fixture'))->toBeNull()
        ->and(Libraries::library('fixture'))->toBeNull()
        ->and(Libraries::aliases('fixture'))->toBe([]);
});

it('names an icon each installed set actually has', function (string $set) {
    $icons = app(Factory::class);

    foreach (Libraries::aliases($set) as $resolved) {
        // svg() throws SvgNotFound for a name the set does not have, which is
        // the assertion that matters; the contents check is what keeps the loop
        // from passing on an empty table.
        expect($icons->svg(Libraries::prefix($set).'-'.$resolved)->contents())->toContain('<svg');
    }

    expect(Libraries::aliases($set))->not->toBeEmpty();
})->with(['lucide', 'outline', 'solid', 'mini', 'micro']);

it('lists the names Shape asks for once, whichever library spells them', function () {
    // The keys, not what they resolve to: the published file is named for the
    // name that asked for it, so both libraries contribute the same one entry.
    expect(Libraries::required())->toBe([
        'checkbox-check',
        'checkbox-indeterminate',
        'error',
        'select-chevron',
        'spinner',
    ]);
});

it('spells every name it asks for in both libraries', function () {
    // The asymmetry nothing else catches. `required()` unions the keys, so a name
    // added to one library alone becomes globally protected by `shape:icon:remove`
    // while `shape:install --default=solid` never publishes it -- and the shipped
    // component throws for every Heroicons consumer. Compared as sets rather than
    // against a written-out list, so this keeps holding as the table grows.
    $names = array_map(
        static fn (array $library): array => array_keys($library['aliases']),
        Libraries::KNOWN,
    );

    foreach ($names as $library => $keys) {
        expect($keys)->toEqualCanonicalizing(Libraries::required(), "[{$library}] is missing a name");
    }
});

it('lists a name from every library it knows', function () {
    foreach (Libraries::KNOWN as $library) {
        expect(Libraries::required())->toContain(...array_keys($library['aliases']));
    }
});
