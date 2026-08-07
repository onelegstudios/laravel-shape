<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Color',
    'summary' => 'The native picker as a swatch, at the height of the field beside it.',
    'examples' => [
        [
            // No width on any of these, and that is the component rather than the
            // gallery: a swatch is square at the rung's height. What it holds has
            // no length to fill.
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:color size="xs" value="#4f46e5" />',
                '<shape:color size="sm" value="#059669" />',
                '<shape:color size="md" value="#e11d48" />',
                '<shape:color size="lg" value="#0284c7" />',
            ]),
        ],
        [
            // The same bargain the slider takes: the four sizes are the input's own
            // outer heights, so a swatch and a field of the same rung sit level.
            'title' => 'Stands level with the field beside it',
            'source' => implode("\n", [
                '<shape:color size="sm" value="#4f46e5" />',
                '<shape:input size="sm" value="#4f46e5" class="max-w-3xs" />',
                '<shape:color size="md" value="#4f46e5" />',
                '<shape:input size="md" value="#4f46e5" class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:color label="Brand" description="Used for buttons and links." name="brand" value="#4f46e5" />',
                '<shape:color label="Accent" description-trailing="Sparingly." name="accent" value="#059669" />',
            ]),
        ],
        [
            // Reading the value back into text takes JavaScript, and the library
            // ships none -- so the pair is a call site's to assemble, and Livewire
            // or Alpine is what keeps the two in step.
            'title' => 'Showing the value: a field bound to the same model',
            'source' => implode("\n", [
                '<shape:field name="brand">',
                '    <shape:label>Brand colour</shape:label>',
                '    <shape:color value="#4f46e5" />',
                '    <shape:input value="#4f46e5" class="max-w-3xs" />',
                '</shape:field>',
            ]),
        ],
        [
            // This control has a border, so it says it there -- the input's own
            // pair, unchanged.
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:color name="brand" value="#ffffff" :invalid="true" />',
                '<shape:field name="brand">',
                '    <shape:label>Brand</shape:label>',
                '    <shape:color value="#ffffff" :invalid="true" />',
                '    <shape:error>Pick something with more contrast.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:color value="#4f46e5" />',
                '<shape:color value="#4f46e5" disabled />',
            ]),
        ],
        [
            // A row of them is the thing this shape is for. Stretched to the width
            // of a field, the same six would be six bands of saturated colour.
            'title' => 'A palette, which is where a square earns itself',
            'source' => implode("\n", [
                '<shape:color size="sm" value="#4f46e5" />',
                '<shape:color size="sm" value="#059669" />',
                '<shape:color size="sm" value="#d97706" />',
                '<shape:color size="sm" value="#e11d48" />',
                '<shape:color size="sm" value="#0284c7" />',
                '<shape:color size="sm" value="#475569" />',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its
            // own surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:color label="Brand" description="Used for buttons and links." name="brand" value="#4f46e5" />',
                '<shape:color name="brand" value="#ffffff" :invalid="true" />',
                '<shape:color value="#4f46e5" disabled />',
            ]),
        ],
        [
            'title' => 'Defaults and long-form x-shape:: syntax',
            'source' => implode("\n", [
                '<shape:color value="#4f46e5" />',
                '<x-shape::color value="#4f46e5" />',
            ]),
        ],
    ],
];
