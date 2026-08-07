<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Range',
    'summary' => 'A slider drawn from the same tokens as everything else, on the same four rungs.',
    'examples' => [
        [
            // The track fills what it is given, so the width belongs to the call
            // site -- and here it lands on the control itself, because a slider is
            // its own box. There is no wrapper to be talking about instead.
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:range size="xs" value="20" class="max-w-3xs" />',
                '<shape:range size="sm" value="40" class="max-w-3xs" />',
                '<shape:range size="md" value="60" class="max-w-3xs" />',
                '<shape:range size="lg" value="80" class="max-w-3xs" />',
            ]),
        ],
        [
            // What the rung heights buy, and the only reason to state them: the
            // slider takes the input's own 26, 34, 38 and 46px, so the two stand
            // level in a row without either being told about the other.
            'title' => 'Stands level with the field beside it',
            'source' => implode("\n", [
                '<shape:input size="sm" placeholder="Preset" class="max-w-3xs" />',
                '<shape:range size="sm" value="40" class="max-w-3xs" />',
                '<shape:input size="md" placeholder="Preset" class="max-w-3xs" />',
                '<shape:range size="md" value="60" class="max-w-3xs" />',
            ]),
        ],
        [
            // The thumb is the switch's, at every rung. Two controls you drag or
            // flip, carrying the same mark.
            'title' => 'The switch\'s thumb, so the two read as one set',
            'source' => implode("\n", [
                '<shape:range size="md" value="50" class="max-w-3xs" />',
                '<shape:switch size="md" checked />',
                '<shape:range size="lg" value="50" class="max-w-3xs" />',
                '<shape:switch size="lg" checked />',
            ]),
        ],
        [
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:range label="Volume" description="Applies to previews only." name="volume" max="100" value="70" class="max-w-3xs" />',
                '<shape:range label="Quality" description-trailing="Higher costs more." name="quality" min="1" max="5" value="3" class="max-w-3xs" />',
            ]),
        ],
        [
            // The range attributes are the control's own and pass straight through.
            // Nothing here is a Shape prop.
            'title' => 'The range itself (min, max, step, list)',
            'source' => implode("\n", [
                '<shape:range min="0" max="11" step="1" value="11" class="max-w-3xs" />',
                '<shape:range min="0" max="1" step="0.05" value="0.35" class="max-w-3xs" />',
            ]),
        ],
        [
            // No border to say it on, so the track carries it. Ordinarily this
            // comes from the validator; the gallery has no failed request behind
            // it, so these say so explicitly.
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:range name="volume" value="0" :invalid="true" class="max-w-3xs" />',
                '<shape:field name="volume">',
                '    <shape:label>Volume</shape:label>',
                '    <shape:range value="0" :invalid="true" />',
                '    <shape:error>Pick a level above zero.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            // One class dims the track and the thumb together: opacity on the
            // element carries its pseudo-elements.
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:range value="60" class="max-w-3xs" />',
                '<shape:range value="60" disabled class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'Composed, for everything the shorthand cannot say',
            'source' => implode("\n", [
                '<shape:field name="settings.volume">',
                '    <shape:label>Volume</shape:label>',
                '    <shape:description>Applies to previews only.</shape:description>',
                '    <shape:range max="100" value="70" aria-describedby="settings-volume-description" />',
                '    <shape:error />',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its
            // own surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:range label="Volume" description="Applies to previews only." name="volume" value="70" class="max-w-3xs" />',
                '<shape:range name="volume" value="0" :invalid="true" class="max-w-3xs" />',
                '<shape:range value="60" disabled class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'Defaults and long-form x-shape:: syntax',
            'source' => implode("\n", [
                '<shape:range value="50" class="max-w-3xs" />',
                '<x-shape::range value="50" class="max-w-3xs" />',
            ]),
        ],
    ],
];
