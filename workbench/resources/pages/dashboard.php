<?php

declare(strict_types=1);

// The control group. Headings, a bar, a grid of cards and a lot of icons, with
// almost no form on it -- the page where none of this is expected to matter much.
//
// Worth measuring precisely because of that: a strategy that only pays on a page
// built out of fields is a narrower win than one that pays here too, and the way
// to know is to have a page in the set that does not flatter it.

return [
    'title' => 'Dashboard',
    'summary' => 'Headings, cards and icons, with barely a control on it. The page where none of this matters much.',
    'data' => fn (): array => [
        'stats' => [
            ['label' => 'Active records', 'value' => '1,284', 'icon' => 'circle-check', 'tone' => 'success'],
            ['label' => 'Drafts', 'value' => '96', 'icon' => 'info', 'tone' => 'info'],
            ['label' => 'Needs review', 'value' => '12', 'icon' => 'triangle-alert', 'tone' => 'warning'],
            ['label' => 'Failed imports', 'value' => '3', 'icon' => 'circle-x', 'tone' => 'danger'],
        ],
        'activity' => array_map(fn (int $i): array => [
            'who' => ['Ada', 'Grace', 'Alan', 'Edsger'][$i % 4],
            'what' => 'updated record '.($i * 7),
        ], range(1, 12)),
    ],
    'markup' => <<<'BLADE'
        <shape:header>
            <shape:header.brand href="/">Acme</shape:header.brand>
            <shape:header.nav>
                <shape:header.item href="/settings">Settings</shape:header.item>
                <shape:header.item href="/records">Records</shape:header.item>
                <shape:header.item href="/dashboard" current>Dashboard</shape:header.item>
            </shape:header.nav>
        </shape:header>

        <main class="mx-auto max-w-5xl space-y-8 px-6 py-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <shape:heading level="1" size="lg">Dashboard</shape:heading>
                    <p class="mt-1 text-sm text-ink-muted">Everything at a glance.</p>
                </div>

                <shape:button size="sm" icon="sparkles">What's new</shape:button>
            </div>

            <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($stats as $stat)
                    <div class="rounded-lg border border-border p-4">
                        <div class="flex items-center gap-2">
                            <shape:icon :name="$stat['icon']" :color="$stat['tone']" size="sm" />
                            <p class="text-sm text-ink-muted">{{ $stat['label'] }}</p>
                        </div>
                        <p class="mt-2 text-2xl font-semibold">{{ $stat['value'] }}</p>
                    </div>
                @endforeach
            </div>

            <div class="grid gap-6 lg:grid-cols-3">
                <section class="space-y-3 lg:col-span-2">
                    <shape:heading level="2">Recent activity</shape:heading>

                    <ul class="divide-y divide-hairline rounded-lg border border-border">
                        @foreach ($activity as $entry)
                            <li class="flex items-center gap-3 px-4 py-3 text-sm">
                                <shape:icon name="check" size="sm" />
                                <span class="font-medium">{{ $entry['who'] }}</span>
                                <span class="text-ink-muted">{{ $entry['what'] }}</span>
                            </li>
                        @endforeach
                    </ul>
                </section>

                <section class="space-y-3">
                    <shape:heading level="2">Shortcuts</shape:heading>

                    <div class="flex flex-col items-stretch gap-2">
                        <shape:button icon="check">Approve queue</shape:button>
                        <shape:button icon="download">Export everything</shape:button>
                        <shape:button icon="settings">Workspace settings</shape:button>
                        <shape:button icon-trailing="arrow-right" variant="ghost">All reports</shape:button>
                    </div>
                </section>
            </div>
        </main>
        BLADE,
];
