<?php

declare(strict_types=1);

use Symfony\Component\Console\Exception\InvalidArgumentException;

it('lists the icon commands', function () {
    $this->artisan('shape:icon')
        ->expectsOutputToContain('shape:icon:add')
        ->expectsOutputToContain('shape:icon:remove')
        ->expectsOutputToContain('shape:icon:update')
        ->assertSuccessful();
});

it('takes no icon name of its own', function () {
    // The bare name is an index, not a verb, because icon names and verb names
    // are one vocabulary: `check`, `plus` and `x` are all icons somewhere. A
    // name passed here is refused outright rather than read as an action.
    $this->artisan('shape:icon', ['name' => 'check'])->run();
})->throws(InvalidArgumentException::class, 'The "name" argument does not exist.');
