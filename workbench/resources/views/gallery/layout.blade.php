<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }} — Shape Component Gallery</title>

    {{-- Applied before the first paint so a forced scheme does not flash the
         other one. shape.css defines what these classes do; all this does is
         put the stored choice back on <html>. --}}
    <script>
        const scheme = localStorage.getItem('shape-gallery-scheme');

        if (scheme === 'light' || scheme === 'dark') {
            document.documentElement.classList.add(scheme);
        }
    </script>

    {{-- Dev-only Tailwind build for the workbench. Not part of the package. --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    {{-- The package's real theme, not a copy of it: the gallery previews the same
         role definitions a consuming app gets from resources/css/shape.css, so a
         change there shows up here. The browser build scans the DOM rather than
         the filesystem, which is why the route strips the @source directive. --}}
    <style type="text/tailwindcss">
        /* This block is the entry stylesheet, so it has to pull Tailwind in
           itself -- without this the browser build compiles to nothing. */
        @import "tailwindcss";

        {!! $theme !!}
    </style>
</head>
<body class="h-full bg-wb-page text-wb-ink antialiased">
    <div class="lg:flex">
        {{-- One nav, two shapes: a scrolling row of chips on a phone, a sticky
             column beside the content once there is room for one. --}}
        <aside class="border-b border-wb-border bg-wb-panel px-6 py-4 lg:sticky lg:top-0 lg:flex lg:h-screen lg:w-64 lg:shrink-0 lg:flex-col lg:overflow-y-auto lg:border-r lg:border-b-0 lg:py-8">
            <p class="text-sm font-medium text-wb-accent">Workbench</p>
            <p class="mt-1 text-base font-semibold tracking-tight">Shape</p>

            <nav class="mt-4 flex gap-1 overflow-x-auto lg:mt-6 lg:flex-col lg:overflow-visible">
                @foreach ($pages as $slug => $item)
                    <a
                        href="{{ url($slug) }}"
                        @class([
                            'shrink-0 rounded-lg px-3 py-2 text-sm',
                            'bg-wb-active font-semibold' => $slug === $current,
                            'font-medium text-wb-ink-muted hover:bg-wb-hover hover:text-wb-ink' => $slug !== $current,
                        ])
                        @if ($slug === $current) aria-current="page" @endif
                    >{{ $item['title'] }}</a>
                @endforeach
            </nav>

            {{-- Three states rather than a two-way switch: "system" is the mode
                 the package ships, and a gallery that cannot go back to it can
                 only ever preview the two overrides. --}}
            <div class="mt-4 lg:mt-auto lg:pt-8">
                <div class="inline-flex gap-1 rounded-lg border border-wb-border p-1" role="group" aria-label="Colour scheme">
                    @foreach ([['system', 'monitor', 'System'], ['light', 'sun', 'Light'], ['dark', 'moon', 'Dark']] as [$value, $icon, $label])
                        <button
                            type="button"
                            data-scheme="{{ $value }}"
                            aria-pressed="false"
                            title="{{ $label }}"
                            class="cursor-pointer rounded-md p-2 text-wb-ink-muted hover:bg-wb-hover hover:text-wb-ink aria-pressed:bg-wb-active aria-pressed:text-wb-ink"
                        >
                            <shape:icon name="{{ $icon }}" size="sm" label="{{ $label }}" />
                        </button>
                    @endforeach
                </div>
            </div>
        </aside>

        <main class="min-w-0 flex-1">
            <div class="mx-auto max-w-3xl px-6 py-12 lg:py-16">
                @yield('content')

                <footer class="mt-12 text-sm text-wb-ink-faint">
                    onelegstudios/laravel-shape · <code>composer serve</code>
                </footer>
            </div>
        </main>
    </div>

    <script>
        const buttons = document.querySelectorAll('[data-scheme]');

        const apply = (choice) => {
            // The classes are shape.css's own subtree overrides, so the chrome and
            // the components below it change together. "system" is their absence.
            document.documentElement.classList.remove('light', 'dark');

            if (choice !== 'system') {
                document.documentElement.classList.add(choice);
            }

            buttons.forEach((button) => {
                button.setAttribute('aria-pressed', String(button.dataset.scheme === choice));
            });
        };

        buttons.forEach((button) => button.addEventListener('click', () => {
            localStorage.setItem('shape-gallery-scheme', button.dataset.scheme);
            apply(button.dataset.scheme);
        }));

        apply(localStorage.getItem('shape-gallery-scheme') ?? 'system');
    </script>
</body>
</html>
