<?php

use Illuminate\Support\Facades\Route;

// One page per component. Each file in workbench/resources/gallery returns a
// title, a summary and its examples; dropping a new one in there adds it to the
// sidebar, which lists the pages in filename order.
Route::get('/{component?}', function (?string $component = null) {
    $pages = [];

    foreach (glob(__DIR__.'/../resources/gallery/*.php') ?: [] as $file) {
        $pages[basename($file, '.php')] = require $file;
    }

    if ($component === null) {
        return redirect(url((string) array_key_first($pages)));
    }

    abort_unless(isset($pages[$component]), 404);

    // Preview against the package's own theme rather than a duplicate of it, then
    // append a role the package does not ship. ocean.css belongs to the workbench,
    // not to Shape: it stands in for a consuming app's stylesheet, so the gallery
    // proves the open colour set the way a consumer meets it.
    //
    // The in-browser Tailwind build scans the DOM, so every @source directive is
    // stripped: the filesystem one has nothing to point at here, and the inline
    // safelists are redundant when the class names are already in the markup. A
    // real application does need them -- ocean.css says so at the top.
    // gallery.css is the workbench's too, but for the page around the previews
    // rather than for a role in them.
    $theme = implode("\n", [
        (string) file_get_contents(__DIR__.'/../../resources/css/shape.css'),
        (string) file_get_contents(__DIR__.'/../resources/css/ocean.css'),
        (string) file_get_contents(__DIR__.'/../resources/css/gallery.css'),
    ]);
    $theme = (string) preg_replace('/^@source\b[^;]*;\s*$/m', '', $theme);

    return view('gallery.page', [
        'pages' => $pages,
        'current' => $component,
        'page' => $pages[$component],
        'theme' => $theme,
    ]);
});
