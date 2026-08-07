<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Switch',
    'summary' => 'A checkbox that announces itself as a switch, with a thumb of CSS and nothing to publish.',
    'examples' => [
        [
            // `checked` is staged with a plain attribute here so both states can be
            // compared in one row. In an application it comes from the model.
            'title' => 'Off and on',
            'source' => implode("\n", [
                '<shape:switch label="Off" />',
                '<shape:switch label="On" checked />',
            ]),
        ],
        [
            // Pick a 2px inset and the rest follows: travel is the track's width
            // minus its height, and travel is also the thumb -- a thumb clears its
            // own width and stops. Shown checked so the thumb is at the far end,
            // which is where an overshoot would be visible.
            'title' => 'Density (size), tracks 28x16, 32x18, 36x20 and 44x24px',
            'source' => implode("\n", [
                '<shape:switch size="xs" label="Extra small" checked />',
                '<shape:switch size="sm" label="Small" checked />',
                '<shape:switch size="md" label="Medium" checked />',
                '<shape:switch size="lg" label="Large" checked />',
            ]),
        ],
        [
            // The track heights are the checkbox's box sizes, which is what lets
            // these two share a top and a bottom edge at every rung. Three selection
            // rules on one control, and a reader comparing them down a column should
            // find them the same height.
            'title' => 'The same height as the box beside it',
            'source' => implode("\n", [
                '<shape:switch size="xs" label="Extra small" checked />',
                '<shape:checkbox size="xs" label="Extra small" checked />',
                '<shape:switch size="lg" label="Large" checked />',
                '<shape:checkbox size="lg" label="Large" checked />',
            ]),
        ],
        [
            // A row rather than the field's column, control first: the label names
            // the thing beside it rather than the field above it, and `items-start`
            // wraps a long one under its own first line.
            'title' => 'Label and help text, on one side of the switch',
            'source' => implode("\n", [
                '<shape:switch label="Email me about releases" description="About once a month, and never anything else." name="notify" class="max-w-xs" />',
            ]),
        ],
        [
            // A switch is never one of a set, so `value` is not a discriminator here
            // the way it is on a box: this one answers to `notify`, not `notify-1`.
            // Standing alone it is the whole field, so it owes its own message.
            'title' => 'Invalid (said outright here; ordinarily the validator)',
            'source' => implode("\n", [
                '<shape:switch label="Enable two-factor authentication" name="notify" value="1" :invalid="true" />',
            ]),
        ],
        [
            // The fade is opacity rather than restated colours, so it does not have
            // to beat `checked:` in Tailwind's variant order to work.
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:switch label="Ordinary" />',
                '<shape:switch label="Disabled" disabled />',
                '<shape:switch label="Disabled and on" checked disabled />',
            ]),
        ],
        [
            // The off thumb is muted ink rather than a surface or a border colour,
            // which is what keeps it visible here as well as on white.
            'title' => 'Dark mode (surface swap, same markup)',
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:switch label="Off" />',
                '<shape:switch label="On" checked />',
                '<shape:switch label="Invalid" name="notify" :invalid="true" />',
                '<shape:switch label="Disabled" checked disabled />',
            ]),
        ],
    ],
];
