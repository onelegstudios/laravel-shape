<?php

declare(strict_types=1);

namespace Onelegstudios\Shape;

/**
 * Derivations the field components share.
 *
 * There is one, and it exists because two components have to reach the same
 * answer without being able to talk to each other: a label's `for` and its
 * input's `id` are set in separate files, and a pair that disagrees is a label
 * that clicks through to nothing.
 *
 * @internal
 */
final class Fields
{
    /**
     * The DOM id a field name resolves to.
     *
     * Form names are not ids. Livewire and Laravel both spell nested state with
     * dots and brackets -- `user.email`, `items[0].qty` -- and while HTML5 permits
     * those in an id, a CSS or querySelector round trip through one needs
     * escaping that nothing here would remember to do. Every run of characters
     * outside the alphanumerics collapses to a single hyphen instead.
     *
     * Case is left alone. Ids are case-sensitive and both sides of the pair
     * derive through this same function, so lowercasing would only cost a
     * `userEmail` its shape for no one's benefit.
     */
    public static function id(string $name): string
    {
        return trim((string) preg_replace('/[^A-Za-z0-9]+/', '-', $name), '-');
    }
}
