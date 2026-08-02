<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Illuminate\Support\Str;

/**
 * The format of a published icon, for the verbs that write one.
 *
 * Shared because `shape:icon:update` compares against it, not because copying
 * two heredocs would have been tedious. Update decides whether to touch a file
 * by rendering what the file should say and checking it against what the file
 * does say -- so a single character of difference between the two verbs would
 * make every icon report as changed on every run, rewrite the whole published
 * directory, and clear compiled views each time. A format two things agree on
 * byte for byte is a format with one definition.
 *
 * Kept out of InteractsWithPublishedIcons so that `shape:icon:remove` never
 * gains the ability to write an icon. A delete verb that can also write is one
 * refactor away from doing both.
 */
trait WritesIconComponents
{
    /**
     * Wrap raw SVG markup in a component that takes the caller's attributes.
     */
    private function component(string $contents, string $icon, string $set): string
    {
        // `shrink-0` and nothing else. An icon is a fixed glyph beside text that
        // wraps, and flex will happily squash it into an ellipse to make room --
        // but accessibility is left to the caller, because `merge` can only add
        // an attribute. An icon that hid itself here could never be unhidden by a
        // <shape:icon label="..."> above it.
        $merge = "{{ \$attributes->merge(['class' => 'shrink-0']) }}";

        $svg = Str::replaceFirst('<svg', '<svg '.$merge, $contents);

        // The header names no command. It used to name `shape:icon:add`, which
        // stopped being true the moment a second verb could write the file --
        // and a header that differs by which verb last wrote it is a header that
        // makes every icon look changed to the verb that compares against it.
        return <<<BLADE
            @blaze(fold: true)

            {{-- {$icon} -- published from set "{$set}" by Shape's icon commands.
                 Adding again leaves this file alone; `shape:icon:update` rewrites it. --}}

            {$svg}

            BLADE;
    }

    /**
     * Build the default-set component that forwards to the real one.
     */
    private function forward(string $set, string $name): string
    {
        return <<<BLADE
            {{-- Forwards to the configured default set. Written by Shape's icon commands. --}}

            <x-shape-icon::{$set}.{$name} {{ \$attributes }} />

            BLADE;
    }
}
