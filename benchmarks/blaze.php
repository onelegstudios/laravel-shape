<?php

declare(strict_types=1);

/*
|--------------------------------------------------------------------------
| Blaze strategy benchmark
|--------------------------------------------------------------------------
|
| Answers one question: on a page built from Shape's real component markup,
| how much does each rendering strategy actually save?
|
|   php benchmarks/blaze.php [--repeat=N] [--renders=N]
|
| Three modes are compared:
|
|   blade    Blaze disabled entirely -- Blade's own component pipeline.
|   runtime  Blaze on, but with an icon that resolves rather than publishes:
|            reading its set, its aliases and its SVG on every render. Those
|            config reads make it unfoldable, so this is the ceiling the
|            published icon is measured against.
|   blaze    Blaze on, components as the package ships them: the icon and the
|            button fold, the field and header families are compiled.
|
| There used to be a fourth, `fold`, which generated a foldable button by
| rewriting its config reads into literal @props defaults -- the button folds as
| shipped now, so the mode measured the package against itself. What is left on
| the table has moved to the field family, and measuring that needs the folded
| family to exist first; it does not, because folding it bakes the counter that
| invents an id for an unnamed field. See docs/performance.md.
|
| Method notes, because they decide whether the numbers mean anything:
|
|   - Each mode runs in its own process. Blaze writes temporary templates into
|     the compiled-view directory while folding, and switching strategies
|     inside one process leaves those behind for the next mode to trip over.
|     The compiled-view directory is emptied at the start of every mode as
|     well: a fresh process does not give a cold cache on its own, because the
|     cache lives on disk and outlives the run that wrote it.
|   - The gallery renders each example through Blade::render() at request time,
|     which compiles a fresh template per call. That is a gallery affordance,
|     not how an application page behaves, so this takes the gallery's markup
|     and puts it in one ordinary view compiled once and rendered many times --
|     a warm view cache, which is what production runs with.
|   - Compilation is timed separately from rendering. Folding moves work to
|     compile time, so charging it to the render loop would flatter it.
|   - Modes that should fold assert that the compiled view actually contains
|     Blaze's [BlazeFolded] marker, so a component that quietly declined to
|     fold cannot be reported as a folding result.
|   - Every mode reports a hash of its normalised HTML. They must all agree, or
|     the timings are comparing different work.
|
*/

use Illuminate\Contracts\Console\Kernel;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\View;
use Livewire\Blaze\Blaze;
use Orchestra\Testbench\Foundation\Application;
use Orchestra\Testbench\Foundation\Config;

require __DIR__.'/../vendor/autoload.php';

// The whole gallery stacked `--repeat` times is one very large template, and
// Blade compiles it by running `token_get_all()` over the lot. At the default
// `--repeat=10` that alone clears PHP's usual 128M, and the failure arrives as
// an exhausted-memory fatal from inside the compiler rather than as anything
// that looks like a benchmark problem. Raised here rather than in the composer
// script so that a direct `php benchmarks/blaze.php --mode=blade` gets it too.
ini_set('memory_limit', '1G');

const MODES = ['blade', 'runtime', 'blaze'];

$options = getopt('', ['repeat::', 'renders::', 'mode::']);
$repeat = max(1, (int) ($options['repeat'] ?? 10));
$renders = max(1, (int) ($options['renders'] ?? 60));
$mode = is_string($options['mode'] ?? null) ? $options['mode'] : null;

$basePath = dirname(__DIR__);

$mode === null
    ? runDriver($repeat, $renders)
    : runMode($basePath, $mode, $repeat, $renders);

/**
 * Run each mode in its own process and print the comparison.
 */
function runDriver(int $repeat, int $renders): void
{
    $results = [];

    foreach (MODES as $mode) {
        $command = sprintf(
            '%s %s --mode=%s --repeat=%d --renders=%d 2>&1',
            escapeshellarg(PHP_BINARY),
            escapeshellarg(__FILE__),
            escapeshellarg($mode),
            $repeat,
            $renders,
        );

        $output = (string) shell_exec($command);

        $lines = array_values(array_filter(array_map('trim', explode("\n", $output)), fn ($l) => $l !== ''));

        $decoded = $lines === [] ? null : json_decode((string) end($lines), true);

        if (! is_array($decoded)) {
            fwrite(STDERR, "Mode [{$mode}] failed:\n".$output."\n");
            exit(1);
        }

        $results[] = $decoded;
    }

    $baseline = $results[0]['median_ms'];
    $components = $results[0]['components'];

    printf("\nShape / Blaze strategy benchmark\n");
    printf("%s\n", str_repeat('=', 78));
    printf(
        "components/page: %d   repeat: %d   renders/mode: %d   output: %.1f KB\n\n",
        $components,
        $repeat,
        $renders,
        $results[0]['bytes'] / 1024,
    );

    printf("%-9s %11s %10s %10s %11s %9s %9s\n", 'mode', 'median ms', 'min ms', 'p95 ms', 'compile ms', 'vs blade', 'us/comp');
    printf("%s\n", str_repeat('-', 78));

    foreach ($results as $row) {
        printf(
            "%-9s %11.3f %10.3f %10.3f %11.1f %8.1fx %9.1f\n",
            $row['mode'],
            $row['median_ms'],
            $row['min_ms'],
            $row['p95_ms'],
            $row['compile_ms'],
            $baseline / $row['median_ms'],
            ($row['median_ms'] * 1000) / max(1, $components),
        );
    }

    // Two different claims, so two checks. blade and blaze render the same
    // components through different pipelines and must agree byte for byte.
    // `runtime` is a different implementation of the icon, so it is held to
    // equivalence instead: same elements, same classes, same values, allowing
    // for attribute order and the whitespace a separate template file adds.
    $byMode = array_column($results, null, 'mode');

    $strict = array_unique(array_column(
        array_intersect_key($byMode, array_flip(['blade', 'blaze'])),
        'hash',
    ));

    printf("\nblade / blaze byte-identical: %s\n", count($strict) === 1 ? 'yes' : 'NO');
    printf(
        "runtime equivalent to shipped (formatting aside): %s\n",
        ($byMode['runtime']['nhash'] ?? null) === ($byMode['blaze']['nhash'] ?? false) ? 'yes' : 'NO',
    );

    if (count($strict) !== 1) {
        foreach ($results as $row) {
            printf("  %-9s %s\n", $row['mode'], $row['hash']);
        }
    }

    printf("\n");
}

/**
 * Measure a single mode and print its result as JSON.
 */
function runMode(string $basePath, string $mode, int $repeat, int $renders): void
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

    $scratch = $app->basePath('vendor/orchestra/testbench-core/laravel/storage/framework/bench');

    File::ensureDirectoryExists($scratch.'/views');

    $markup = galleryMarkup($basePath, $repeat);
    $components = preg_match_all('/<shape:[\w-]+/', $markup);

    publishGalleryIcons($markup);

    if ($mode === 'runtime') {
        registerRuntimeIcon($scratch);

        $markup = str_replace('<shape:icon', '<x-benchruntime::icon', $markup);
    }

    if ($mode === 'blade') {
        Blaze::disable();
    }

    View::addNamespace('bench', $scratch.'/views');

    // A cold compiled cache, and it has to be taken rather than assumed.
    //
    // Every mode compiles the same component files, and Blade and Blaze both key
    // their compiled output on the source path -- so a file left behind by the
    // previous mode is picked up by the next one, which then measures the wrong
    // pipeline or trips over a function the current mode never generated. The
    // icon publish above only clears when it actually published something, so a
    // second run in a row -- every icon already on disk -- clears nothing at all.
    //
    // It is also what makes `compile_ms` mean anything: timing compilation
    // against a warm cache times a file read.
    clearCompiledViews();

    $result = measure(
        $mode,
        $markup,
        $scratch.'/views/page.blade.php',
        $renders,
        $mode === 'blaze',
    );

    if (getenv('BENCH_DUMP') !== false) {
        File::put((string) getenv('BENCH_DUMP').'/'.$mode.'.html', normaliseHtml($result['html']));
    }

    unset($result['html']);

    File::deleteDirectory($scratch);

    echo "\n".json_encode($result + ['components' => $components])."\n";
}

/**
 * Every example from workbench/resources/gallery, stacked `--repeat` times.
 *
 * That is the densest real Shape markup in the repository, and stacking stands
 * in for a page carrying more components than one gallery screen does.
 */
function galleryMarkup(string $basePath, int $repeat): string
{
    $examples = [];

    foreach (glob($basePath.'/workbench/resources/gallery/*.php') ?: [] as $file) {
        $page = require $file;

        foreach ($page['examples'] ?? [] as $example) {
            $examples[] = $example['source'];
        }
    }

    if ($examples === []) {
        fwrite(STDERR, "No gallery examples found.\n");
        exit(1);
    }

    // Shape's own nesting, which is where folding is supposed to compound: an
    // icon inside a button, inside the markup a real page wraps around them.
    $nested = <<<'BLADE'
        <div class="flex items-center gap-2">
            <shape:button variant="solid" color="primary" size="sm">
                <shape:icon name="check" size="sm" />
                Save
            </shape:button>
            <shape:button variant="outline" color="neutral" size="sm">
                <shape:icon name="x" size="sm" />
                Cancel
            </shape:button>
        </div>
        BLADE;

    return str_repeat(implode("\n", array_merge($examples, [$nested]))."\n", $repeat);
}

/**
 * Publish the icons the markup names.
 *
 * The icon component renders published components, so they have to be on disk
 * before anything can be measured -- as they would be in an application.
 */
function publishGalleryIcons(string $markup): void
{
    preg_match_all('/<shape:icon[^>]*\bname="([^"]+)"/', $markup, $matches);

    $names = array_values(array_unique($matches[1]));

    if ($names === []) {
        return;
    }

    if (Artisan::call('shape:icon:add', ['name' => $names]) !== 0) {
        fwrite(STDERR, "Could not publish the gallery's icons:\n".Artisan::output());
        exit(1);
    }
}

/**
 * The icon component as it would be without publishing.
 *
 * A faithful equivalent: set, aliases and size read from config on every render,
 * and Blade Icons asked for the SVG. It is the honest comparison for what
 * publishing buys, and it cannot fold -- those config reads are exactly what
 * folding would freeze.
 */
function registerRuntimeIcon(string $scratch): void
{
    $source = <<<'BLADE'
        @blaze

        @props(['name' => '', 'set' => null, 'size' => null, 'color' => null, 'label' => null])

        @php
            $defaults = array_filter((array) config('shape.components.icon'), 'is_string');
            $size ??= $defaults['size'] ?? 'md';

            $icons = (array) config('shape.icons');
            $sets = array_filter((array) ($icons['sets'] ?? []), 'is_string');
            $aliases = array_filter((array) ($icons['aliases'] ?? []), 'is_string');
            $set ??= is_string($icons['set'] ?? null) ? $icons['set'] : 'lucide';

            // The packaged table under the application's own, which is the order
            // `shape:icon:add` resolves in -- `config('shape.icons.aliases')` ships
            // empty, and the names Shape's own views use (`spinner`, `error`,
            // `checkbox-check`, `select-chevron`) live only in Libraries. Without
            // this the replica asks Blade Icons for `lucide-spinner`, which does
            // not exist, and the mode dies on markup the other three render.
            $aliases += \Onelegstudios\Shape\Icons\Libraries::aliases($set);

            $name = $aliases[$name] ?? $name;
            $prefix = $sets[$set] ?? $set;
            $icon = $prefix === '' ? $name : $prefix.'-'.$name;

            $sizes = ['xs' => 'size-3.5', 'sm' => 'size-4', 'md' => 'size-5', 'lg' => 'size-6'];
            $scale = $sizes[$size] ?? $sizes['md'];

            $tint = is_string($color) && preg_match('/^[a-z][a-z0-9]*(-[a-z0-9]+)*$/', $color) === 1
                ? ' text-'.$color.'-on-tint'
                : '';

            $a11y = $label === null
                ? ['aria-hidden' => 'true']
                : ['role' => 'img', 'aria-label' => e($label, false)];

            $attrs = $attributes->merge(['class' => $scale.$tint.' shrink-0'])->getAttributes();
        @endphp

        {{ svg($icon, '', array_merge($a11y, $attrs)) }}
        BLADE;

    File::ensureDirectoryExists($scratch.'/runtime/components');
    File::put($scratch.'/runtime/components/icon.blade.php', $source);

    View::addNamespace('benchruntime', $scratch.'/runtime');
    Blade::anonymousComponentNamespace('benchruntime::components', 'benchruntime');
}

/**
 * Compile once, then time steady-state renders against a warm view cache.
 *
 * @return array<string, mixed>
 */
function measure(string $mode, string $markup, string $viewFile, int $renders, bool $expectFold): array
{
    File::put($viewFile, $markup);

    $compileStart = hrtime(true);
    $html = View::make('bench::page')->render();
    $compileMs = (hrtime(true) - $compileStart) / 1e6;

    if ($expectFold && ! compiledViewsContainFoldMarker()) {
        fwrite(STDERR, "Mode [{$mode}] did not fold -- refusing to report it as a folding result.\n");
        exit(1);
    }

    for ($i = 0; $i < 3; $i++) {
        View::make('bench::page')->render();
    }

    $samples = [];

    for ($i = 0; $i < $renders; $i++) {
        $start = hrtime(true);
        View::make('bench::page')->render();
        $samples[] = (hrtime(true) - $start) / 1e6;
    }

    sort($samples);

    return [
        'mode' => $mode,
        'compile_ms' => $compileMs,
        'median_ms' => $samples[intdiv(count($samples), 2)],
        'min_ms' => $samples[0],
        'p95_ms' => $samples[(int) floor(count($samples) * 0.95)] ?? end($samples),
        'bytes' => strlen($html),
        'hash' => md5(trim((string) preg_replace('/\s+/', ' ', $html))),
        'nhash' => md5(normaliseHtml($html)),
        'html' => $html,
    ];
}

/**
 * Collapse whitespace and sort attributes and class tokens before hashing.
 *
 * Attribute order and class order are not part of what the strategies have to
 * agree on: the runtime icon hands its attributes to Blade Icons in a
 * different order from the one a published component merges them in, and both
 * are correct HTML. Sorting normalises that away so the comparison stays
 * strict about everything that does matter -- which element, which classes,
 * which values, in which order on the page.
 */
function normaliseHtml(string $html): string
{
    $html = (string) preg_replace_callback(
        '/class="([^"]*)"/',
        function (array $m): string {
            $classes = preg_split('/\s+/', trim($m[1])) ?: [];

            sort($classes);

            return 'class="'.implode(' ', $classes).'"';
        },
        $html,
    );

    $html = (string) preg_replace_callback(
        '/<([a-zA-Z][\w:-]*)\s+([^>]*?)(\/?)>/s',
        function (array $m): string {
            preg_match_all('/[\w:@.\-\[\]]+(?:="[^"]*")?/', $m[2], $attributes);

            $sorted = $attributes[0];

            sort($sorted);

            return '<'.$m[1].($sorted === [] ? '' : ' '.implode(' ', $sorted)).$m[3].'>';
        },
        $html,
    );

    $html = (string) preg_replace('/\s+/', ' ', $html);

    // Whitespace around elements too: a published component's own file ends in
    // a newline where an inline `{{ svg(...) }}` does not, and that is template
    // formatting rather than a difference in what was rendered.
    return trim((string) preg_replace(['/>\s+/', '/\s+</'], ['>', '<'], $html));
}

/**
 * Empty the compiled view directory.
 *
 * Blaze writes its generated component functions into `view.compiled` alongside
 * Blade's own compiled templates, so one sweep covers both pipelines.
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

    // Blaze keeps folded-component bookkeeping in a subdirectory of its own.
    foreach (File::directories($compiled) as $directory) {
        File::deleteDirectory($directory);
    }
}

/**
 * Whether anything in the compiled views was folded.
 */
function compiledViewsContainFoldMarker(): bool
{
    foreach (File::glob(((string) config('view.compiled')).'/*.php') ?: [] as $file) {
        if (str_contains((string) File::get($file), '[BlazeFolded]')) {
            return true;
        }
    }

    return false;
}
