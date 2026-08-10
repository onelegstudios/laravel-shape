<?php

declare(strict_types=1);

// The index page: a filter bar and a long table of rows, each carrying the
// controls that act on it. Button- and icon-heavy rather than field-heavy, which
// makes it the page that says how much stages 1 and 2 already collected -- the
// button folds and the icons inside it fold with it.
//
// The row loop is the part worth reading twice. One call site, forty renders: a
// folded component is evaluated once here and its markup repeated, so this is
// also the shape that would expose anything a fold froze.

return [
    'title' => 'Records',
    'summary' => 'A filtered index of 40 rows, each with its own controls. Button- and icon-heavy.',
    'data' => fn (): array => [
        'records' => array_map(fn (int $i): array => [
            'id' => $i,
            'name' => 'Record '.$i,
            'owner' => ['Ada', 'Grace', 'Alan', 'Edsger'][$i % 4],
            'state' => ['active', 'draft', 'archived'][$i % 3],
        ], range(1, 40)),
    ],
    'markup' => <<<'BLADE'
        <shape:header>
            <shape:header.brand href="/">Acme</shape:header.brand>
            <shape:header.nav>
                <shape:header.item href="/settings">Settings</shape:header.item>
                <shape:header.item href="/records" current>Records</shape:header.item>
                <shape:header.item href="/dashboard">Dashboard</shape:header.item>
            </shape:header.nav>
        </shape:header>

        <main class="mx-auto max-w-5xl space-y-6 px-6 py-10">
            <div class="flex items-end justify-between gap-4">
                <div>
                    <shape:heading level="1" size="lg">Records</shape:heading>
                    <p class="mt-1 text-sm text-ink-muted">40 records.</p>
                </div>

                <div class="flex items-center gap-2">
                    <shape:button size="sm" icon="download">Export</shape:button>
                    <shape:button size="sm" variant="solid" color="primary" icon="check">New record</shape:button>
                </div>
            </div>

            {{-- The filter bar. Static props, so every control on it folds if the
                 family ever does. --}}
            <div class="flex flex-wrap items-end gap-3 rounded-lg border border-border p-4">
                <div class="w-64">
                    <shape:input name="q" size="sm" icon="search" placeholder="Search records" />
                </div>

                <div class="w-40">
                    <shape:select name="state" size="sm">
                        <option>Any state</option>
                        <option>Active</option>
                        <option>Draft</option>
                        <option>Archived</option>
                    </shape:select>
                </div>

                <div class="w-40">
                    <shape:select name="owner" size="sm">
                        <option>Anyone</option>
                        <option>Ada</option>
                        <option>Grace</option>
                    </shape:select>
                </div>

                <shape:button size="sm" variant="ghost">Clear</shape:button>
            </div>

            <table class="w-full text-left text-sm">
                <thead class="border-b border-hairline">
                    <tr>
                        <th class="w-8 py-2"></th>
                        <th class="py-2 font-medium">Name</th>
                        <th class="py-2 font-medium">Owner</th>
                        <th class="py-2 font-medium">State</th>
                        <th class="py-2"></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach ($records as $record)
                        <tr class="border-b border-hairline">
                            <td class="py-2">
                                <shape:checkbox name="selected[]" :value="$record['id']" size="sm" />
                            </td>
                            <td class="py-2">{{ $record['name'] }}</td>
                            <td class="py-2 text-ink-muted">{{ $record['owner'] }}</td>
                            <td class="py-2 text-ink-muted">{{ $record['state'] }}</td>
                            <td class="py-2">
                                <div class="flex items-center justify-end gap-1">
                                    <shape:button size="xs" icon="settings" />
                                    <shape:button size="xs" icon="download" />
                                    <shape:button size="xs" color="danger" icon="trash-2" />
                                </div>
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>

            <div class="flex items-center justify-between border-t border-hairline pt-4">
                <p class="text-sm text-ink-muted">Showing 40 of 40</p>

                <div class="flex items-center gap-2">
                    <shape:button size="sm" variant="ghost">Previous</shape:button>
                    <shape:button size="sm" variant="ghost" icon-trailing="arrow-right">Next</shape:button>
                </div>
            </div>
        </main>
        BLADE,
];
