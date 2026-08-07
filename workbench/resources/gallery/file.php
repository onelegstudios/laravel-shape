<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'File',
    'summary' => 'The input\'s box around the one control that arrives with a button inside it.',
    'examples' => [
        [
            // The button gives up its own border, background and padding, so the
            // field is one frame rather than two -- and its height becomes its own
            // line box, which is what keeps this control level with a text field of
            // the same rung.
            'title' => 'One frame, not two',
            'source' => implode("\n", [
                '<shape:file class="max-w-xs" />',
            ]),
        ],
        [
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:file size="xs" class="max-w-xs" />',
                '<shape:file size="sm" class="max-w-xs" />',
                '<shape:file size="md" class="max-w-xs" />',
                '<shape:file size="lg" class="max-w-xs" />',
            ]),
        ],
        [
            // The button is held off the filename by the same gap the input's rung
            // holds a mark off its value by -- spelled as a margin, because the
            // button is a pseudo-element rather than a flex sibling and the box's
            // `gap` has nothing to apply to.
            'title' => 'Stands level with the input beside it',
            'source' => implode("\n", [
                '<shape:input size="md" placeholder="Title" class="max-w-3xs" />',
                '<shape:file size="md" class="max-w-xs" />',
            ]),
        ],
        [
            // Leading only: the far end of this box belongs to the filename, which
            // has no fixed length and every reason to be the thing that truncates.
            'title' => 'A leading mark (icon)',
            'source' => implode("\n", [
                '<shape:file icon="download" class="max-w-xs" />',
            ]),
        ],
        [
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:file label="Avatar" description="PNG or JPG, up to 2 MB." name="avatar" accept="image/*" class="max-w-xs" />',
            ]),
        ],
        [
            'title' => 'More than one file',
            'source' => implode("\n", [
                '<shape:file multiple class="max-w-xs" />',
            ]),
        ],
        [
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:file name="avatar" :invalid="true" class="max-w-xs" />',
                '<shape:field name="avatar">',
                '    <shape:label>Avatar</shape:label>',
                '    <shape:file :invalid="true" />',
                '    <shape:error>That file is larger than 2 MB.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:file class="max-w-xs" />',
                '<shape:file disabled class="max-w-xs" />',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:file label="Avatar" description="PNG or JPG, up to 2 MB." name="avatar" class="max-w-xs" />',
                '<shape:file icon="download" class="max-w-xs" />',
                '<shape:file name="avatar" :invalid="true" class="max-w-xs" />',
                '<shape:file disabled class="max-w-xs" />',
            ]),
        ],
    ],
];
