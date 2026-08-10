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

/**
 * The half of a control's identity that only the request can settle, rendered.
 *
 * `resolve()` answers everything a compiler can, which is what lets the controls
 * fold; this is what each of them calls from inside an island, on every render,
 * with the array `live()` baked into the compiled view beside it. Spelled as one
 * helper here because the pair is how the class is actually used.
 */
function state(Control $control, ViewErrorBag|MessageBag|null $errors = null): string
{
    return Control::state($control->live(), $errors);
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

    it('derives one from the label for a control that nothing named', function () {
        // A <label> pointing at nothing is worse than no label at all, so the
        // label's own words stand in. This used to take the next number off a
        // process-wide counter, which had to go for the controls to fold: a folded
        // component is evaluated once and its markup repeated, so every row of a
        // loop would have carried whichever number happened to come up first.
        expect(Control::resolve(bag(), label: 'Email address')->id)
            ->toBe('shape-field-email-address');
    });

    it('gives two controls with the same label the same id', function () {
        // The cost of deriving rather than counting, pinned rather than hidden. It
        // is the behaviour two controls *named* the same way have always had, and
        // the case it bites -- a labelled control with no name, no binding and no id
        // of its own, repeated -- is a control that submits nothing anyway.
        expect(Control::resolve(bag(), label: 'Email')->id)
            ->toBe(Control::resolve(bag(), label: 'Email')->id);
    });

    it('prefixes a derived id so it cannot collide with a real field name', function () {
        expect(Control::resolve(bag(), label: 'Email')->id)->toBe('shape-field-email')
            ->and(Control::resolve(bag(['name' => 'Email']))->id)->toBe('Email');
    });

    it('derives none from a label that slugs away to nothing', function () {
        // No stem, no id, and no `for` on the label either -- which is honest where
        // inventing `shape-field-` with nothing after it would not be.
        expect(Control::resolve(bag(), label: '---')->id)->toBeNull();
    });

    it('generates none for a control with only help text', function () {
        // The counter used to spend a number here, and nothing ever pointed at it:
        // `aria-describedby` is built from the scope rather than from this id, so an
        // unnamed control with a description got an id no element referred to.
        expect(Control::resolve(bag(), description: true)->id)->toBeNull();
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
        // the box beside it.
        expect(state(Control::resolve(
            bag(['name' => 'plan']),
            option: 'pro',
            description: true,
            message: true,
        ), errorBag(['plan' => ['Pick a plan.']])))
            ->toContain('aria-describedby="plan-pro-description plan-error"');
    });
});

describe('the error state', function () {
    // Everything above this line is answerable when a view is compiled, which is
    // what lets the controls fold. This is the part that is not, so it lives in
    // `state()` and is asked again on every render. The two are tested through the
    // same pair a control uses: `resolve()` settles the question, `live()` carries
    // it into the compiled view, `state()` weighs it against the bag in front of it.

    it('reads the bag by name', function () {
        expect(state(
            Control::resolve(bag(['name' => 'email'])),
            errorBag(['email' => ['The email field is required.']]),
        ))->toContain('aria-invalid="true"');
    });

    it('resolves a nested name the way the validator spells it', function () {
        expect(state(
            Control::resolve(bag(['name' => 'user.email'])),
            errorBag(['user.email' => ['Required.']]),
        ))->toContain('aria-invalid="true"');
    });

    it('stays quiet when there is no bag to read', function () {
        // No session middleware ran, so nothing was shared. A control that
        // invented an empty bag would report every field as valid.
        expect(state(Control::resolve(bag(['name' => 'email']))))->toBe('');
    });

    it('reads a plain message bag as well as a view error bag', function () {
        // Which is what a consumer sharing their own bag gets, and what the
        // union in the signature is for.
        expect(state(
            Control::resolve(bag(['name' => 'email'])),
            new MessageBag(['email' => ['Required.']]),
        ))->toContain('aria-invalid="true"');
    });

    it('lets the call site mark a field the validator has not seen', function () {
        expect(state(Control::resolve(bag(['name' => 'email']), invalid: true)))
            ->toContain('aria-invalid="true"');
    });

    it('lets the call site clear a field the validator has', function () {
        expect(state(
            Control::resolve(bag(['name' => 'email']), invalid: false),
            errorBag(['email' => ['Required.']]),
        ))->not->toContain('aria-invalid');
    });

    it('reads an override a template stringified', function (mixed $given, bool $expected) {
        expect(str_contains(state(Control::resolve(bag(['name' => 'email']), invalid: $given)), 'aria-invalid'))
            ->toBe($expected);
    })->with([
        'the string true' => ['true', true],
        'the string false' => ['false', false],
        'one' => ['1', true],
        'zero' => ['0', false],
    ]);

    it('carries the override through the fold rather than resolving it early', function () {
        // The override is settled at the call site and the bag is not, so `live()`
        // hands the one over unresolved and lets `state()` weigh both together.
        // Baking `aria-invalid` at compile time is exactly what this arrangement
        // exists to avoid.
        expect(Control::resolve(bag(['name' => 'email']), invalid: true)->attributes())
            ->toBe(['id' => 'email'])
            ->and(Control::resolve(bag(['name' => 'email']), invalid: true)->live())
            ->toMatchArray(['field' => 'email', 'slug' => 'email', 'invalid' => true]);
    });
});

describe('what describes the control', function () {
    it('names nothing when there is nothing around it', function () {
        expect(state(Control::resolve(bag(['name' => 'email']))))->toBe('');
    });

    it('names the sentences the caller says it drew', function () {
        expect(state(Control::resolve(
            bag(['name' => 'email']),
            description: true,
            descriptionTrailing: true,
            message: true,
        ), errorBag(['email' => ['Required.']])))
            ->toContain('aria-describedby="email-description email-description-trailing email-error"');
    });

    it('names no id it was not told about', function () {
        expect(state(Control::resolve(bag(['name' => 'email']), description: true)))
            ->toContain('aria-describedby="email-description"');
    });

    it('scopes the help text ids by the option', function () {
        // Three boxes in one group must not all claim `tags-description`.
        expect(state(Control::resolve(
            bag(['name' => 'tags']),
            option: 'php',
            description: true,
        )))->toContain('aria-describedby="tags-php-description"');
    });

    it('keeps the message id on the field even for a scoped control', function () {
        // The options are three; the sentence is one.
        expect(state(
            Control::resolve(bag(['name' => 'plan']), option: 'pro', message: true),
            errorBag(['plan' => ['Pick a plan.']]),
        ))->toContain('aria-describedby="plan-error"');
    });

    it('does not name a message that will not be rendered', function () {
        // The one place `invalid` and the bag part company. `:invalid="true"`
        // styles a control the validator has not seen, and the message component
        // renders nothing without something in the bag -- so naming the id would
        // point at an element that is not on the page.
        expect(state(Control::resolve(bag(['name' => 'email']), invalid: true, message: true)))
            ->toBe(' aria-invalid="true"');
    });

    it('names the message even where the call site cleared the styling', function () {
        // `:invalid="false"` is a statement about how the control looks. The
        // sentence is still there and still describes it.
        expect(state(
            Control::resolve(bag(['name' => 'email']), invalid: false, message: true),
            errorBag(['email' => ['Required.']]),
        ))->toBe(' aria-describedby="email-error"');
    });

    it('names no message where nobody drew one', function () {
        // A bare control cannot know whether a message exists for its field, and an
        // `aria-describedby` pointing at an element nobody rendered is a finding
        // rather than a courtesy. So the shorthand says it drew one and a control
        // standing on its own says nothing, however much the bag has to say.
        expect(state(
            Control::resolve(bag(['name' => 'email'])),
            errorBag(['email' => ['Required.']]),
        ))->toBe(' aria-invalid="true"');
    });

    it('reads the message id back out of what a shorthand forwarded', function () {
        // The one channel a component has to its own recursion. `forward()` names
        // the message id, the bare render picks it back out here, and `state()` puts
        // it in the markup only where the bag has something to say.
        $forwarded = Control::resolve(bag(['name' => 'email']), description: true, message: true)->forward();

        expect($forwarded['aria-describedby'])->toBe('email-description email-error');

        $bare = Control::resolve(bag(array_merge(['name' => 'email'], $forwarded)));

        expect(state($bare, errorBag(['email' => ['Required.']])))
            ->toContain('aria-describedby="email-description email-error"')
            ->and(state($bare))->toBe(' aria-describedby="email-description"');
    });

    it('lets a call site of its own take the place of the list', function () {
        expect(state(Control::resolve(bag(['name' => 'email', 'aria-describedby' => 'mine']), description: true)))
            ->toBe(' aria-describedby="mine"');
    });
});
