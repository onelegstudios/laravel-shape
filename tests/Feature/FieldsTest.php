<?php

declare(strict_types=1);

use Onelegstudios\Shape\Fields;

it('leaves a name that is already an id alone', function () {
    expect(Fields::id('email'))->toBe('email');
});

it('collapses the punctuation a form name is allowed to carry', function (string $name, string $id) {
    // Livewire and Laravel both spell nested state with dots and brackets. HTML5
    // permits those in an id, but a querySelector round trip through one needs
    // escaping that nothing here would remember to do.
    expect(Fields::id($name))->toBe($id);
})->with([
    'a nested attribute' => ['user.email', 'user-email'],
    'an array index' => ['items[0]', 'items-0'],
    'both at once' => ['items[0].qty', 'items-0-qty'],
    'a run of punctuation counts once' => ['user..email', 'user-email'],
    'underscores are punctuation too' => ['user_email', 'user-email'],
]);

it('does not leave an id starting or ending in a hyphen', function () {
    expect(Fields::id('[email]'))->toBe('email');
});

it('leaves case alone', function () {
    // Ids are case-sensitive and both halves of a label/control pair derive
    // through this, so lowercasing would cost a name its shape for no one.
    expect(Fields::id('userEmail'))->toBe('userEmail');
});

it('returns nothing for a name with nothing in it to keep', function () {
    // The call sites read this as "no id", which is the honest answer: a name of
    // pure punctuation cannot address anything.
    expect(Fields::id('...'))->toBe('');
});
