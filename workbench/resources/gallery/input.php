<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Input',
    'summary' => 'One shorthand for the ordinary field, and the parts of one for everything else.',
    'examples' => [
        [
            // The control fills what it is given, so the width belongs to the call
            // site. `max-w-3xs` here is the gallery constraining a preview, not the
            // component having an opinion -- and it lands on the box rather than on
            // the control, which is the one rule this component's shape costs.
            'title' => 'Density (size), the same four rungs as the button',
            'source' => implode("\n", [
                '<shape:input size="xs" placeholder="Extra small" class="max-w-3xs" />',
                '<shape:input size="sm" placeholder="Small" class="max-w-3xs" />',
                '<shape:input size="md" placeholder="Medium" class="max-w-3xs" />',
                '<shape:input size="lg" placeholder="Large" class="max-w-3xs" />',
            ]),
        ],
        [
            // The point of taking the button's own padding: a field and the button
            // that submits it sit level in one row, at every rung, without either
            // one being told about the other.
            'title' => 'Stands level with the button beside it',
            'source' => implode("\n", [
                '<shape:input size="sm" placeholder="Search orders" class="max-w-3xs" />',
                '<shape:button size="sm" variant="solid" color="primary">Search</shape:button>',
                '<shape:input size="md" placeholder="Search orders" class="max-w-3xs" />',
                '<shape:button size="md" variant="solid" color="primary">Search</shape:button>',
            ]),
        ],
        [
            // The mark is not sized anywhere in this row. The field resolved its
            // rung once and handed it over, which is the same bargain the button's
            // icon prop makes: the pair cannot drift apart.
            'title' => 'A mark in the field (icon, icon-trailing)',
            'source' => implode("\n", [
                '<shape:input icon="search" placeholder="Search" class="max-w-3xs" />',
                '<shape:input icon="at-sign" type="email" placeholder="you@example.com" class="max-w-3xs" />',
                '<shape:input size="sm" icon="search" icon-trailing="chevron-down" placeholder="Filter" class="max-w-3xs" />',
            ]),
        ],
        [
            // What a form is actually made of. The label, the help text and the
            // message are three components; naming them as props is the shorthand
            // that assembles all three, wires the label to the control, and points
            // aria-describedby at exactly the parts it rendered.
            'title' => 'The shorthand (label, description)',
            'source' => implode("\n", [
                '<shape:input label="Email" description="We never share it." name="email" class="max-w-3xs" />',
                '<shape:input label="Handle" description-trailing="Letters and numbers only." name="handle" class="max-w-3xs" />',
            ]),
        ],
        [
            // Ordinarily this comes from the validator: the input looks itself up
            // in the error bag by name, or by whatever `wire:model` is bound to.
            // The gallery has no failed request behind it, so these say so
            // explicitly -- which is the same escape hatch a field the validator
            // has not seen yet would use.
            'title' => 'Invalid (read from the validator, or said outright)',
            'source' => implode("\n", [
                '<shape:input name="email" value="not-an-address" :invalid="true" class="max-w-3xs" />',
                '<shape:field name="email">',
                '    <shape:label>Email</shape:label>',
                '    <shape:input value="not-an-address" :invalid="true" />',
                '    <shape:error>Enter a valid email address.</shape:error>',
                '</shape:field>',
            ]),
        ],
        [
            'title' => 'States the form puts it in',
            'source' => implode("\n", [
                '<shape:input placeholder="Ordinary" class="max-w-3xs" />',
                '<shape:input value="Cannot be edited" disabled class="max-w-3xs" />',
                '<shape:input value="Can be copied" readonly class="max-w-3xs" />',
            ]),
        ],
        [
            // The parts, used directly. Naming the field once is what lets three
            // components that cannot see each other agree: the label points at the
            // control, the description gets an id the control can name, and the
            // message knows which field it belongs to.
            'title' => 'Composed, for everything the shorthand cannot say',
            'source' => implode("\n", [
                '<shape:field name="user.email">',
                '    <shape:label>Email</shape:label>',
                '    <shape:description>We never share it.</shape:description>',
                '    <shape:input icon="at-sign" type="email" aria-describedby="user-email-description" />',
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
                '<shape:input label="Email" description="We never share it." name="email" class="max-w-3xs" />',
                '<shape:input icon="search" placeholder="Search" class="max-w-3xs" />',
                '<shape:input name="email" value="not-an-address" :invalid="true" class="max-w-3xs" />',
                '<shape:input value="Cannot be edited" disabled class="max-w-3xs" />',
            ]),
        ],
        [
            'title' => 'Defaults and long-form x-shape:: syntax',
            'source' => implode("\n", [
                '<shape:input placeholder="Defaults to a medium text input" class="max-w-3xs" />',
                '<x-shape::input placeholder="The same component" class="max-w-3xs" />',
            ]),
        ],
    ],
];
