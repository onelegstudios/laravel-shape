<?php

declare(strict_types=1);

namespace Onelegstudios\Shape\Console\Commands\Concerns;

use Illuminate\Support\Str;

/**
 * The format of a published icon, for the verbs that write one or compare
 * against one.
 *
 * Shared because `shape:icon:update` compares against it, not because copying
 * two heredocs would have been tedious. Update decides whether to touch a file
 * by rendering what the file should say and checking it against what the file
 * does say -- so a single character of difference between the two verbs would
 * make every icon report as changed on every run, rewrite the whole published
 * directory, and clear compiled views each time. A format two things agree on
 * byte for byte is a format with one definition.
 *
 * `shape:icon:check` renders against the same definition without writing
 * anything, and reads the stamp back through the helpers below. Nothing here
 * touches a Filesystem: this trait renders strings, and the verb that got it
 * decides what to do with one. Which is why `shape:icon:remove` still does not
 * use it -- a delete verb that can also produce icon content is one refactor
 * away from doing both.
 *
 * Kept out of InteractsWithPublishedIcons for that same reason.
 */
trait WritesIconComponents
{
    /**
     * What sits in the stamp field of a file whose stamp is not known yet.
     *
     * Deliberately not hexadecimal, so it can never be read back as a stamp:
     * the readers below only accept hex, and a body carrying this reads as
     * unstamped -- which is exactly what it is while it is being hashed.
     */
    private const string BLANK_STAMP = '................';

    /**
     * The stamp field, in three pieces: what precedes it, the stamp, what
     * follows.
     *
     * Split that way because one of the readers has to put the line back
     * together with a different middle, and the pieces it puts back have to be
     * the bytes that were there. Trailing carriage returns are matched rather
     * than assumed away: a checkout on Windows can hand these commands a source
     * file with CRLF endings, and a stamp that only ever reads on Linux would be
     * worse than no stamp at all.
     */
    private const string STAMP_PATTERN = '/^([ \t]*stamp:)([0-9a-f]{16})([ \t]+--\}\}[ \t\r]*)$/m';

    /**
     * The component a call site addresses, which holds no artwork of its own.
     *
     * Two files rather than one, and the split is not tidiness. `<shape:icon>`
     * reaches its artwork with `@include`, because dispatching through
     * <x-dynamic-component> costs a component resolution on every render that
     * cannot fold -- which is every icon whose name is only known at render, and
     * every mark a component sizes from a variable. An include cannot end at a
     * file carrying `@blaze`, though: Blaze compiles one into a function
     * definition, so including it renders nothing at all.
     *
     * So the artwork is a plain view that anything may include, and this is the
     * component that folds. Writing `<x-shape-icon::lucide.check />` by hand still
     * resolves here and still folds away to the SVG, because folding evaluates the
     * include at compile time.
     *
     * The view name is passed in rather than built here: where a published icon
     * lives is a fact about the published directory, and that lives next door in
     * InteractsWithPublishedIcons with the rest of them. Nothing in this trait
     * touches a path.
     */
    private function component(string $view, string $set, string $name): string
    {
        return <<<BLADE
            @blaze(fold: true)

            {{-- The component form of "{$set}/{$name}". The artwork is beside it in
                 art/, which is what `<shape:icon>` includes directly. --}}

            @include('{$view}')

            BLADE;
    }

    /**
     * Wrap raw SVG markup in the artwork view the component includes.
     */
    private function art(string $contents, string $icon, string $set): string
    {
        // `shrink-0` and nothing else. An icon is a fixed glyph beside text that
        // wraps, and flex will happily squash it into an ellipse to make room --
        // but accessibility is left to the caller, because `merge` can only add
        // an attribute. An icon that hid itself here could never be unhidden by a
        // <shape:icon label="..."> above it.
        $merge = "{{ \$attributes->merge(['class' => 'shrink-0']) }}";

        $svg = Str::replaceFirst('<svg', '<svg '.$merge, $contents);

        // Rendered twice because the stamp is a hash of the thing it sits in,
        // and no hash can include itself. The first render is what gets hashed;
        // the second is the same bytes with the answer written in.
        $blank = $this->body($svg, $icon, $set, self::BLANK_STAMP);

        return $this->body($svg, $icon, $set, $this->stamp($blank));
    }

    /**
     * The published file's bytes, with the stamp field given.
     *
     * The header names no command. It used to name `shape:icon:add`, which
     * stopped being true the moment a second verb could write the file -- and a
     * header that differs by which verb last wrote it is a header that makes
     * every icon look changed to the verb that compares against it.
     */
    private function body(string $svg, string $icon, string $set, string $stamp): string
    {
        // No `@blaze` here, and its absence is the whole reason this file exists
        // apart from the component. Blaze compiles a file carrying that directive
        // into a function definition, so an `@include` of one renders nothing --
        // silently, which is the worst way for it to be wrong. The component
        // beside this carries the directive and folds; this stays a plain view
        // that anything can include.
        return <<<BLADE
            {{-- {$icon} -- published from set "{$set}" by Shape's icon commands.
                 Adding again leaves this file alone; `shape:icon:update` rewrites it.
                 stamp:{$stamp} --}}

            {$svg}

            BLADE;
    }

    /**
     * Build the default-set component that forwards to the real one.
     *
     * No stamp on either forward: they hold no artwork, so there is nothing about
     * them a set upgrade can move. Whether one is right is decided by reading it,
     * and one line is short enough to read.
     */
    private function forward(string $view): string
    {
        return <<<BLADE
            @blaze(fold: true)

            {{-- Forwards to the configured default set. Written by Shape's icon commands. --}}

            @include('{$view}')

            BLADE;
    }

    /**
     * The artwork half of the default-set forward.
     *
     * `<shape:icon>` includes `{set}.art.{name}` without knowing which set is
     * configured -- `default` is a real directory precisely so it does not have to
     * -- so `default/art/` has to answer too, and it answers by forwarding to the
     * same file the component above does.
     */
    private function forwardArt(string $view): string
    {
        return <<<BLADE
            {{-- Forwards to the configured default set. Written by Shape's icon commands. --}}

            @include('{$view}')

            BLADE;
    }

    /**
     * Hash a body that has no stamp in it yet.
     *
     * Truncated because a full digest in a comment reads as machinery, and
     * sixteen hex characters is already far past the point where two icons in
     * one directory could collide by accident. None of this is a security
     * boundary -- anyone who can edit the stamp can edit the artwork above it --
     * so the digest is chosen for being unremarkable rather than for strength.
     */
    private function stamp(string $blank): string
    {
        return substr(hash('sha256', $blank), 0, 16);
    }

    /**
     * The stamp a published file records, or null if it carries none.
     *
     * Null is the honest answer for a file published before stamps existed, and
     * the reason `shape:icon:check` has a second way of comparing. It is not an
     * error and it is not drift.
     */
    private function stampIn(string $blade): ?string
    {
        return preg_match(self::STAMP_PATTERN, $blade, $matches) === 1
            ? $matches[2]
            : null;
    }

    /**
     * The stamp a published file's own bytes come to.
     *
     * Blanking the field first is what makes this comparable to the stamp the
     * file records: the recorded one was taken of exactly these bytes with
     * exactly this field blank. They agree if and only if nothing above has been
     * touched since it was written.
     */
    private function stampOf(string $blade): string
    {
        $blank = preg_replace(
            self::STAMP_PATTERN,
            '${1}'.self::BLANK_STAMP.'${3}',
            $blade,
            1,
        );

        return $this->stamp($blank ?? $blade);
    }

    /**
     * The artwork in a published file, ignoring everything above it.
     *
     * The fallback for a file with no stamp, where the header cannot be trusted
     * to match what these commands write today but the SVG below it still
     * answers "would updating change this?" exactly.
     */
    private function artwork(string $blade): ?string
    {
        $position = strpos($blade, '<svg');

        return $position === false ? null : rtrim(substr($blade, $position));
    }
}
