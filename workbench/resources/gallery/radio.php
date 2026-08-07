<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Radio',
    'summary' => 'The checkbox\'s row and box, made round, with a dot that is CSS rather than artwork.',
    'examples' => [
        [
            // Round rather than square is the only thing telling a reader this set is
            // one-of-many rather than any-of-many, which is why the shape is not a
            // prop. The dot is two tokens of CSS: Heroicons ships no `circle` and no
            // `dot`, so an alias would point at a glyph half the libraries lack.
            'title' => 'Two states, and a dot that needs no icon published',
            'source' => implode("\n", [
                '<shape:radio name="gallery-plan" value="off" label="Unselected" />',
                '<shape:radio name="gallery-plan" value="on" label="Selected" checked />',
            ]),
        ],
        [
            // The checkbox's boxes exactly, because a radio and a checkbox in one form
            // are the same control with two selection rules -- a reader comparing them
            // down a column should find them the same size. The dots are even-numbered
            // so a centred one never lands on a half pixel.
            'title' => 'Density (size), the checkbox\'s boxes with 6, 6, 8 and 10px dots',
            'source' => implode("\n", [
                '<shape:radio size="xs" name="gallery-xs" value="a" label="Extra small" checked />',
                '<shape:radio size="sm" name="gallery-sm" value="a" label="Small" checked />',
                '<shape:radio size="md" name="gallery-md" value="a" label="Medium" checked />',
                '<shape:radio size="lg" name="gallery-lg" value="a" label="Large" checked />',
            ]),
        ],
        [
            // What this control is actually for, and it needs no group component: the
            // field carries the name, each option derives its own id from its value,
            // and the field prints the one message.
            //
            // `legend` rather than `label` is what makes the field a <fieldset>, and a
            // group wants one. Three radios wired by `name` are visually a set and,
            // without it, three unrelated controls to anything reading the page --
            // plus a name that pointed at an id no option carries.
            'title' => 'A group, in a field',
            'source' => implode("\n", [
                '<shape:field name="plan" legend="Plan" description="Change it whenever.">',
                '    <shape:radio value="free" label="Free" description="One project, no card." />',
                '    <shape:radio value="pro" label="Pro" description="Best for a small team." checked />',
                '    <shape:radio value="team" label="Team" description="Seats and shared billing." />',
                '</shape:field>',
            ]),
        ],
        [
            // A radio never prints a message of its own, even standing alone. One
            // option of a set the user cannot choose from is a bug in the markup
            // rather than a state to style, so the sentence always belongs to a group.
            'title' => 'Invalid (the styling is the control\'s, the sentence is the group\'s)',
            'source' => implode("\n", [
                '<shape:field name="plan" legend="Plan">',
                '    <shape:radio value="free" label="Free" :invalid="true" />',
                '    <shape:radio value="pro" label="Pro" :invalid="true" />',
                '    <shape:error>Choose a plan to continue.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:radio name="gallery-states" value="a" label="Ordinary" />',
                '<shape:radio name="gallery-states" value="b" label="Disabled" disabled />',
                '<shape:radio name="gallery-states-2" value="c" label="Disabled and selected" checked disabled />',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:radio name="gallery-dark" value="a" label="Unselected" />',
                '<shape:radio name="gallery-dark" value="b" label="Selected" checked />',
                '<shape:radio name="plan" value="free" label="Invalid" :invalid="true" />',
                '<shape:radio name="gallery-dark-2" value="c" label="Disabled" checked disabled />',
            ]),
        ],
    ],
];
