<?php

use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    // Defined here in plain PHP so the branded <shape:*> source strings are not
    // rewritten by the package's compile-time tag preprocessor. Blade::render()
    // in the view still expands them at runtime for the live preview.
    $examples = [
        [
            'title' => 'Button — emphasis ladder (variant)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save changes</shape:button>',
                '<shape:button variant="soft" color="primary">Save changes</shape:button>',
                '<shape:button variant="ghost" color="primary">Save changes</shape:button>',
                '<shape:button variant="outline" color="primary">Save changes</shape:button>',
            ]),
        ],
        [
            'title' => 'Button — semantic roles (color)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Primary</shape:button>',
                '<shape:button variant="solid" color="success">Success</shape:button>',
                '<shape:button variant="solid" color="warning">Warning</shape:button>',
                '<shape:button variant="solid" color="danger">Danger</shape:button>',
                '<shape:button variant="solid" color="info">Info</shape:button>',
                '<shape:button variant="solid" color="neutral">Neutral</shape:button>',
            ]),
        ],
        [
            'title' => 'Button — the two props compose',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="danger">Delete</shape:button>',
                '<shape:button variant="soft" color="danger">Delete</shape:button>',
                '<shape:button variant="ghost" color="danger">Delete</shape:button>',
                '<shape:button variant="outline" color="danger">Delete</shape:button>',
            ]),
        ],
        [
            'title' => 'Button — dark mode (role swap, same markup)',
            'surface' => 'dark bg-neutral-900',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save</shape:button>',
                '<shape:button variant="soft" color="primary">Save</shape:button>',
                '<shape:button variant="ghost" color="primary">Save</shape:button>',
                '<shape:button variant="outline" color="danger">Delete</shape:button>',
            ]),
        ],
        [
            'title' => 'Button — a realistic pairing (the default is the quiet one)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save changes</shape:button>',
                '<shape:button>Cancel</shape:button>',
            ]),
        ],
        [
            // `ocean` is defined in workbench/resources/css/ocean.css, not in the
            // package. Nothing in Shape knows the name -- the component builds the
            // class from whatever role it is handed.
            'title' => 'Button — a role the package does not ship (ocean)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="ocean">Book a demo</shape:button>',
                '<shape:button variant="soft" color="ocean">Book a demo</shape:button>',
                '<shape:button variant="outline" color="ocean">Book a demo</shape:button>',
            ]),
        ],
        [
            'title' => 'Button — defaults and long-form x-shape:: syntax',
            'source' => implode("\n", [
                '<shape:button>Defaults to outline neutral</shape:button>',
                '<x-shape::button variant="soft" color="neutral">Cancel</x-shape::button>',
            ]),
        ],
    ];

    // Preview against the package's own theme rather than a duplicate of it, then
    // append a role the package does not ship. ocean.css belongs to the workbench,
    // not to Shape: it stands in for a consuming app's stylesheet, so the gallery
    // proves the open colour set the way a consumer meets it.
    //
    // The in-browser Tailwind build scans the DOM, so every @source directive is
    // stripped: the filesystem one has nothing to point at here, and the inline
    // safelists are redundant when the class names are already in the markup. A
    // real application does need them -- ocean.css says so at the top.
    $theme = implode("\n", [
        (string) file_get_contents(__DIR__.'/../../resources/css/shape.css'),
        (string) file_get_contents(__DIR__.'/../resources/css/ocean.css'),
    ]);
    $theme = (string) preg_replace('/^@source\b[^;]*;\s*$/m', '', $theme);

    return view('gallery', ['examples' => $examples, 'theme' => $theme]);
});
