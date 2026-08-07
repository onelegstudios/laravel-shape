<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Select',
    'summary' => 'The input\'s box around a native select, with a chevron from your own icon set.',
    'examples' => [
        [
            // The one row worth clicking rather than reading. The chevron is a real
            // published icon in the same cell as the control rather than a flex
            // sibling, which is what makes the whole box open the select -- a mark
            // in its own column would leave the last twenty pixels dead, which is
            // exactly where everybody clicks.
            'title' => 'Click anywhere, chevron included',
            'source' => implode("\n", [
                '<shape:select class="max-w-3xs">',
                '    <option>Free</option>',
                '    <option>Pro</option>',
                '    <option>Team</option>',
                '</shape:select>',
            ]),
        ],
        [
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:select size="xs" class="max-w-3xs"><option>Extra small</option></shape:select>',
                '<shape:select size="sm" class="max-w-3xs"><option>Small</option></shape:select>',
                '<shape:select size="md" class="max-w-3xs"><option>Medium</option></shape:select>',
                '<shape:select size="lg" class="max-w-3xs"><option>Large</option></shape:select>',
            ]),
        ],
        [
            // The room left for the chevron is the mark's own width plus the gap the
            // input's rung holds a mark off its value by, so a long option stops
            // exactly where a long value in the field above it stops.
            // `outline` rather than `solid` on the button, because that is what the
            // claim is about: a border sits outside the box, so an outline button
            // stands 38px at `md` where a solid one stands 36.
            'title' => 'Stands level with the input and the outline button beside it',
            'source' => implode("\n", [
                '<shape:input size="md" placeholder="Search orders" class="max-w-3xs" />',
                '<shape:select size="md" class="max-w-3xs"><option>Any status</option></shape:select>',
                '<shape:button size="md" variant="outline">Search</shape:button>',
            ]),
        ],
        [
            // A leading mark is the call site's own and takes `icon-set`; the
            // chevron is Shape's and always resolves through `default`, the way the
            // button's spinner does.
            'title' => 'A leading mark (icon)',
            'source' => implode("\n", [
                '<shape:select icon="settings" class="max-w-3xs"><option>Any status</option></shape:select>',
                '<shape:select size="sm" icon="search" class="max-w-3xs"><option>All results</option></shape:select>',
            ]),
        ],
        [
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:select label="Plan" description="Change it whenever." name="plan" class="max-w-3xs">',
                '    <option>Free</option>',
                '    <option>Pro</option>',
                '</shape:select>',
            ]),
        ],
        [
            // A `multiple` select is a list box rather than a dropdown: nothing
            // opens, so there is no chevron, no room left for one, and the operating
            // system's own rendering is left alone.
            'title' => 'A list box (multiple)',
            'source' => implode("\n", [
                '<shape:select multiple size="4" class="max-w-3xs">',
                '    <option>Free</option>',
                '    <option>Pro</option>',
                '    <option>Team</option>',
                '    <option>Enterprise</option>',
                '</shape:select>',
            ]),
        ],
        [
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:select name="plan" :invalid="true" class="max-w-3xs"><option>Pick one</option></shape:select>',
                '<shape:field name="plan">',
                '    <shape:label>Plan</shape:label>',
                '    <shape:select :invalid="true"><option>Pick one</option></shape:select>',
                '    <shape:error>Choose a plan to continue.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:select class="max-w-3xs"><option>Ordinary</option></shape:select>',
                '<shape:select disabled class="max-w-3xs"><option>Cannot be changed</option></shape:select>',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its
            // own surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:select label="Plan" description="Change it whenever." name="plan" class="max-w-3xs"><option>Pro</option></shape:select>',
                '<shape:select icon="settings" class="max-w-3xs"><option>Any status</option></shape:select>',
                '<shape:select name="plan" :invalid="true" class="max-w-3xs"><option>Pick one</option></shape:select>',
                '<shape:select disabled class="max-w-3xs"><option>Cannot be changed</option></shape:select>',
            ]),
        ],
    ],
];
