<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

return [
    'title' => 'Heading',
    'summary' => 'A title, the description under it and the buttons beside it — with where it sits in the outline kept apart from how large it reads.',
    'examples' => [
        [
            // The row the component exists for. Both pairs render the same two
            // elements and read as opposites, which is what you cannot do when one
            // prop controls both.
            'title' => 'Level is the outline, size is the type',
            'source' => implode("\n", [
                '<shape:heading level="1" size="sm">An h1 that reads quietly</shape:heading>',
                '',
                '<shape:heading level="6" size="lg">An h6 set larger than it</shape:heading>',
            ]),
        ],
        [
            'title' => 'Density (size)',
            'source' => implode("\n", [
                '<shape:heading size="xs">Extra small</shape:heading>',
                '<shape:heading size="sm">Small</shape:heading>',
                '<shape:heading size="md">Medium</shape:heading>',
                '<shape:heading size="lg">Large</shape:heading>',
            ]),
        ],
        [
            // A title alone renders a bare h2 -- no wrapper, no landmark. Adding a
            // description is what brings the <header> and the stack with it.
            'title' => 'A title on its own, and one with something to say',
            'source' => implode("\n", [
                '<shape:heading class="w-full">Team members</shape:heading>',
                '',
                '<shape:heading class="w-full" description="Everyone with access to this workspace.">Team members</shape:heading>',
            ]),
        ],
        [
            // The stack becomes a row. The title has min-w-0 and the actions have
            // shrink-0, so a long title wraps rather than pushing the buttons off
            // the end -- narrow the window on this one to watch it happen.
            'title' => 'Actions turn the stack into a row',
            'source' => implode("\n", [
                '<shape:heading class="w-full" level="1" size="lg" description="Everyone with access to this workspace.">',
                '    <x-slot:actions>',
                '        <shape:button size="sm">Export</shape:button>',
                '        <shape:button size="sm" variant="solid" color="primary">Invite</shape:button>',
                '    </x-slot:actions>',
                '',
                '    Team members',
                '</shape:heading>',
            ]),
        ],
        [
            // Weight and colour do the work: the description is a full step of ink
            // quieter than the title and only one rung smaller, so it stays
            // comfortable to read instead of becoming fine print.
            'title' => 'Told apart on colour rather than on size alone',
            'source' => implode("\n", [
                '<shape:heading class="w-full" size="lg" description="A description is muted, not shrunk. It steps down one rung from the title and takes the muted ink, which is what makes it read as belonging to the title rather than as a second sentence of it.">',
                '    Billing',
                '</shape:heading>',
            ]),
        ],
        [
            'title' => 'Inside an article, where a header is not a landmark',
            'source' => implode("\n", [
                '<article class="w-full">',
                '    <shape:heading level="2" description="Published 8 August 2026">',
                '        What we changed this month',
                '    </shape:heading>',
                '</article>',
            ]),
        ],
        [
            'title' => 'Dark mode (token swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its own
            // surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:heading class="w-full" size="lg" description="Everyone with access to this workspace.">',
                '    <x-slot:actions>',
                '        <shape:button size="sm" variant="solid" color="primary">Invite</shape:button>',
                '    </x-slot:actions>',
                '',
                '    Team members',
                '</shape:heading>',
            ]),
        ],
    ],
];
