<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Icons;

/**
 * The icon libraries `shape:install` can install, and what each one calls the
 * icons Shape's own components render.
 *
 * Shape still ships no icons and still requires no set. What it does know is the
 * part a consumer cannot reasonably be expected to: that the loading spinner is
 * `loader-circle` in Lucide and `arrow-path` in Heroicons, and that the mark on a
 * validation message is `circle-alert` in one and `exclamation-circle` in the
 * other. Leaving that in
 * `config/shape.php` alone meant the installer could only ever offer the one
 * library the shipped file happened to name -- choosing another published
 * nothing until somebody knew which names to remap by hand.
 *
 * So this is the naming layer's factory setting. `icons.sets` and
 * `icons.aliases` are read first and win where they say anything, which keeps
 * config the place an application states what it wants; this fills the silence
 * for the libraries Shape knows, and a library it does not know still works
 * exactly as it did -- as a prefix used as it stands.
 *
 * A library contributes one or more *set names*. Lucide is single-weight and
 * contributes one. Heroicons keeps its weight in the filename, so one Composer
 * package contributes four set names pointing into the same Blade Icons set --
 * which is why the aliases sit on the library rather than on each set: all four
 * weights draw from one icon list.
 */
final class Libraries
{
    /**
     * The libraries the installer offers, in the order it offers them.
     *
     * @var array<string, array{label: string, package: string, sets: array<string, string>, aliases: array<string, string>}>
     */
    public const array KNOWN = [

        'lucide' => [
            'label' => 'Lucide',
            'package' => 'mallardduck/blade-lucide-icons',
            'sets' => [
                'lucide' => 'lucide',
            ],
            // `loader-circle` is a single arc, so a plain rotation reads as
            // movement at a glance -- rather than `loader`, whose evenly spaced
            // spokes look nearly still while they turn.
            //
            // `circle-alert` over `triangle-alert` for the error: the triangle is
            // the warning mark, and a field that failed validation is not being
            // warned about. Over `circle-x` too, which reads as "removed" next to
            // a close button using the same glyph.
            'aliases' => [
                'error' => 'circle-alert',
                'spinner' => 'loader-circle',
            ],
        ],

        'heroicons' => [
            'label' => 'Heroicons',
            'package' => 'blade-ui-kit/blade-heroicons',
            'sets' => [
                'outline' => 'heroicon-o',
                'solid' => 'heroicon-s',
                'mini' => 'heroicon-m',
                'micro' => 'heroicon-c',
            ],
            // Heroicons has no loader, so the spinner is `arrow-path`: the
            // refresh arrows, which is the icon its own examples spin.
            //
            // `exclamation-circle` is the same mark Lucide draws as
            // `circle-alert`, which is the point of a name in between: the two
            // libraries agree on the glyph and disagree only on what to call it.
            'aliases' => [
                'error' => 'exclamation-circle',
                'spinner' => 'arrow-path',
            ],
        ],

    ];

    /**
     * The Blade Icons name prefix a known set name maps to.
     *
     * Null rather than a fallback, so a caller can tell "Shape knows this name"
     * apart from "use it as a prefix as it stands" and keep the second bargain
     * where it already lives.
     */
    public static function prefix(string $set): ?string
    {
        foreach (self::KNOWN as $library) {
            if (isset($library['sets'][$set])) {
                return $library['sets'][$set];
            }
        }

        return null;
    }

    /**
     * The semantic names the library owning a set spells its own way.
     *
     * Empty for a set Shape does not know, which is the honest answer: an
     * application's own set gets its names from `icons.aliases`, the same as it
     * always did.
     *
     * @return array<string, string>
     */
    public static function aliases(string $set): array
    {
        foreach (self::KNOWN as $library) {
            if (isset($library['sets'][$set])) {
                return $library['aliases'];
            }
        }

        return [];
    }

    /**
     * The Composer package that installs the library a set comes from.
     */
    public static function package(string $set): ?string
    {
        foreach (self::KNOWN as $library) {
            if (isset($library['sets'][$set])) {
                return $library['package'];
            }
        }

        return null;
    }

    /**
     * Every set name Shape knows, mapped to its prefix.
     *
     * @return array<string, string>
     */
    public static function sets(): array
    {
        $sets = [];

        foreach (self::KNOWN as $library) {
            $sets = [...$sets, ...$library['sets']];
        }

        return $sets;
    }

    /**
     * The names Shape's own components render, whichever library is installed.
     *
     * The alias keys rather than what they resolve to, because a published icon
     * is named for the name that asked for it: the button's spinner is
     * `spinner.blade.php` on disk whether its artwork came from `loader-circle`
     * or `arrow-path`, and that file name is what a remove has to be checked
     * against.
     *
     * One flat list across every library rather than a list per set, because the
     * component asks without a `set` prop -- `<shape:icon name="spinner" />`
     * resolves through whichever set `default/` forwards at, which can move after
     * the icon was published. A name is either one Shape's views ask for or it is
     * not; which directory a copy of it sits in does not change the answer.
     *
     * Config's own aliases are deliberately not here. Those are an application's
     * vocabulary for its own call sites, and it can take them back out whenever
     * it likes -- what this protects is the promise the package made, that a
     * shipped component has something to render.
     *
     * @return array<int, string>
     */
    public static function required(): array
    {
        $names = [];

        foreach (self::KNOWN as $library) {
            $names = [...$names, ...array_keys($library['aliases'])];
        }

        $names = array_values(array_unique($names));

        sort($names);

        return $names;
    }

    /**
     * The library a known set name belongs to.
     */
    public static function library(string $set): ?string
    {
        foreach (self::KNOWN as $name => $library) {
            if (isset($library['sets'][$set])) {
                return $name;
            }
        }

        return null;
    }
}
