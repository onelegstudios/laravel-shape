<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Shape / Blaze whole-page benchmark
|--------------------------------------------------------------------------
|
| benchmarks/blaze.php measures strategies against the gallery, which is a
| catalogue: every component at once, in a density no screen has. This measures
| whole application pages instead, and exists to answer one question the gallery
| cannot -- whether folding the field family is worth doing.
|
|   php benchmarks/pages.php [--renders=N] [--page=slug]
|
| The pages are the fixtures in workbench/resources/pages, which are also served
| at /page/{slug} by `composer serve`. That is deliberate: a page anyone can open
| and argue with is worth more as a measurement than one that only ever existed
| as a benchmark string.
|
| Three modes:
|
|   blade    Blaze disabled entirely -- Blade's own component pipeline.
|   blaze    The package as it ships: button and icon fold, the families compile.
|   fold     A scratch copy of every component with `fold: true`, its config
|            reads inlined as literals and its `$errors` reads stubbed out. This
|            is not a correct build of Shape and could not be shipped -- it is
|            what stage 3 would have to reach, standing in for it so the ceiling
|            can be measured before the work is done.
|
| What to read off it:
|
|   - The census line, not the timings, is the point. Folding is a compile-time
|     evaluation, so a call site binding a value declines it -- and how many of a
|     real page's call sites can fold at all bounds everything else. That is the
|     number the Livewire and records fixtures exist to supply.
|   - `fold events` counts what the compiler collapsed, nested components
|     included, so it can exceed the tag count and is not a share of it. It is
|     reported because it is ground truth from Blaze's own markers; the census
|     beside it is what says whether folding reaches the code you write.
|   - `fold` timings are an upper bound rather than a forecast. Stubbing the
|     error bag removes work that a real implementation would have to put back
|     inside an island.
|   - Output is hashed and compared across modes. `fold` is held to it only on
|     pages that declare no validation errors, because stubbing the bag is a
|     visible difference on the ones that do -- which is itself the reason that
|     transform cannot ship.
|
*/

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;
use Livewire\Blaze\Blaze;
use Onelegstudios\Shape\Icons\Libraries;
use Orchestra\Testbench\Foundation\Application;
use Orchestra\Testbench\Foundation\Config;

require __DIR__.'/../vendor/autoload.php';

// Same reason benchmarks/blaze.php raises it: Blade compiles a template by
// running token_get_all() over the whole of it, and a page repeated for a
// steady-state measurement is a large template.
ini_set('memory_limit', '1G');

const MODES = ['blade', 'blaze', 'fold'];

$options = getopt('', ['renders::', 'mode::', 'page::']);
$renders = max(1, (int) ($options['renders'] ?? 40));
$mode = is_string($options['mode'] ?? null) ? $options['mode'] : null;
$only = is_string($options['page'] ?? null) ? $options['page'] : null;

$basePath = dirname(__DIR__);

$mode === null
    ? runDriver($renders, $only)
    : runMode($basePath, $mode, $renders, $only);

/**
 * Run each mode in its own process and print the comparison.
 */
function runDriver(int $renders, ?string $only): void
{
    $results = [];

    foreach (MODES as $mode) {
        $command = sprintf(
            '%s %s --mode=%s --renders=%d%s 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__FILE__),
            escapeshellarg($mode),
            $renders,
            $only === null ? '' : ' --page='.escapeshellarg($only),
        );

        $output = (string) shell_exec($command);

        $lines = array_values(array_filter(array_map('trim', explode("\n", $output)), fn ($l) => $l !== ''));

        $decoded = $lines === [] ? null : json_decode((string) end($lines), true);

        if (! is_array($decoded)) {
            fwrite(STDERR, "Mode [{$mode}] failed:\n".$output."\n");
            exit(1);
        }

        $results[$mode] = $decoded;
    }

    printf("\nShape / Blaze whole-page benchmark\n");
    printf("%s\n", str_repeat('=', 84));
    printf("renders/page/mode: %d\n", $renders);

    foreach (array_keys($results['blade']) as $page) {
        $census = $results['blade'][$page]['census'];

        printf("\n%s -- %d Shape tags (%d field family, %d button, %d icon); %d of them bind a value\n",
            $page, $census['total'], $census['family'], $census['button'], $census['icon'], $census['dynamic']);
        printf("%s\n", str_repeat('-', 84));
        printf("%-8s %11s %10s %11s %9s %13s\n", 'mode', 'median ms', 'min ms', 'compile ms', 'vs blade', 'fold events');
        printf("%s\n", str_repeat('-', 84));

        foreach (MODES as $mode) {
            $row = $results[$mode][$page];

            printf("%-8s %11.3f %10.3f %11.1f %8.1fx %13s\n",
                $mode,
                $row['median_ms'],
                $row['min_ms'],
                $row['compile_ms'],
                $results['blade'][$page]['median_ms'] / $row['median_ms'],
                $row['folded'] === 0 ? '-' : (string) $row['folded'],
            );
        }

        // Identity is the check that stops a mode being reported as faster when it
        // is really rendering something else. `fold` is exempt only where stubbing
        // the error bag is visible, which is the pages that declare messages.
        $hashes = array_map(fn (string $m): string => $results[$m][$page]['hash'], MODES);

        $strict = $results['blade'][$page]['census']['errors']
            ? array_slice($hashes, 0, 2)
            : $hashes;

        printf("identical output: %s%s\n",
            count(array_unique($strict)) === 1 ? 'yes' : 'NO',
            $results['blade'][$page]['census']['errors'] ? '  (blade/blaze only -- fold stubs the error bag)' : '',
        );
    }

    printf("\n");
}

/**
 * Measure every page in a single mode and print the results as JSON.
 */
function runMode(string $basePath, string $mode, int $renders, ?string $only): void
{
    if (! in_array($mode, MODES, true)) {
        fwrite(STDERR, "Unknown mode [{$mode}].\n");
        exit(1);
    }

    $app = Application::createFromConfig(
        Config::loadFromYaml($basePath),
        options: ['enables_package_discoveries' => true],
    );

    $app->make(Kernel::class)->bootstrap();

    if ($mode === 'blade') {
        Blaze::disable();
    }

    $scratch = $app->basePath('vendor/orchestra/testbench-core/laravel/storage/framework/pagebench');

    File::ensureDirectoryExists($scratch.'/views');

    View::addNamespace('pagebench', $scratch.'/views');

    $tag = 'shape';

    if ($mode === 'fold') {
        $tag = registerFoldableFamily($basePath, $scratch);
    }

    $pages = [];

    foreach (glob($basePath.'/workbench/resources/pages/*.php') ?: [] as $file) {
        $slug = basename($file, '.php');

        if ($only !== null && $slug !== $only) {
            continue;
        }

        $pages[$slug] = require $file;
    }

    if ($pages === []) {
        fwrite(STDERR, "No page fixtures found.\n");
        exit(1);
    }

    publishPageIcons($pages);

    // Once, at the start of the process, rather than once per page. A cold cache
    // is what makes the compile timings meaningful, but emptying the directory
    // underneath a booted application is not the same thing: Blade and Blaze both
    // hold resolved paths in memory, and a file deleted after it was resolved is
    // read back as missing. Pages compile to distinct files, so one sweep here is
    // enough and nothing collides afterwards.
    clearCompiledViews();

    $results = [];

    foreach ($pages as $slug => $page) {
        $results[$slug] = measure($app, $scratch, $slug, $page, $tag, $renders);
    }

    File::deleteDirectory($scratch);

    echo "\n".json_encode($results)."\n";
}

/**
 * Compile one page once, then time steady-state renders against a warm cache.
 *
 * @param  array<string, mixed>  $page
 * @return array<string, mixed>
 */
function measure(mixed $app, string $scratch, string $slug, array $page, string $tag, int $renders): array
{
    $markup = (string) $page['markup'];

    // In `fold` mode the components live under a scratch prefix, so the call
    // sites have to name it. The branded `<shape:*>` syntax is rewritten by the
    // service provider before compilation; this is the same rewrite done ahead
    // of time, against a different namespace.
    if ($tag !== 'shape') {
        $markup = (string) preg_replace('/<(\/?)shape:(?=[\w-])/', '<${1}x-'.$tag.'::', $markup);
    }

    View::share('errors', (new ViewErrorBag)->put('default', new MessageBag($page['errors'] ?? [])));

    $data = isset($page['data']) ? ($page['data'])() : [];

    $view = $scratch.'/views/'.$slug.'.blade.php';

    File::put($view, $markup);

    $compileStart = hrtime(true);
    $html = View::make('pagebench::'.$slug, $data)->render();
    $compileMs = (hrtime(true) - $compileStart) / 1e6;

    // Warm-up, so the first timed render is not paying for anything the compile
    // left to be resolved once.
    for ($i = 0; $i < 3; $i++) {
        View::make('pagebench::'.$slug, $data)->render();
    }

    $samples = [];

    for ($i = 0; $i < $renders; $i++) {
        $start = hrtime(true);
        View::make('pagebench::'.$slug, $data)->render();
        $samples[] = (hrtime(true) - $start) / 1e6;
    }

    sort($samples);

    return [
        'census' => census($page),
        'compile_ms' => $compileMs,
        'median_ms' => $samples[intdiv(count($samples), 2)],
        'min_ms' => $samples[0],
        'folded' => foldedCallSites($view),
        'bytes' => strlen($html),
        'hash' => md5(trim((string) preg_replace('/\s+/', ' ', $html))),
    ];
}

/**
 * What a page is made of, counted from its source rather than its output.
 *
 * @param  array<string, mixed>  $page
 * @return array<string, int|bool>
 */
function census(array $page): array
{
    preg_match_all('/<shape:([\w.-]+)((?:[^>"\']|"[^"]*"|\'[^\']*\')*)/', (string) $page['markup'], $matches);

    $tags = $matches[1];

    // Whether a call site could fold at all, which is the number the stage 3
    // decision turns on. A bound attribute (`:label="$x"`) or an echo inside a
    // value (`wire:model="{{ $x }}"`) is a value known only at render, and folding
    // is a compile-time evaluation -- so a call site carrying either declines.
    //
    // Counted from the markup rather than from the compiler's markers, and the two
    // answer different questions: markers count fold *events*, nested components
    // included, so a page can report more folds than it has tags. This counts the
    // tags somebody wrote.
    $dynamic = count(array_filter(
        $matches[2],
        fn (string $attributes): bool => preg_match('/\s:[\w.-]+=/', $attributes) === 1
            || str_contains($attributes, '{{'),
    ));

    // The loop bodies are written once and rendered many times, so a count of tags
    // in the source understates a page with a table on it. Multiplying by the data
    // the fixture supplies would be guesswork; the honest thing is to count call
    // sites, which is also the unit folding works in.
    $family = ['input', 'select', 'textarea', 'file', 'checkbox', 'radio', 'switch', 'range',
        'color', 'field', 'label', 'description', 'error', 'legend', 'input.prefix', 'input.suffix'];

    return [
        'total' => count($tags),
        'dynamic' => $dynamic,
        'family' => count(array_intersect($tags, $family)),
        'button' => count(array_filter($tags, fn (string $t): bool => $t === 'button')),
        'icon' => count(array_filter($tags, fn (string $t): bool => $t === 'icon')),
        'errors' => ($page['errors'] ?? []) !== [],
    ];
}

/**
 * How many call sites the compiler folded in this page.
 *
 * Counted from Blaze's own markers rather than worked out from the markup: the
 * fold decision belongs to Folder::isSafeToFold(), and a benchmark that
 * reimplemented it with a regex would be reporting its own opinion rather than
 * the compiler's.
 *
 * Read from this page's compiled file alone, not the whole directory, because
 * the directory holds every page the process has measured so far. The config
 * stamp ShapeServiceProvider writes is excluded -- it is a dependency record
 * rather than a folded call site.
 */
function foldedCallSites(string $view): int
{
    $compiled = Blade::getCompiledPath($view);

    if (! File::isFile($compiled)) {
        return 0;
    }

    preg_match_all('/\[BlazeFolded\]:\{([^}]+)\}/', (string) File::get($compiled), $matches);

    // Component names carry their namespace (`shape::icon`); the stamps
    // ShapeServiceProvider writes for the config file and for icon artwork do
    // not. Those are dependency records rather than folded call sites.
    return count(array_filter($matches[1], fn (string $name): bool => str_contains($name, '::')));
}

/**
 * Copy every component into a scratch namespace with folding forced on.
 *
 * Three transforms, and each one stands for a decision stage 3 would have to
 * make for real:
 *
 *   - config reads become the literal defaults they resolve to today. The button
 *     already solved this properly, by stamping the config file as a fold
 *     dependency; the rest of the family would do the same, and inlining is the
 *     cheap stand-in for measuring.
 *   - `$errors` reads are stubbed to null. Blaze refuses to fold a component
 *     that reads the bag at all -- see Folder::checkProblematicPatterns -- so
 *     something has to give, and a real implementation would put the read in an
 *     `@unblaze` island rather than delete it. This is the transform that makes
 *     the mode a ceiling rather than a forecast.
 *   - internal `<x-shape::*>` references are repointed at the copies, so the
 *     whole tree is the scratch one and a folded parent folds its children.
 *
 * Returns the tag prefix the copies are registered under.
 */
function registerFoldableFamily(string $basePath, string $scratch): string
{
    $source = $basePath.'/resources/views/components';

    File::ensureDirectoryExists($scratch.'/fold/components/input');
    File::ensureDirectoryExists($scratch.'/fold/components/header');

    foreach (['*.blade.php', 'input/*.blade.php', 'header/*.blade.php'] as $pattern) {
        foreach (File::glob($source.'/'.$pattern) ?: [] as $file) {
            $sub = basename(dirname($file));
            $sub = in_array($sub, ['input', 'header'], true) ? $sub.'/' : '';

            File::put(
                $scratch.'/fold/components/'.$sub.basename($file),
                foldable((string) File::get($file)),
            );
        }
    }

    View::addNamespace('foldfam', $scratch.'/fold');
    Blade::anonymousComponentNamespace('foldfam::components', 'foldfam');

    return 'foldfam';
}

/**
 * Apply the fold transforms to one component's source.
 */
function foldable(string $source): string
{
    $source = str_replace('x-shape::', 'x-foldfam::', $source);

    $source = (string) preg_replace_callback(
        "/config\('shape\.components\.([a-z]+)'\)/",
        fn (array $m): string => var_export(
            array_filter((array) config('shape.components.'.$m[1]), 'is_string'),
            true,
        ),
        $source,
    );

    // The live reads first, then everything left over. Blaze looks for `$errors`
    // in the raw source, comments included, so neutralising the two reads is not
    // enough on its own -- a sentence explaining why the guard is written that way
    // trips the check as surely as the guard does. The sweep also catches the arm
    // of the error component's ternary that the `false` above has just made dead.
    $source = str_replace(['$errors ?? null', 'isset($errors)'], ['null', 'false'], $source);
    $source = (string) preg_replace('/\$errors\b/', '$__stubbedErrors', $source);

    $source = (string) preg_replace('/^@blaze(\([^)]*\))?\s*$/m', '', ltrim($source));

    // `unsafe` is not decoration, and the reason is worth stating because it is the
    // sharpest thing this benchmark found.
    //
    // Blaze treats every `@props` name as unsafe already, so a bound prop declines
    // the fold on its own. What it cannot know is that this family reads three
    // attributes it deliberately does *not* declare -- `name`, `value` and `id` all
    // have to reach the rendered element, so they stay on the bag and are read out
    // of it. To the compiler they look like pass-through, which means a bound one
    // is allowed to fold: the attribute is swapped for a placeholder, rendered, and
    // the placeholder swapped back wherever it appears verbatim.
    //
    // It does not appear verbatim. `Control::resolve()` runs both through
    // `Fields::id()` to derive the id the label points at, and that rewrites the
    // placeholder's underscores -- so `BLAZE_PLACEHOLDER_0_` becomes
    // `BLAZE-PLACEHOLDER-0`, no longer matches, and is served to the browser inside
    // an `id`. The `value` attribute beside it restores correctly, which is what
    // makes it quiet: a row of checkboxes with the right values and one frozen id.
    //
    // Declaring them is what a shipped implementation would have to do, and it
    // costs real folds -- every call site binding a name or a value now declines,
    // which the numbers below are supposed to reflect rather than flatter.
    return "@blaze(fold: true, unsafe: ['name', 'value', 'id'])\n\n".ltrim($source);
}

/**
 * Publish every icon the fixtures name, as an application would.
 *
 * @param  array<string, array<string, mixed>>  $pages
 */
function publishPageIcons(array $pages): void
{
    $markup = implode("\n", array_map(fn (array $p): string => (string) $p['markup'], $pages));

    // Both spellings: an icon named on an <shape:icon> tag, and one handed to a
    // component as a prop. The dashboard's cards use a bound `:name`, which no
    // regex can resolve -- those come from the fixture's own data, so the pattern
    // below picks up the quoted names and Libraries::required() covers the marks
    // the components draw for themselves.
    preg_match_all('/(?:\bicon(?:-trailing)?|\bname)="([\w-]+)"/', $markup, $matches);

    $names = array_values(array_unique(array_merge(
        $matches[1],
        Libraries::required(),
        // The dashboard's stat tiles name these through bound props.
        ['circle-check', 'circle-x', 'info', 'triangle-alert'],
    )));

    // Names that are not icons -- a field's `name="email"` matches the pattern
    // above too -- make the command fail, so they are filtered by asking it.
    foreach ($names as $name) {
        Artisan::call('shape:icon:add', [
            'name' => [$name],
            '--no-clear' => true,
        ]);
    }
}

/**
 * Empty the compiled view directory, Blaze's scratch subdirectories included.
 */
function clearCompiledViews(): void
{
    $compiled = (string) config('view.compiled');

    if ($compiled === '' || ! File::isDirectory($compiled)) {
        return;
    }

    foreach (File::glob($compiled.'/*.php') ?: [] as $file) {
        File::delete($file);
    }

    foreach (File::directories($compiled) as $directory) {
        File::deleteDirectory($directory);
    }
}
