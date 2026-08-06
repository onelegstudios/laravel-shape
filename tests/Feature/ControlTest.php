<?php

declare(strict_types=1);

use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;
use Onelegstudios\Shape\Control;

/**
 * The resolution every control in the family shares, tested through the class
 * rather than through five renders of it. A component test can tell you the
 * markup came out right; this is where the rules that produced it are pinned --
 * which name won, which id was derived, and which of the sentences around a
 * control it claims to be described by.
 */

/**
 * @param  array<string, mixed>  $attributes
 */
function bag(array $attributes = []): ComponentAttributeBag
{
    return new ComponentAttributeBag($attributes);
}

/**
 * @param  array<string, array<int, string>>  $messages
 */
function errorBag(array $messages): ViewErrorBag
{
    $bag = new ViewErrorBag;

    return $bag->put('default', new MessageBag($messages));
}

describe('the field name', function () {
    it('takes the name written on the tag', function () {
        expect(Control::resolve(bag(['name' => 'email']))->field)->toBe('email');
    });

    it('takes the name a Livewire binding is bound to', function (string $binding) {
        // The modifiers ride on the attribute name rather than its value, so it
        // is the prefix that has to be matched -- and a Livewire form is the case
        // where there is no `name` attribute at all.
        expect(Control::resolve(bag([$binding => 'email']))->field)->toBe('email');
    })->with([
        'the plain binding' => ['wire:model'],
        'deferred until an event' => ['wire:model.blur'],
        'live, with modifiers stacked on the name' => ['wire:model.live.debounce.300ms'],
    ]);

    it('takes the name of the field around it', function () {
        expect(Control::resolve(bag(), name: 'email')->field)->toBe('email');
    });

    it('prefers what the tag says to what it is bound to', function () {
        // Local information beats inherited, and a binding is only as local as
        // the attribute beside it.
        expect(Control::resolve(bag(['name' => 'own', 'wire:model' => 'bound']))->field)->toBe('own');
    });

    it('prefers what it is bound to over the field around it', function () {
        expect(Control::resolve(bag(['wire:model' => 'bound']), name: 'inherited')->field)->toBe('bound');
    });

    it('has no name when nothing named it', function () {
        expect(Control::resolve(bag())->field)->toBeNull();
    });

    it('ignores an empty name rather than treating it as one', function (array $attributes) {
        expect(Control::resolve(bag($attributes), name: 'inherited')->field)->toBe('inherited');
    })->with([
        'an empty name attribute' => [['name' => '']],
        'a name that is not a string' => [['name' => true]],
        'an empty binding' => [['wire:model' => '']],
    ]);
});

describe('naming the element', function () {
    it('names a control the field around it named', function () {
        // Which is what makes `<shape:field name="email"><shape:input /></shape:field>`
        // a complete statement rather than a control that submits nothing.
        expect(Control::resolve(bag(), name: 'email')->attributes())
            ->toBe(['id' => 'email', 'name' => 'email']);
    });

    it('leaves a control that named itself alone', function () {
        // `merge` would not overwrite it anyway; not offering it is what keeps
        // the rule readable.
        expect(Control::resolve(bag(['name' => 'email']))->attributes())
            ->toBe(['id' => 'email']);
    });

    it('leaves a bound control alone', function () {
        // A Livewire form has no use for the attribute, and adding one would
        // submit a field the component is not reading.
        expect(Control::resolve(bag(['wire:model' => 'email']), name: 'email')->attributes())
            ->toBe(['id' => 'email']);
    });
});

describe('where the name came from', function () {
    // The question a checkbox asks to decide whether it owns its own validation
    // message, and the one whose obvious test is wrong: Blade's `@aware` reads a
    // component's own data before its ancestors', so `$name` is not null for a
    // control that wrote its own `name` attribute. A check against null would
    // suppress exactly the message that has nowhere else to go.

    it('is inherited when only the field around it named it', function () {
        expect(Control::resolve(bag(), name: 'tags')->inherited)->toBeTrue();
    });

    it('is not inherited when the control named itself', function () {
        // Even though `$name` would be set at the call site too, which is the trap.
        expect(Control::resolve(bag(['name' => 'terms']), name: 'terms')->inherited)->toBeFalse();
    });

    it('is not inherited when the control is bound', function () {
        expect(Control::resolve(bag(['wire:model' => 'terms']), name: 'terms')->inherited)->toBeFalse();
    });

    it('is not inherited when nothing named it at all', function () {
        expect(Control::resolve(bag())->inherited)->toBeFalse();
    });
});

describe('the id', function () {
    it('derives an id from the name', function () {
        expect(Control::resolve(bag(['name' => 'user.email']))->id)->toBe('user-email');
    });

    it('follows an explicit id rather than the one it would have derived', function () {
        expect(Control::resolve(bag(['name' => 'email', 'id' => 'signup-email']))->id)
            ->toBe('signup-email');
    });

    it('has no id when there is nothing to derive one from', function () {
        expect(Control::resolve(bag())->id)->toBeNull();
    });

    it('generates one for a labelled control that nothing named', function () {
        // A <label> pointing at nothing is worse than no label at all.
        expect(Control::resolve(bag(), chrome: true)->id)->not->toBeNull();
    });

    it('generates a different one each time', function () {
        // Two unnamed labelled controls on one page are two pairs, not one.
        $first = Control::resolve(bag(), chrome: true)->id;
        $second = Control::resolve(bag(), chrome: true)->id;

        expect($first)->not->toBe($second);
    });

    it('generates none for a bare control', function () {
        // Nothing is pointing at it, so an id would be markup nobody reads.
        expect(Control::resolve(bag())->id)->toBeNull();
    });
});

describe('a control that shares its name', function () {
    // Three radios called `plan` are one field and three options. The field is
    // what the validator has an opinion about; the option is what a label points
    // at, and `Fields::id()` alone cannot tell them apart.
    //
    // The discriminator is an argument rather than something read off the bag,
    // which is the whole reason a checkbox opts into it and an input does not: a
    // `value` on a text field is what is currently in it, and scoping an id by
    // that would turn `<shape:input name="email" value="a@b.c">` into
    // `id="email-a-b-c"` -- a new id every time somebody types.

    it('scopes its own id by the option it carries', function () {
        // Spelled the way a checkbox spells it: the attribute stays on the bag so
        // the element still renders it, and the same string is handed over as the
        // discriminator.
        $control = Control::resolve(bag(['name' => 'plan', 'value' => 'pro']), option: 'pro');

        expect($control->slug)->toBe('plan')
            ->and($control->scope)->toBe('plan-pro')
            ->and($control->id)->toBe('plan-pro');
    });

    it('gives every option in a group an id of its own', function () {
        $free = Control::resolve(bag(), name: 'plan', option: 'free');
        $pro = Control::resolve(bag(), name: 'plan', option: 'pro');

        expect($free->id)->toBe('plan-free')
            ->and($pro->id)->toBe('plan-pro');
    });

    it('slugs the option the way it slugs the name', function () {
        expect(Control::resolve(bag(['name' => 'plan']), option: 'pro plus')->id)
            ->toBe('plan-pro-plus');
    });

    it('leaves an input carrying a value unscoped', function () {
        // The case the argument exists to keep out: nothing here opted in, so the
        // id is the field's own however much the element is carrying.
        expect(Control::resolve(bag(['name' => 'email', 'value' => 'a@b.c']))->id)
            ->toBe('email');
    });

    it('takes a value that is not a string', function () {
        // `:value="$plan->id"` is the ordinary way to write one.
        expect(Control::resolve(bag(['name' => 'plan']), option: 7)->id)->toBe('plan-7');
    });

    it('stays unscoped where there is no value to scope it by', function (mixed $option) {
        $control = Control::resolve(bag(['name' => 'plan']), option: $option);

        expect($control->scope)->toBe('plan')
            ->and($control->id)->toBe('plan');
    })->with([
        'no value at all' => [null],
        'an empty value' => [''],
        'a value that is not scalar' => [['pro']],
    ]);

    it('keeps the message on the field rather than the option', function () {
        // A validator has one opinion per name however many controls carry it,
        // so the sentence answers to the group while the help text answers to
        // the box beside it. Read through `forward()`, which is where the pair
        // reaches the markup.
        expect(Control::resolve(
            bag(['name' => 'plan']),
            errors: errorBag(['plan' => ['Pick a plan.']]),
            option: 'pro',
            description: true,
        )->forward()['aria-describedby'])->toBe('plan-pro-description plan-error');
    });
});

describe('the error state', function () {
    it('reads the bag by name', function () {
        expect(Control::resolve(
            bag(['name' => 'email']),
            errors: errorBag(['email' => ['The email field is required.']]),
        )->invalid)->toBeTrue();
    });

    it('resolves a nested name the way the validator spells it', function () {
        expect(Control::resolve(
            bag(['name' => 'user.email']),
            errors: errorBag(['user.email' => ['Required.']]),
        )->invalid)->toBeTrue();
    });

    it('stays quiet when there is no bag to read', function () {
        // No session middleware ran, so nothing was shared. A control that
        // invented an empty bag would report every field as valid.
        expect(Control::resolve(bag(['name' => 'email']))->invalid)->toBeFalse();
    });

    it('reads a plain message bag as well as a view error bag', function () {
        // Which is what a consumer sharing their own bag gets, and what the
        // union in the signature is for.
        expect(Control::resolve(
            bag(['name' => 'email']),
            errors: new MessageBag(['email' => ['Required.']]),
        )->invalid)->toBeTrue();
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(Control::resolve(bag(['name' => 'email']), invalid: true)->invalid)->toBeTrue();
    });

    it('lets the call site clear a field the validator has', function () {
        expect(Control::resolve(
            bag(['name' => 'email']),
            invalid: false,
            errors: errorBag(['email' => ['Required.']]),
        )->invalid)->toBeFalse();
    });

    it('reads an override a template stringified', function (mixed $given, bool $expected) {
        expect(Control::resolve(bag(['name' => 'email']), invalid: $given)->invalid)->toBe($expected);
    })->with([
        'the string true' => ['true', true],
        'the string false' => ['false', false],
        'one' => ['1', true],
        'zero' => ['0', false],
    ]);

    it('says so on the element', function () {
        expect(Control::resolve(bag(['name' => 'email']), invalid: true)->attributes())
            ->toBe(['id' => 'email', 'aria-invalid' => 'true']);
    });
});

describe('what describes the control', function () {
    it('names nothing when there is nothing around it', function () {
        expect(Control::resolve(bag(['name' => 'email']))->forward())
            ->toBe(['id' => 'email']);
    });

    it('names the sentences the caller says it drew', function () {
        expect(Control::resolve(
            bag(['name' => 'email']),
            errors: errorBag(['email' => ['Required.']]),
            description: true,
            descriptionTrailing: true,
        )->forward())->toBe([
            'id' => 'email',
            'aria-describedby' => 'email-description email-description-trailing email-error',
        ]);
    });

    it('names no id it was not told about', function () {
        expect(Control::resolve(bag(['name' => 'email']), description: true)->forward())
            ->toBe(['id' => 'email', 'aria-describedby' => 'email-description']);
    });

    it('scopes the help text ids by the option', function () {
        // Three boxes in one group must not all claim `tags-description`.
        expect(Control::resolve(
            bag(['name' => 'tags']),
            option: 'php',
            description: true,
        )->forward())->toBe([
            'id' => 'tags-php',
            'aria-describedby' => 'tags-php-description',
        ]);
    });

    it('keeps the message id on the field even for a scoped control', function () {
        // The options are three; the sentence is one.
        expect(Control::resolve(
            bag(['name' => 'plan']),
            errors: errorBag(['plan' => ['Pick a plan.']]),
            option: 'pro',
        )->forward())->toBe([
            'id' => 'plan-pro',
            'aria-describedby' => 'plan-error',
        ]);
    });

    it('does not name a message that will not be rendered', function () {
        // The one place `invalid` and the bag part company. `:invalid="true"`
        // styles a control the validator has not seen, and the message component
        // renders nothing without something in the bag -- so naming the id would
        // point at an element that is not on the page.
        expect(Control::resolve(bag(['name' => 'email']), invalid: true)->forward())
            ->toBe(['id' => 'email']);
    });

    it('names the message even where the call site cleared the styling', function () {
        // `:invalid="false"` is a statement about how the control looks. The
        // sentence is still there and still describes it.
        expect(Control::resolve(
            bag(['name' => 'email']),
            invalid: false,
            errors: errorBag(['email' => ['Required.']]),
        )->forward())->toBe([
            'id' => 'email',
            'aria-describedby' => 'email-error',
        ]);
    });
});
