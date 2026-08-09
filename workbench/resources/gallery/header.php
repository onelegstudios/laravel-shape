<?php

// Plain PHP, not Blade: the package's compile-time tag preprocessor rewrites
// <shape:*> in every template it compiles, so the source strings shown beside
// each preview would not survive being written in a view. Blade::render() in
// the gallery page still expands them at runtime for the live preview.

// Every bar below is `w-full`, so it fills the stage rather than sitting in the
// middle of it. The stage keeps its own padding, which is why the chrome stops
// short of the panel edge here and would not on a real page.

return [
    'title' => 'Header',
    'summary' => 'The bar across the top of a page, its brand, its nav, and the two page-surface tokens it draws itself with.',
    'examples' => [
        [
            // `sticky` is here for the source rather than for the preview: the
            // stage does not scroll, so a pinned bar behaves like an unpinned one
            // in it. What it does on a real page is sit at `z-40` against the top
            // of the window, which leaves room above for an application's dialogs.
            'title' => 'The whole thing',
            'source' => implode("\n", [
                '<shape:header sticky>',
                '    <shape:header.brand href="/">Acme</shape:header.brand>',
                '',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '        <shape:header.item href="/pricing">Pricing</shape:header.item>',
                '    </shape:header.nav>',
                '',
                '    <shape:button size="sm" variant="solid" color="primary">Sign in</shape:button>',
                '</shape:header>',
            ]),
        ],
        [
            // Three rungs of the same bar. What changes is the height, the room the
            // contents keep off the edge, and the gap holding the brand off the nav
            // -- and the items follow without being told, which is the `@aware` the
            // whole family stays off Blaze for.
            'title' => 'Density (size), and the items that follow it',
            'source' => implode("\n", [
                '<shape:header size="xs">',
                '    <shape:header.brand>Acme</shape:header.brand>',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '    </shape:header.nav>',
                '</shape:header>',
                '',
                '<shape:header size="md">',
                '    <shape:header.brand>Acme</shape:header.brand>',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '    </shape:header.nav>',
                '</shape:header>',
                '',
                '<shape:header size="lg">',
                '    <shape:header.brand>Acme</shape:header.brand>',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '    </shape:header.nav>',
                '</shape:header>',
            ]),
        ],
        [
            // The row that shows why there are two elements. The hairline runs the
            // full width of the stage in both, and only the contents move inward --
            // painting the chrome on the centred element instead would leave the
            // page showing either side of a bar meant to be the top of the window.
            'title' => 'Where the contents stop (container)',
            'source' => implode("\n", [
                '<shape:header container="3xl">',
                '    <shape:header.brand>Narrow — 3xl</shape:header.brand>',
                '</shape:header>',
                '',
                '<shape:header container="full">',
                '    <shape:header.brand>Edge to edge — full</shape:header.brand>',
                '</shape:header>',
            ]),
        ],
        [
            // Muted at rest, full ink and a tint when current. The marker is in
            // aria-current as well, which is the half of it this preview cannot
            // show and the half that matters to anyone not looking at the colour.
            'title' => 'The page you are on (current)',
            'source' => implode("\n", [
                '<shape:header.nav>',
                '    <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '    <shape:header.item href="/blog">Blog</shape:header.item>',
                '    <shape:header.item href="/pricing">Pricing</shape:header.item>',
                '</shape:header.nav>',
            ]),
        ],
        [
            // An href makes it a link and gives it a focus ring; without one it is a
            // div, which is what a shell that is already home wants.
            'title' => 'The brand, with somewhere to go and without',
            'source' => implode("\n", [
                '<shape:header.brand href="/">',
                '    <shape:icon name="sparkles" />',
                '    Acme',
                '</shape:header.brand>',
                '',
                '<shape:header.brand>',
                '    <shape:icon name="sparkles" />',
                '    Acme',
                '</shape:header.brand>',
            ]),
        ],
        [
            'title' => 'Dark mode (token swap, same markup)',
            // Darker than the panel it sits in, so the stage still reads as its own
            // surface once the chrome around it is dark too.
            'surface' => 'dark bg-neutral-950',
            'source' => implode("\n", [
                '<shape:header>',
                '    <shape:header.brand href="/">Acme</shape:header.brand>',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '    </shape:header.nav>',
                '</shape:header>',
            ]),
        ],
        [
            // The argument for --color-chrome being its own token, made in one
            // class: the bar moves and the field below it does not. Sharing
            // --color-surface would have tinted both.
            'title' => 'A tinted bar over untinted fields',
            'source' => implode("\n", [
                '<shape:header class="bg-neutral-tint">',
                '    <shape:header.brand>Acme</shape:header.brand>',
                '    <shape:header.nav class="ms-auto">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '    </shape:header.nav>',
                '</shape:header>',
                '',
                '<shape:input class="w-full" placeholder="Still on the surface token" />',
            ]),
        ],
        [
            // Shape ships no JavaScript, so the disclosure is the application's.
            // What the component gives you is the bar and a nav that knows how to
            // hide -- the rest is two Alpine attributes.
            'title' => 'Narrow viewports are the application\'s to open',
            'source' => implode("\n", [
                '<shape:header>',
                '    <shape:header.brand href="/">Acme</shape:header.brand>',
                '',
                '    <shape:header.nav class="ms-auto hidden md:flex">',
                '        <shape:header.item href="/docs" current>Docs</shape:header.item>',
                '        <shape:header.item href="/blog">Blog</shape:header.item>',
                '    </shape:header.nav>',
                '',
                '    <shape:button size="sm" icon="menu" aria-label="Menu" class="ms-auto md:hidden" />',
                '</shape:header>',
            ]),
        ],
    ],
];
