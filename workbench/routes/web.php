<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Defined here in plain PHP so the branded <shape:*> source strings are not
    // rewritten by the package's compile-time tag preprocessor. Blade::render()
    // in the view still expands them at runtime for the live preview.
    $examples = [
        [
            'title' => 'Button — default',
            'source' => '<shape:button>Save changes</shape:button>',
        ],
        [
            'title' => 'Button — with attributes',
            'source' => '<shape:button class="rounded-md bg-indigo-600 px-4 py-2 text-sm font-medium text-white hover:bg-indigo-500">Publish</shape:button>',
        ],
        [
            'title' => 'Button — long-form x-shape:: syntax',
            'source' => '<x-shape::button class="rounded-md border border-slate-300 px-4 py-2 text-sm font-medium">Cancel</x-shape::button>',
        ],
    ];

    return view('gallery', ['examples' => $examples]);
});
