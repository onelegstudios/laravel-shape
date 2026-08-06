<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * One form control's identity, resolved once for the view that renders it.
 *
 * Every control in the family has the same four questions to answer and no way
 * to answer them alone: what is this field called, does the validator have
 * something to say about it, what id does its label point at, and which of the
 * sentences around it describe it. `Fields` next door is the string half of that
 * -- a name to an id -- which the label, the description and the message call
 * without ever having a control to resolve. This is the other half.
 *
 * A value object rather than a bag of static methods, because the answers are a
 * chain: the id depends on the scope, the scope on the slug, the slug on the
 * name, and `aria-describedby` on all of them plus the error state. Spelled as
 * statics, a view would have to thread five intermediates through five calls and
 * hand each one the attribute bag again -- which is the `@php` block this class
 * exists to delete, written out with more punctuation.
 *
 * @internal
 */
final class Control
{
    /**
     * The counter behind a generated id.
     *
     * A sequence rather than `uniqid()`, and not only because Pest's security
     * arch preset refuses that function: an id has to be unique within one
     * document, which a counter guarantees and a clock only makes likely.
     *
     * It resets per process, which is the right scope -- one request renders one
     * document -- and means nothing depends on the value itself. Nothing should:
     * a generated id exists because a `<label>` needs something to point at, and
     * the only promise it makes is that the pair agree.
     */
    private static int $sequence = 0;

    /**
     * @param  string|null  $field  The resolved field name, or null where nothing named it.
     * @param  string|null  $slug  The id stem the *field* answers to -- the group, for a radio.
     * @param  string|null  $scope  The id stem this *control* answers to -- the option, for a radio.
     * @param  string|null  $id  What the control renders as its id, and a label points `for` at.
     * @param  bool  $invalid  Whether this control should be styled and announced as wrong.
     * @param  bool  $inherited  Whether the name came from an enclosing field rather than from here.
     * @param  bool  $anonymous  Whether nothing local named the field, so an inherited name may.
     * @param  string|null  $described  The `aria-describedby` value, or null where there is nothing to name.
     */
    private function __construct(
        public readonly ?string $field,
        public readonly ?string $slug,
        public readonly ?string $scope,
        public readonly ?string $id,
        public readonly bool $invalid,
        public readonly bool $inherited,
        private readonly bool $anonymous,
        private readonly ?string $described,
    ) {}

    /**
     * Resolve a control from its attribute bag and the props around it.
     *
     * Named arguments at every call site, which is what keeps eight parameters
     * readable in a view: the flags say what they mean where they are passed
     * rather than in a positional order nobody can remember.
     *
     * @param  ComponentAttributeBag  $attributes  The control's own bag, read but not consumed.
     * @param  string|null  $name  The name an enclosing field pushed through `@aware`.
     * @param  mixed  $invalid  A call site's override, read through filter_var.
     * @param  ViewErrorBag|MessageBag|null  $errors  The shared bag, or null where no middleware put one there.
     * @param  mixed  $option  A discriminator -- a checkbox's or radio's `value` -- for controls sharing a name.
     * @param  bool  $chrome  Whether the caller is drawing a label, which is what forces an id.
     * @param  bool  $description  Whether the caller drew leading help text.
     * @param  bool  $descriptionTrailing  Whether the caller drew trailing help text.
     */
    public static function resolve(
        ComponentAttributeBag $attributes,
        ?string $name = null,
        mixed $invalid = null,
        ViewErrorBag|MessageBag|null $errors = null,
        mixed $option = null,
        bool $chrome = false,
        bool $description = false,
        bool $descriptionTrailing = false,
    ): self {
        // Three places a field name can come from, in the order a reader would
        // expect to find it: what this tag says, what it is bound to, and what
        // the field around it was called. Local information beats inherited -- a
        // control carrying its own binding is describing itself more precisely
        // than its wrapper can.
        $own = $attributes->get('name');
        $own = is_string($own) && $own !== '' ? $own : null;

        // Livewire's binding is the only one a Livewire form usually writes.
        // Modifiers ride on the attribute name rather than its value --
        // `wire:model.live.debounce.300ms` -- so it is the prefix that has to be
        // matched, not the whole key.
        $model = $attributes->whereStartsWith('wire:model')->first();
        $model = is_string($model) && $model !== '' ? $model : null;

        $inherited = is_string($name) && $name !== '' ? $name : null;

        $field = $own ?? $model ?? $inherited;

        // The error bag decides, unless the call site says otherwise. A named
        // `invalid` wins in both directions: `true` marks a field the validator
        // has not seen yet, and `false` clears one it has.
        //
        // Read through filter_var so an `invalid="false"` from a template that
        // stringified a variable does not read as broken.
        //
        // Null rather than an invented empty bag: `$errors` is shared onto views
        // by ShareErrorsFromSession and a package cannot assume the middleware
        // ran -- a Blade::render() outside the web group has no session, and
        // neither does a mail template. A bag guessed at here would report every
        // field valid in the one case where it cannot tell.
        $reported = $field !== null && $errors !== null && $errors->has($field);

        $bad = $invalid !== null
            ? (bool) filter_var($invalid, FILTER_VALIDATE_BOOLEAN)
            : $reported;

        // What the *field* answers to. The message hangs off this, because a
        // validator has one opinion per name however many controls carry it.
        $slug = $field !== null ? (Fields::id($field) ?: null) : null;

        // What *this control* answers to. The same string, until a discriminator
        // says several controls share the name: three radios called `plan` are
        // three ids -- `plan-free`, `plan-pro`, `plan-team` -- and three labels
        // that each click through to their own rather than all to the first.
        $suffix = is_scalar($option) && (string) $option !== ''
            ? Fields::id((string) $option)
            : '';

        $scope = $slug !== null && $suffix !== '' ? $slug.'-'.$suffix : $slug;

        // An explicit id wins, because a name that collides with something else
        // on the page is exactly what one is for.
        $given = $attributes->get('id');

        $id = is_string($given) && $given !== '' ? $given : $scope;

        // A labelled control with no name has nothing to derive an id from, and
        // a <label> pointing at nothing is worse than no label at all.
        if ($chrome && $id === null) {
            $id = 'shape-field-'.++self::$sequence;
        }

        // Only ids that will exist, and only where the caller knows they will. A
        // reference to an element that was never rendered is a finding rather
        // than a courtesy.
        //
        // The description ids come off the scope, so a radio group's three help
        // texts do not all claim `plan-description`; the message id comes off the
        // slug, because the group has one message.
        $described = [];

        if ($scope !== null && $description) {
            $described[] = $scope.'-description';
        }

        if ($scope !== null && $descriptionTrailing) {
            $described[] = $scope.'-description-trailing';
        }

        // `$reported` rather than `$bad`, which is the one place the two part
        // company. `:invalid="true"` styles a control the validator has not seen,
        // and there is no sentence for it to be described by -- the message
        // component renders nothing without something in the bag. Naming the id
        // anyway would point `aria-describedby` at an element that is not on the
        // page, which is the audit finding this list is built to avoid.
        if ($slug !== null && $reported) {
            $described[] = $slug.'-error';
        }

        return new self(
            field: $field,
            slug: $slug,
            scope: $scope,
            id: $id,
            invalid: $bad,
            // "Something outside named me." The only reliable way a control can
            // tell, and worth spelling out because the obvious test is wrong:
            // `$name` is not null for a control that wrote its own `name`
            // attribute, because Blade's `@aware` reads the component's own data
            // before walking up to its ancestors. So a standalone
            // `<shape:checkbox name="terms" />` sees `terms` in `$name` and looks
            // exactly like one inside `<shape:field name="terms">`.
            //
            // What tells them apart is where the name came from, which is the one
            // question `resolve()` has already answered: nothing local named this
            // control, and something did. A checkbox reads this to decide whether
            // it owns its own validation message or whether the field around it
            // does.
            inherited: $own === null && $model === null && $inherited !== null,
            // True when nothing local named the field, which is the same
            // condition as `$field === $inherited` where both are non-null.
            anonymous: $own === null && $model === null,
            described: $described !== [] ? implode(' ', $described) : null,
        );
    }

    /**
     * What a shorthand hands the bare call inside it.
     *
     * The id, so the label the shorthand drew has something to point at, and the
     * sentences it knows it drew. Not the name: the bag being merged already
     * carries whatever the call site wrote, and the bare render resolves it
     * again the same way this one did.
     *
     * @return array<string, string>
     */
    public function forward(): array
    {
        return array_merge(
            $this->identity(),
            $this->described !== null ? ['aria-describedby' => $this->described] : [],
        );
    }

    /**
     * What the rendered element carries.
     *
     * Key order is rendered order -- `ComponentAttributeBag::merge()` ends in
     * `array_merge($defaults, $attributes)` -- so these three are listed the way
     * they should read in the markup.
     *
     * @return array<string, string>
     */
    public function attributes(): array
    {
        return array_merge(
            $this->identity(),
            // A field that named itself names its control too, which is what
            // makes `<shape:field name="email"><shape:input /></shape:field>` a
            // complete statement rather than a control that submits nothing.
            // Only where nothing else is already doing that job: a bound control
            // has no use for the attribute, and one that wrote its own is not to
            // be argued with.
            $this->anonymous && $this->field !== null ? ['name' => $this->field] : [],
            $this->invalid ? ['aria-invalid' => 'true'] : [],
        );
    }

    /**
     * The id, where there is one to render.
     *
     * Set in both shapes a control comes in, because a label written by hand --
     * inside a composed field or in the call site's own markup -- needs
     * something to point at, and the name is the only thing both halves see.
     *
     * @return array<string, string>
     */
    private function identity(): array
    {
        return $this->id !== null ? ['id' => $this->id] : [];
    }
}
