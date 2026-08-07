<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Checkbox',
    'summary' => 'A box you draw yourself, with its tick and its bar from your own icon set.',
    'examples' => [
        [
            // `checked` and `indeterminate` are staged with plain attributes here so
            // the three states can be compared in one row. In an application they
            // come from the model, and `indeterminate` is set from JavaScript --
            // there is no HTML attribute for it, which is why the preview writes the
            // property in a one-line script.
            'title' => 'The three states',
            'source' => implode("\n", [
                '<shape:checkbox label="Unchecked" />',
                '<shape:checkbox label="Checked" checked />',
                '<shape:checkbox label="Indeterminate" id="gallery-mixed" />',
                '<script>document.getElementById("gallery-mixed").indeterminate = true</script>',
            ]),
        ],
        [
            // The boxes pair with an icon rung at a constant inset and sit inside the
            // label's own line box, which is what aligns each row with no margin to
            // tune. `xs` does not get a 14px box -- the floor is 16, because 14 with
            // a 12 inside it is a target nobody can hit.
            'title' => 'Density (size), boxes 16, 18, 20 and 24px',
            'source' => implode("\n", [
                '<shape:checkbox size="xs" label="Extra small" checked />',
                '<shape:checkbox size="sm" label="Small" checked />',
                '<shape:checkbox size="md" label="Medium" checked />',
                '<shape:checkbox size="lg" label="Large" checked />',
            ]),
        ],
        [
            // A row rather than the field's column: the label names the option beside
            // it rather than the field above it, and `items-start` is what wraps a
            // long one under its own first line instead of under the box.
            'title' => 'Label and help text, on one side of the box',
            'source' => implode("\n", [
                '<shape:checkbox label="Email me about releases" description="About once a month, and never anything else." name="notify" value="1" class="max-w-xs" />',
            ]),
        ],
        [
            // Three boxes bound to one field. Each derives its own id from its value,
            // so each label clicks through to its own box rather than all three to
            // the first -- and the field prints one message rather than three.
            //
            // `legend` rather than `label` is what makes the field a <fieldset>, which
            // is what makes the set announce as a group. The group's help text is named
            // on the fieldset; the message is not, because every box already carries it.
            'title' => 'A group, in a field',
            'source' => implode("\n", [
                '<shape:field name="tags" legend="Tags" description="Pick any that apply.">',
                '    <shape:checkbox value="php" label="PHP" description="The language." />',
                '    <shape:checkbox value="laravel" label="Laravel" />',
                '    <shape:checkbox value="livewire" label="Livewire" />',
                '</shape:field>',
            ]),
        ],
        [
            // Standing alone it is the whole field, so it owes its own message: a
            // consent box that fails validation silently is the bug that covers.
            'title' => 'Invalid (said outright here; ordinarily the validator)',
            'source' => implode("\n", [
                '<shape:checkbox label="I accept the terms" name="terms" value="1" :invalid="true" />',
                '<shape:field name="tags" legend="Tags">',
                '    <shape:checkbox value="php" label="PHP" :invalid="true" />',
                '    <shape:error>Pick at least one tag.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            // The fade is opacity rather than restated colours, so it does not have
            // to beat `checked:` in Tailwind's variant order to work.
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:checkbox label="Ordinary" />',
                '<shape:checkbox label="Disabled" disabled />',
                '<shape:checkbox label="Disabled and checked" checked disabled />',
            ]),
        ],
        [
            'title' => 'Dark mode (surface swap, same markup)',
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:checkbox label="Unchecked" />',
                '<shape:checkbox label="Checked" checked />',
                '<shape:checkbox label="Invalid" name="terms" value="1" :invalid="true" />',
                '<shape:checkbox label="Disabled" checked disabled />',
            ]),
        ],
    ],
];
