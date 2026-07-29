<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Icon',
    'summary' => 'The same density rungs as the button, colour by inheritance, and names resolved through config.',
    'examples' => [
        [
            'title' => 'Density (size), the same four rungs as the button',
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
            //
            // Sizes pair rung for rung, which is what docs/icons.md promises the
            // shared scale buys: an icon in a `sm` button is `sm`. The two md
            // pairs say nothing at all, because md is the default on both.
            'title' => 'Inherits its colour from what it sits in',
            'source' => implode("\n", [
                '<shape:button variant="solid" color="primary"><shape:icon name="check" />Save changes</shape:button>',
                '<shape:button variant="solid" color="danger" size="sm"><shape:icon name="trash-2" size="sm" />Delete</shape:button>',
                '<shape:button variant="outline"><shape:icon name="download" />Export</shape:button>',
                '<shape:button variant="ghost" color="neutral" size="lg">Continue<shape:icon name="arrow-right" size="lg" /></shape:button>',
            ]),
        ],
        [
            // The other half: an icon that carries meaning on its own names a role
            // and picks up the same on-tint token the quiet button variants use.
            'title' => 'A semantic role, for the icon that stands alone',
            'source' => implode("\n", [
                '<shape:icon name="circle-check" color="success" label="Passed" />',
                '<shape:icon name="triangle-alert" color="warning" label="Needs review" />',
                '<shape:icon name="circle-x" color="danger" label="Failed" />',
                '<shape:icon name="info" color="info" label="Note" />',
                '<shape:icon name="circle-check" color="ocean" label="Ocean" />',
            ]),
        ],
        [
            'title' => 'Dark mode (role swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its
            // own surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:icon name="circle-check" color="success" label="Passed" />',
                '<shape:icon name="circle-x" color="danger" label="Failed" />',
                '<shape:button variant="soft" color="primary"><shape:icon name="check" />Save</shape:button>',
            ]),
        ],
        [
            // `close` is not a Lucide icon. It is the semantic name Shape's own
            // components use, mapped to `x` in config/shape.php -- which is what
            // lets a consumer swap icon libraries without touching a view.
            'title' => 'A semantic alias resolved through config',
            'source' => implode("\n", [
                '<shape:icon name="close" label="Dismiss" />',
                '<shape:icon name="chevron-down" />',
                '<shape:icon name="spinner" class="animate-spin" color="primary" label="Loading" />',
            ]),
        ],
    ],
];
