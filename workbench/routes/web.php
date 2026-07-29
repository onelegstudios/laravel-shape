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
            // items-center on the row is the gallery's, not the component's: it is
            // what makes four different heights sit on one line to be compared.
            'title' => 'Button — density (size)',
            'source' => implode("\n", [
                '<shape:button size="xs" variant="solid" color="primary">Extra small</shape:button>',
                '<shape:button size="sm" variant="solid" color="primary">Small</shape:button>',
                '<shape:button size="md" variant="solid" color="primary">Medium</shape:button>',
                '<shape:button size="lg" variant="solid" color="primary">Large</shape:button>',
            ]),
        ],
        [
            // Emphasis down one row, density across the other: nothing about being
            // small makes a button quieter, and nothing about being loud makes it
            // bigger. A dense table row can still shout when it needs to.
            'title' => 'Button — the three props compose',
            'source' => implode("\n", [
                '<shape:button size="xs" variant="solid" color="danger">Delete</shape:button>',
                '<shape:button size="sm" variant="soft" color="danger">Delete</shape:button>',
                '<shape:button size="md" variant="ghost" color="danger">Delete</shape:button>',
                '<shape:button size="lg" variant="outline" color="danger">Delete</shape:button>',
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
        [
            'title' => 'Icon — density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:icon name="settings" size="xs" />',
                '<shape:icon name="settings" size="sm" />',
                '<shape:icon name="settings" size="md" />',
                '<shape:icon name="settings" size="lg" />',
            ]),
        ],
        [
            // No colour prop and no colour class: the icon is on currentColor, so
            // it comes out whatever the thing around it is. This is the row that
            // shows why inherit is the default rather than a named role.
            'title' => 'Icon — inherits its colour from what it sits in',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary"><shape:icon name="check" size="sm" />Save changes</shape:button>',
                '<shape:button variant="solid" color="danger" size="sm"><shape:icon name="trash-2" size="xs" />Delete</shape:button>',
                '<shape:button variant="outline"><shape:icon name="download" size="sm" />Export</shape:button>',
                '<shape:button variant="ghost" color="neutral" size="lg">Continue<shape:icon name="arrow-right" size="md" /></shape:button>',
            ]),
        ],
        [
            // The other half: an icon that carries meaning on its own names a role
            // and picks up the same on-tint token the quiet button variants use.
            'title' => 'Icon — a semantic role, for the icon that stands alone',
            'source' => implode("\n", [
                '<shape:icon name="circle-check" color="success" label="Passed" />',
                '<shape:icon name="triangle-alert" color="warning" label="Needs review" />',
                '<shape:icon name="circle-x" color="danger" label="Failed" />',
                '<shape:icon name="info" color="info" label="Note" />',
                '<shape:icon name="circle-check" color="ocean" label="Ocean" />',
            ]),
        ],
        [
            'title' => 'Icon — dark mode (role swap, same markup)',
            'surface' => 'dark bg-neutral-900',
            'source' => implode("\n", [
                '<shape:icon name="circle-check" color="success" label="Passed" />',
                '<shape:icon name="circle-x" color="danger" label="Failed" />',
                '<shape:button variant="soft" color="primary"><shape:icon name="check" size="sm" />Save</shape:button>',
            ]),
        ],
        [
            // `close` is not a Lucide icon. It is the semantic name Shape's own
            // components use, mapped to `x` in config/shape.php -- which is what
            // lets a consumer swap icon libraries without touching a view.
            'title' => 'Icon — a semantic alias resolved through config',
            'source' => implode("\n", [
                '<shape:icon name="close" label="Dismiss" />',
                '<shape:icon name="chevron-down" />',
                '<shape:icon name="spinner" class="animate-spin" color="primary" label="Loading" />',
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
