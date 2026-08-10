<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

use Illuminate\Contracts\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Illuminate\View\ComponentAttributeBag;

/**
 * One form control's identity, resolved once for the view that renders it.
 *
 * Every control in the family has the same questions to answer and no way to
 * answer them alone: what is this field called, what id does its label point at,
 * and which of the sentences around it describe it. `Fields` next door is the
 * string half of that -- a name to an id -- which the label, the description and
 * the message call without ever having a control to resolve. This is the other
 * half.
 *
 * A value object rather than a bag of static methods, because the answers are a
 * chain: the id depends on the scope, the scope on the slug, the slug on the
 * name, and `aria-describedby` on all of them. Spelled as statics, a view would
 * have to thread five intermediates through five calls and hand each one the
 * attribute bag again -- which is the `@php` block this class exists to delete,
 * written out with more punctuation.
 *
 * Everything here is answerable when the view is *compiled*, which is the line
 * this class is drawn along and the reason the controls can fold. The one
 * question that is not -- what the validator made of the field -- is answered by
 * `state()` at the bottom, which the controls call from inside an island on every
 * render. See docs/performance.md.
 *
 * @internal
 */
final class Control
{
    /**
     * @param  string|null  $field  The resolved field name, or null where nothing named it.
     * @param  string|null  $slug  The id stem the *field* answers to -- the group, for a radio.
     * @param  string|null  $scope  The id stem this *control* answers to -- the option, for a radio.
     * @param  string|null  $id  What the control renders as its id, and a label points `for` at.
     * @param  bool  $inherited  Whether the name came from an enclosing field rather than from here.
     * @param  bool  $anonymous  Whether nothing local named the field, so an inherited name may.
     * @param  mixed  $invalid  A call site's override, left unresolved for `state()` to weigh.
     * @param  string|null  $described  The ids that describe this control, before the message is weighed.
     * @param  bool  $message  Whether the caller drew a message that may describe this control.
     */
    private function __construct(
        public readonly ?string $field,
        public readonly ?string $slug,
        public readonly ?string $scope,
        public readonly ?string $id,
        public readonly bool $inherited,
        private readonly bool $anonymous,
        private readonly mixed $invalid,
        private readonly ?string $described,
        private readonly bool $message,
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
     * @param  mixed  $invalid  A call site's override, carried through to `state()`.
     * @param  mixed  $option  A discriminator -- a checkbox's or radio's `value` -- for controls sharing a name.
     * @param  string|null  $label  The label the caller is drawing, which is what forces an id.
     * @param  bool  $description  Whether the caller drew leading help text.
     * @param  bool  $descriptionTrailing  Whether the caller drew trailing help text.
     * @param  bool  $message  Whether the caller drew a message that may describe this control.
     */
    public static function resolve(
        ComponentAttributeBag $attributes,
        ?string $name = null,
        mixed $invalid = null,
        mixed $option = null,
        ?string $label = null,
        bool $description = false,
        bool $descriptionTrailing = false,
        bool $message = false,
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

        // A labelled control with no name has nothing to derive an id from, and a
        // <label> pointing at nothing is worse than no label at all. So the label's
        // own words stand in, which is a change of source rather than of purpose:
        // this used to take the next number off a process-wide counter.
        //
        // The counter had to go for the controls to fold at all -- a folded
        // component is evaluated once and its markup repeated, so every row of a
        // loop would have carried whichever number came up first, and *which*
        // number depended on what else the application happened to compile before
        // it. Deriving it puts the answer back in the markup: the same tag always
        // renders the same id.
        //
        // What that costs is stated plainly in docs/performance.md. Two controls
        // labelled the same way, with no name and no binding between them, now
        // answer to one id -- exactly as two controls *named* the same way always
        // have. The prefix keeps them out of the way of a real field name, and
        // lower case keeps `label="Email"` from colliding with `name="Email"`
        // through a difference nothing else in this file is sensitive to.
        //
        // Only where a label is drawn. Help text alone used to spend a number as
        // well, and nothing ever pointed at it: `aria-describedby` is built from
        // the scope below rather than from this id, so an unnamed control with a
        // description got an id no element referred to.
        if ($id === null && is_string($label) && $label !== '') {
            $id = ($stem = Fields::id($label)) !== '' ? 'shape-field-'.strtolower($stem) : null;
        }

        // Only ids that will exist, and only where the caller knows they will. A
        // reference to an element that was never rendered is a finding rather
        // than a courtesy.
        //
        // The description ids come off the scope, so a radio group's three help
        // texts do not all claim `plan-description`. The message id is not here at
        // all: whether there is a message to point at is the request's business,
        // so `state()` adds that token on render.
        $described = [];

        if ($scope !== null && $description) {
            $described[] = $scope.'-description';
        }

        if ($scope !== null && $descriptionTrailing) {
            $described[] = $scope.'-description-trailing';
        }

        // A call site that wrote its own takes the place of the list rather than
        // adding to it, which is what `ComponentAttributeBag::merge()` did for
        // this attribute before the value moved out of the bag. It is also how a
        // shorthand hands its bare control the ids it drew: `forward()` writes the
        // attribute, and the bare render reads it back here.
        //
        // The message id travels the same way, and that is the whole reason it is
        // picked back out rather than left in the list. A bare control does not name
        // the message -- it has no way to know one was drawn, and an
        // `aria-describedby` pointing at an element nobody rendered is a finding
        // rather than a courtesy -- so the shorthand saying "there is a message for
        // this field" is what the token means. Whether it survives to the markup is
        // the request's business, and `state()` settles that.
        //
        // A call site that writes the id by hand is read as saying the same thing,
        // which is the right answer for it too: the element it names exists exactly
        // when the bag has something to say.
        $ownDescribed = $attributes->get('aria-describedby');

        if (is_string($ownDescribed) && $ownDescribed !== '') {
            $tokens = preg_split('/\s+/', trim($ownDescribed)) ?: [];

            if ($slug !== null && in_array($slug.'-error', $tokens, true)) {
                $message = true;

                $tokens = array_values(array_diff($tokens, [$slug.'-error']));
            }

            $described = $tokens;
        }

        return new self(
            field: $field,
            slug: $slug,
            scope: $scope,
            id: $id,
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
            invalid: $invalid,
            described: $described !== [] ? implode(' ', $described) : null,
            message: $message,
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
     * The message id goes too, where the shorthand drew one -- not as a promise
     * that it will be rendered, but as the only way to tell the control inside
     * that a message exists for its field at all. `state()` weighs it per render
     * and drops it on a field the validator is happy with. Nothing else about the
     * error state is forwarded: `aria-invalid` is the request's to settle, and the
     * bare control settles it for itself.
     *
     * @return array<string, string>
     */
    public function forward(): array
    {
        $described = $this->message && $this->slug !== null
            ? trim(($this->described ?? '').' '.$this->slug.'-error')
            : $this->described;

        return array_merge(
            $this->identity(),
            $described !== null && $described !== '' ? ['aria-describedby' => $described] : [],
        );
    }

    /**
     * What the rendered element carries, of the part that is settled.
     *
     * Key order is rendered order -- `ComponentAttributeBag::merge()` ends in
     * `array_merge($defaults, $attributes)` -- so these are listed the way they
     * should read in the markup.
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
        );
    }

    /**
     * What an island needs to finish the job on render.
     *
     * Everything in here is settled at compile time, which is the requirement
     * rather than a convenience: an island's scope is written into the compiled
     * view with `var_export`, so it holds arrays and scalars and is evaluated
     * once. The bag it is weighed against arrives separately, per render.
     *
     * @return array<string, mixed>
     */
    public function live(): array
    {
        return [
            'field' => $this->field,
            'slug' => $this->slug,
            'invalid' => $this->invalid,
            'described' => $this->described,
            'message' => $this->message,
        ];
    }

    /**
     * The attributes only the request can settle, rendered.
     *
     * Called from inside every control's island, with that control's `live()`
     * array baked into the compiled view beside it. It returns a string rather
     * than an array because there is no bag left to merge into by the time it
     * runs -- the element's other attributes were written when the view compiled.
     *
     * @param  array<string, mixed>  $scope  The control's `live()` array.
     * @param  ViewErrorBag|MessageBag|null  $errors  The shared bag, or null where no middleware put one there.
     */
    public static function state(array $scope, ViewErrorBag|MessageBag|null $errors = null): string
    {
        $field = $scope['field'] ?? null;
        $slug = $scope['slug'] ?? null;
        $invalid = $scope['invalid'] ?? null;
        $described = $scope['described'] ?? null;
        $message = (bool) ($scope['message'] ?? false);

        // Null rather than an invented empty bag: `errors` is shared onto views by
        // ShareErrorsFromSession and a package cannot assume the middleware ran --
        // a Blade::render() outside the web group has no session, and neither does
        // a mail template. A bag guessed at here would report every field valid in
        // the one case where it cannot tell.
        $reported = is_string($field) && $field !== '' && $errors !== null && $errors->has($field);

        // The bag decides, unless the call site says otherwise. A named `invalid`
        // wins in both directions: `true` marks a field the validator has not seen
        // yet, and `false` clears one it has.
        //
        // Read through filter_var so an `invalid="false"` from a template that
        // stringified a variable does not read as broken.
        $bad = $invalid !== null
            ? (bool) filter_var($invalid, FILTER_VALIDATE_BOOLEAN)
            : $reported;

        // `$reported` rather than `$bad`, which is the one place the two part
        // company. `:invalid="true"` styles a control the validator has not seen,
        // and there is no sentence for it to be described by -- the message
        // component renders nothing without something in the bag. Naming the id
        // anyway would point `aria-describedby` at an element that is not on the
        // page, which is the audit finding this list is built to avoid.
        //
        // `$message` is the other half of the same rule: the id is only named where
        // somebody drew the element it names. A control standing on its own does
        // not, however much the bag has to say about its field.
        if ($reported && $message && is_string($slug) && $slug !== '') {
            $described = $described !== null ? $described.' '.$slug.'-error' : $slug.'-error';
        }

        $out = '';

        // Order is rendered order, and it matches what `attributes()` used to hand
        // `merge()` so the two halves of an element still read the same way round.
        if ($bad) {
            $out .= ' aria-invalid="true"';
        }

        if (is_string($described) && $described !== '') {
            $out .= ' aria-describedby="'.e($described).'"';
        }

        return $out;
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
