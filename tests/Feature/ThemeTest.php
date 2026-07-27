<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Blade;

/**
 * The full variant x colour matrix, rendered. Assertions run against real output
 * rather than the Blade source so that prose in a comment cannot pass or fail them.
 */
function renderedButtonMatrix(): string
{
    $html = '';

    foreach (['solid', 'soft', 'ghost', 'outline'] as $variant) {
        foreach (['primary', 'success', 'warning', 'danger', 'info', 'neutral'] as $color) {
            $html .= Blade::render(
                '<shape:button variant="'.$variant.'" color="'.$color.'">Label</shape:button>',
            );
        }
    }

    return $html;
}

function shapeTheme(): string
{
    return shapeFile('resources/css/shape.css');
}

function shapeFile(string $path): string
{
    return (string) file_get_contents(__DIR__.'/../../'.$path);
}

/**
 * Tailwind's `@source inline()` brace expansion, so a safelist pattern can be
 * compared against the class names components actually render.
 *
 * @param  list<string>  $patterns
 * @return list<string>
 */
function expandSafelist(array $patterns): array
{
    $expand = static function (string $pattern) use (&$expand): array {
        if (preg_match('/^([^{]*)\{([^{}]*)\}(.*)$/s', $pattern, $parts) !== 1) {
            return [$pattern];
        }

        $expanded = [];

        foreach (explode(',', $parts[2]) as $option) {
            foreach ($expand($parts[1].$option.$parts[3]) as $candidate) {
                $expanded[] = $candidate;
            }
        }

        return $expanded;
    };

    $classes = [];

    foreach ($patterns as $pattern) {
        foreach (explode(' ', $pattern) as $candidate) {
            $classes = [...$classes, ...$expand($candidate)];
        }
    }

    return array_values(array_unique($classes));
}

/**
 * The safelist the theme actually applies -- the `@source inline()` lines in
 * column one, as opposed to the indented examples inside its comments.
 *
 * @return list<string>
 */
function shapeSafelist(): array
{
    preg_match_all('/^@source inline\("([^"]+)"\);/m', shapeTheme(), $matches);

    return expandSafelist($matches[1]);
}

/**
 * The safelist a piece of documentation tells consumers to write for a role of
 * their own, normalised onto `primary` so it can be compared with the real one.
 * Every example names the role `ocean`, which is what distinguishes a documented
 * line from a live one.
 *
 * @return list<string>
 */
function documentedSafelist(string $documentation): array
{
    preg_match_all('/@source inline\("([^"]+)"\);/', $documentation, $matches);

    $recipe = array_values(array_filter(
        $matches[1],
        static fn (string $pattern): bool => str_contains($pattern, 'ocean'),
    ));

    return array_map(
        static fn (string $class): string => str_replace('ocean', 'primary', $class),
        expandSafelist($recipe),
    );
}

it('defines every colour token its components reference', function () {
    // Components style themselves against theme tokens, so a token the theme
    // forgets is a class that silently resolves to nothing in a consuming app.
    // Longest alternatives first so "primary-fill-hover" is not read as
    // "primary-fill", which would let a genuinely missing token pass.
    preg_match_all(
        '/\b(primary|success|warning|danger|info|neutral)-(fill-hover|on-fill|fill|tint-hover|on-tint|tint|border|ring|\d{2,3})\b/',
        renderedButtonMatrix(),
        $matches,
        PREG_SET_ORDER,
    );

    $tokens = array_values(array_unique(array_map(
        static fn (array $match): string => $match[1].'-'.$match[2],
        $matches,
    )));

    $theme = shapeTheme();

    $missing = array_values(array_filter(
        $tokens,
        static fn (string $token): bool => ! str_contains($theme, '--color-'.$token.':'),
    ));

    expect($tokens)->not->toBeEmpty()
        ->and($missing)->toBe([]);
});

it('keeps components off bare ramp steps so surfaces stay themeable', function () {
    // The point of the surface layer: overriding one surface must not require
    // redefining what a ramp step means for every other component.
    expect(renderedButtonMatrix())
        ->not->toMatch('/\b(?:bg|text|border|outline)-(?:primary|success|warning|danger|info|neutral)-\d{2,3}\b/');
});

it('resolves dark mode in the theme rather than in component markup', function () {
    expect(shapeTheme())->toContain('light-dark(');
    expect(renderedButtonMatrix())->not->toContain('dark:');
});

it('tells Tailwind to scan the package views', function () {
    // Without this the consumer imports the theme, gets the variables, and still
    // sees unstyled components because no utility class was ever generated.
    expect(shapeTheme())->toContain('@source');
});

it('safelists every role-based class its components render', function () {
    // Components interpolate the role into `bg-:role-fill`, so Tailwind's scanner
    // never meets the literal string and emits nothing for it -- the safelist is
    // the only reason any of this compiles. A class the safelist misses is not a
    // build error: it renders in the markup and resolves to no style at all, which
    // is exactly the failure this pins down. Adding a role, or a component that
    // reaches for a surface in a new way, has to be reflected there.
    preg_match_all(
        '/[a-z-]*:?[a-z-]+-(?:primary|success|warning|danger|info|neutral)-[a-z-]+/',
        renderedButtonMatrix(),
        $matches,
    );

    $rendered = array_values(array_unique($matches[0]));
    $safelisted = shapeSafelist();

    expect($rendered)->not->toBeEmpty()
        ->and(array_values(array_diff($rendered, $safelisted)))->toBe([]);
});

it('safelists nothing it does not use, so the theme carries no dead rules', function () {
    // The lazy version of this block is one cross product of every utility against
    // every surface. It is shorter to write and compiles 134 unusable rules.
    preg_match_all(
        '/[a-z-]*:?[a-z-]+-(?:primary|success|warning|danger|info|neutral)-[a-z-]+/',
        renderedButtonMatrix(),
        $matches,
    );

    $unused = array_values(array_diff(shapeSafelist(), array_unique($matches[0])));

    expect($unused)->toBe([]);
});

it('documents a safelist recipe that matches the one it actually uses', function (string $path) {
    // Adding a colour role is the consumer's job, so the recipe is documentation
    // rather than code -- and documentation is exactly what rots when the real
    // safelist changes. Pinning the two together is what stops a consumer from
    // following instructions and getting a button that renders with no style.
    $documented = documentedSafelist(shapeFile($path));

    $mine = array_values(array_filter(
        shapeSafelist(),
        static fn (string $class): bool => str_contains($class, 'primary'),
    ));

    expect($documented)->not->toBeEmpty()
        ->and($documented)->toEqualCanonicalizing($mine);
})->with([
    'the theme itself' => ['resources/css/shape.css'],
    'the README' => ['README.md'],
]);

it('ships no colour role beyond the six it uses', function () {
    // Tailwind keeps any theme variable another kept variable references, so an
    // unused role is not free -- it reaches every application whether or not that
    // application ever names it. A second brand colour is the consumer's to define
    // and the consumer's to name, which is why none is bundled here.
    preg_match_all('/--color-([a-z]+)-(?:fill|tint|border|ring):/', shapeTheme(), $matches);

    expect(array_values(array_unique($matches[1])))
        ->toEqualCanonicalizing(['primary', 'success', 'warning', 'danger', 'info', 'neutral']);
});

it('maps roles onto palette ramps rather than hardcoding colour values', function () {
    expect(shapeTheme())
        ->toContain('--color-primary-700: var(--color-indigo-700)')
        ->not->toMatch('/--color-(primary|success|warning|danger|info|neutral)-\d+:\s*#/');
});

it('keeps the warning role legible by inverting its solid fill', function () {
    // Every other role clears AA as a -700 fill under white text; amber does not,
    // so warning fills light with dark text instead.
    expect(shapeTheme())
        ->toContain('--color-warning-fill: var(--color-warning-400)')
        ->toContain('--color-warning-on-fill: var(--color-warning-950)')
        ->toContain('--color-primary-on-fill: var(--color-white)');
});

it('orders the colour-scheme rules so a root-level dark class still wins', function () {
    // `<html class="dark">` is the standard Tailwind setup, and there `:root` and
    // `.dark` match the same element with equal specificity -- only source order
    // decides. Reversing these two blocks breaks dark mode for most consumers
    // without breaking anything else, so the order is pinned here.
    $theme = shapeTheme();

    expect(strpos($theme, ':root {'))->toBeLessThan(strpos($theme, '.dark {'));
});

it('declares a colour scheme so light-dark() has something to resolve against', function () {
    // light-dark() silently returns the light value everywhere without this.
    expect(shapeTheme())
        ->toContain('color-scheme: light dark')
        ->toContain('color-scheme: dark');
});
