<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Textarea',
    'summary' => 'The input\'s box around a control that stretches instead of sitting on a line.',
    'examples' => [
        [
            // Two things change from the input and no more: the box does not centre
            // a line it does not have, and each rung takes the next step up in
            // leading -- 14px on a 20px line is comfortable for one line of a form
            // and tight for five lines of prose.
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:textarea size="xs" placeholder="Extra small" class="max-w-3xs" />',
                '<shape:textarea size="sm" placeholder="Small" class="max-w-3xs" />',
                '<shape:textarea size="md" placeholder="Medium" class="max-w-3xs" />',
                '<shape:textarea size="lg" placeholder="Large" class="max-w-3xs" />',
            ]),
        ],
        [
            // `rows` is a merged default rather than a prop: it is a plain HTML
            // attribute that already reaches the control, so a prop would exist only
            // to set a default -- and three rather than the browser's two, which is
            // a box so short it reads as broken.
            'title' => 'Height (rows, defaulting to three)',
            'source' => implode("\n", [
                '<shape:textarea placeholder="Three lines by default" class="max-w-3xs" />',
                '<shape:textarea rows="6" placeholder="Six" class="max-w-3xs" />',
            ]),
        ],
        [
            // Opt-in rather than the default: `field-sizing-content` lands in
            // Chromium and not everywhere else, and a packaged control that reflowed
            // under the cursor in one engine and sat still in another would behave
            // differently per browser for no reason the call site asked for.
            'title' => 'Growing with its content (autosize)',
            'source' => implode("\n", [
                '<shape:textarea autosize placeholder="Type and watch it grow" class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:textarea label="Bio" description="A sentence or two." name="bio" class="max-w-3xs" />',
                '<shape:textarea label="Notes" description-trailing="Markdown works here." name="notes" class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:textarea name="bio" :invalid="true" class="max-w-3xs">Too short</shape:textarea>',
                '<shape:field name="bio">',
                '    <shape:label>Bio</shape:label>',
                '    <shape:textarea :invalid="true">Too short</shape:textarea>',
                '    <shape:error>Write at least twenty characters.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:textarea placeholder="Ordinary" class="max-w-3xs" />',
                '<shape:textarea disabled class="max-w-3xs">Cannot be edited</shape:textarea>',
                '<shape:textarea readonly class="max-w-3xs">Can be copied</shape:textarea>',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:textarea label="Bio" description="A sentence or two." name="bio" class="max-w-3xs" />',
                '<shape:textarea name="bio" :invalid="true" class="max-w-3xs">Too short</shape:textarea>',
                '<shape:textarea disabled class="max-w-3xs">Cannot be edited</shape:textarea>',
            ]),
        ],
    ],
];
