<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Button',
    'summary' => 'Emphasis, semantic role and density as three props that compose.',
    'examples' => [
        [
            'title' => 'Emphasis ladder (variant)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save changes</shape:button>',
                '<shape:button variant="soft" color="primary">Save changes</shape:button>',
                '<shape:button variant="ghost" color="primary">Save changes</shape:button>',
                '<shape:button variant="outline" color="primary">Save changes</shape:button>',
            ]),
        ],
        [
            'title' => 'Semantic roles (color)',
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
            'title' => 'Density (size)',
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
            'title' => 'The three props compose',
            'source' => implode("\n", [
                '<shape:button size="xs" variant="solid" color="danger">Delete</shape:button>',
                '<shape:button size="sm" variant="soft" color="danger">Delete</shape:button>',
                '<shape:button size="md" variant="ghost" color="danger">Delete</shape:button>',
                '<shape:button size="lg" variant="outline" color="danger">Delete</shape:button>',
            ]),
        ],
        [
            'title' => 'Dark mode (role swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its
            // own surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save</shape:button>',
                '<shape:button variant="soft" color="primary">Save</shape:button>',
                '<shape:button variant="ghost" color="primary">Save</shape:button>',
                '<shape:button variant="outline" color="danger">Delete</shape:button>',
            ]),
        ],
        [
            'title' => 'A realistic pairing (the default is the quiet one)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary">Save changes</shape:button>',
                '<shape:button>Cancel</shape:button>',
            ]),
        ],
        [
            // `ocean` is defined in workbench/resources/css/ocean.css, not in the
            // package. Nothing in Shape knows the name -- the component builds the
            // class from whatever role it is handed.
            'title' => 'A role the package does not ship (ocean)',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="ocean">Book a demo</shape:button>',
                '<shape:button variant="soft" color="ocean">Book a demo</shape:button>',
                '<shape:button variant="outline" color="ocean">Book a demo</shape:button>',
            ]),
        ],
        [
            'title' => 'Defaults and long-form x-shape:: syntax',
            'source' => implode("\n", [
                '<shape:button>Defaults to outline neutral</shape:button>',
                '<x-shape::button variant="soft" color="neutral">Cancel</x-shape::button>',
            ]),
        ],
    ],
];
