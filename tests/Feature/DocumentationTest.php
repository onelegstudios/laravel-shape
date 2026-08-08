<?php

declare(strict_types=1);

/**
 * Every documentation page, as a path relative to the package root: the README a
 * reader arrives at, and everything under docs/.
 *
 * Globbed rather than listed, so a page added without a link into it still has to
 * answer to the tests below.
 *
 * @return list<string>
 */
function documentationPages(): array
{
    $root = dirname(__DIR__, 2);

    $paths = [
        ...(array) glob($root.'/docs/*.md'),
        ...(array) glob($root.'/docs/*/*.md'),
    ];

    return [
        'README.md',
        ...array_map(
            static fn (string $path): string => ltrim(str_replace($root, '', $path), '/'),
            $paths,
        ),
    ];
}

/**
 * A page with its fenced code blocks taken out. A Blade example is full of things
 * that look like markup and none of them are links, and a shell block's `#` comment
 * is not a heading -- reading either would have the tests assert on prose the
 * renderer never treats as one.
 */
function documentationBody(string $page): string
{
    return (string) preg_replace('/^```.*?^```/ms', '', shapeFile($page));
}

/**
 * The link targets a page names, hrefs only.
 *
 * @return list<string>
 */
function documentationLinks(string $page): array
{
    preg_match_all('/\[[^\]]*\]\(([^)\s]+)\)/', documentationBody($page), $matches);

    return array_values(array_filter(
        $matches[1],
        static fn (string $link): bool => ! preg_match('#^(https?:|mailto:)#', $link),
    ));
}

/**
 * GitHub's heading slug: lowercased, punctuation dropped, spaces hyphenated. It is
 * what an in-page anchor has to match, so it is what the tests derive rather than
 * trusting the link.
 */
function documentationSlug(string $heading): string
{
    $slug = mb_strtolower(trim($heading));

    return str_replace(' ', '-', (string) preg_replace('/[^\p{L}\p{N} _-]+/u', '', $slug));
}

/**
 * Every anchor a page defines, one per heading.
 *
 * @return list<string>
 */
function documentationAnchors(string $page): array
{
    preg_match_all('/^#{1,6} +(.*)$/m', documentationBody($page), $matches);

    return array_map(documentationSlug(...), $matches[1]);
}

/**
 * A relative link resolved against the page that wrote it, or null where it leaves
 * the package -- README's `../../contributors` is GitHub's own shorthand rather than
 * a path on disk, and there is nothing here to hold it against.
 */
function documentationTarget(string $page, string $link): ?string
{
    $segments = [];

    foreach (explode('/', dirname($page).'/'.$link) as $segment) {
        if ($segment === '' || $segment === '.') {
            continue;
        }

        if ($segment === '..') {
            if ($segments === []) {
                return null;
            }

            array_pop($segments);

            continue;
        }

        $segments[] = $segment;
    }

    return implode('/', $segments);
}

/**
 * The pages the index lists, in the order it lists them.
 *
 * @return list<string>
 */
function documentationOrder(): array
{
    preg_match_all('/^\d+\. \[[^\]]+\]\(([^)]+)\)/m', documentationBody('docs/README.md'), $matches);

    return array_map(
        static fn (string $link): string => 'docs/'.$link,
        $matches[1],
    );
}

/**
 * The previous, index and next links a page closes with, keyed by name. The nav is
 * one line, so it is read as one pattern rather than three searches.
 *
 * @return array{previous: ?string, index: ?string, next: ?string}
 */
function documentationNav(string $page): array
{
    $lines = array_filter(array_map(trim(...), explode("\n", shapeFile($page))));

    $matched = preg_match(
        '/^(?:\[← [^\]]+\]\(([^)]+)\) · )?\[Index\]\(([^)]+)\)(?: · \[[^\]]+ →\]\(([^)]+)\))?$/u',
        (string) end($lines),
        $nav,
    );

    if ($matched !== 1) {
        return ['previous' => null, 'index' => null, 'next' => null];
    }

    return [
        'previous' => ($nav[1] ?? '') === '' ? null : $nav[1],
        'index' => $nav[2],
        'next' => ($nav[3] ?? '') === '' ? null : $nav[3],
    ];
}

it('links only to files that exist', function (string $page) {
    // Documentation split across files is held together by its links, and a link
    // that names the wrong path reads fine right up until someone clicks it.
    $broken = array_values(array_filter(
        documentationLinks($page),
        function (string $link) use ($page): bool {
            $path = explode('#', $link, 2)[0];

            if ($path === '') {
                return false;
            }

            $target = documentationTarget($page, $path);

            return $target !== null && ! file_exists(dirname(__DIR__, 2).'/'.$target);
        },
    ));

    expect($broken)->toBe([]);
})->with(documentationPages());

it('names only anchors its target defines', function (string $page) {
    // An anchor pointing across files is the half of a link a file-existence check
    // cannot see: the path resolves and the heading it names does not exist.
    $broken = [];

    foreach (documentationLinks($page) as $link) {
        [$path, $anchor] = array_pad(explode('#', $link, 2), 2, null);

        if ($anchor === null || $anchor === '') {
            continue;
        }

        $target = $path === '' ? $page : documentationTarget($page, $path);

        if ($target === null || ! str_ends_with($target, '.md')) {
            continue;
        }

        if (! in_array($anchor, documentationAnchors($target), true)) {
            $broken[] = $link;
        }
    }

    expect($broken)->toBe([]);
})->with(documentationPages());

it('ends every page with a link back to the index', function (string $page) {
    $nav = documentationNav($page);

    expect($nav['index'])->not->toBeNull()
        ->and(documentationTarget($page, $nav['index']))->toBe('docs/README.md');
})->with(array_values(array_diff(documentationPages(), ['README.md', 'docs/README.md'])));

it('chains the pages in the order the index lists them', function () {
    // The index is the source of the order and the nav is a copy of it, so the copy
    // is what rots: a page inserted in the middle leaves its two neighbours pointing
    // past it.
    $order = documentationOrder();

    expect($order)->not->toBeEmpty();

    foreach ($order as $position => $page) {
        $nav = documentationNav($page);

        $previous = $nav['previous'] === null ? null : documentationTarget($page, $nav['previous']);
        $next = $nav['next'] === null ? null : documentationTarget($page, $nav['next']);

        expect($previous)->toBe($order[$position - 1] ?? null)
            ->and($next)->toBe($order[$position + 1] ?? null);
    }
});

it('lists every page it ships in the index', function () {
    // A page nothing links to is a page nobody reads.
    $listed = [...documentationOrder(), 'docs/README.md'];

    $orphans = array_values(array_diff(documentationPages(), [...$listed, 'README.md']));

    expect($orphans)->toBe([]);
});

it('documents every component it ships', function () {
    // Named by tag rather than by filename, because a component that only helps
    // another one is documented on its parent's page rather than a page of its own:
    // the input's affixes sit with the input, the field's parts sit with the field.
    $views = [
        ...(array) glob(dirname(__DIR__, 2).'/resources/views/components/*.blade.php'),
        ...(array) glob(dirname(__DIR__, 2).'/resources/views/components/*/*.blade.php'),
    ];

    // The whole page this time, code fences included: a component is documented by
    // the example that shows it as much as by the prose around it.
    $documentation = implode('', array_map(shapeFile(...), documentationPages()));

    $undocumented = array_values(array_filter($views, function (string $view) use ($documentation): bool {
        $name = str_replace(
            ['/', '.blade.php'],
            ['.', ''],
            (string) preg_replace('#^.*/resources/views/components/#', '', $view),
        );

        return ! str_contains($documentation, '<shape:'.$name);
    }));

    expect($views)->not->toBeEmpty()
        ->and($undocumented)->toBe([]);
});
