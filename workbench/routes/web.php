<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\MessageBag;
use Illuminate\Support\ViewErrorBag;

// Closures rather than named functions: this file is a route file, and Laravel
// includes it once per application boot. A process that boots twice -- static
// analysis does, and so does anything that builds a second kernel -- would
// redeclare a named function and die before any route was registered.

/**
 * The stylesheet the previews are rendered against.
 *
 * Preview against the package's own theme rather than a duplicate of it, then
 * append a role the package does not ship. ocean.css belongs to the workbench,
 * not to Shape: it stands in for a consuming app's stylesheet, so the gallery
 * proves the open colour set the way a consumer meets it.
 *
 * The in-browser Tailwind build scans the DOM, so every @source directive is
 * stripped: the filesystem one has nothing to point at here, and the inline
 * safelists are redundant when the class names are already in the markup. A
 * real application does need them -- ocean.css says so at the top. gallery.css
 * is the workbench's too, but for the page around the previews rather than for
 * a role in them.
 */
$theme = function (): string {
    $theme = implode("\n", [
        (string) file_get_contents(__DIR__.'/../../resources/css/shape.css'),
        (string) file_get_contents(__DIR__.'/../resources/css/ocean.css'),
        (string) file_get_contents(__DIR__.'/../resources/css/gallery.css'),
    ]);

    return (string) preg_replace('/^@source\b[^;]*;\s*$/m', '', $theme);
};

/**
 * Read a directory of fixtures, keyed by filename.
 */
$fixtures = function (string $directory): array {
    $pages = [];

    foreach (glob(__DIR__.'/../resources/'.$directory.'/*.php') ?: [] as $file) {
        $pages[basename($file, '.php')] = require $file;
    }

    return $pages;
};

// Whole application pages rather than component examples, in the density and the
// mix a real screen has. benchmarks/pages.php reads the same fixture files and
// measures them, which is the point of them being servable: a page anyone can
// open and argue with is worth more as a measurement than one that only ever
// existed as a benchmark string.
Route::get('/page/{page}', function (string $page) use ($theme, $fixtures) {
    $pages = $fixtures('pages');

    abort_unless(isset($pages[$page]), 404);

    // Shared rather than passed, because that is how the framework does it: the
    // field components read `$errors` out of the view factory's shared data,
    // which ShareErrorsFromSession populates on a real request. A fixture that
    // handed it over as render data would reach the page and not the components
    // inside it.
    view()->share('errors', (new ViewErrorBag)->put(
        'default',
        new MessageBag($pages[$page]['errors'] ?? []),
    ));

    return view('pages.show', [
        'pages' => $pages,
        'current' => $page,
        'page' => $pages[$page],
        'data' => isset($pages[$page]['data']) ? ($pages[$page]['data'])() : [],
        'theme' => $theme(),
    ]);
});

// One page per component. Each file in workbench/resources/gallery returns a
// title, a summary and its examples; dropping a new one in there adds it to the
// sidebar, which lists the pages in filename order.
Route::get('/{component?}', function (?string $component = null) use ($theme, $fixtures) {
    $pages = $fixtures('gallery');

    if ($component === null) {
        return redirect(url((string) array_key_first($pages)));
    }

    abort_unless(isset($pages[$component]), 404);

    return view('gallery.page', [
        'pages' => $pages,
        'current' => $component,
        'page' => $pages[$component],
        'theme' => $theme(),
    ]);
});
