<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $page['title'] }} — Shape Pages</title>

    {{-- Applied before the first paint so a forced scheme does not flash the
         other one. shape.css defines what these classes do; all this does is put
         the stored choice back on <html>. Shared with the gallery's layout on
         purpose: the two should not disagree about which scheme you chose. --}}
    <script>
        const scheme = localStorage.getItem('shape-gallery-scheme');

        if (scheme === 'light' || scheme === 'dark') {
            document.documentElement.classList.add(scheme);
        }
    </script>

    {{-- Dev-only Tailwind build for the workbench. Not part of the package. --}}
    <script src="https://cdn.jsdelivr.net/npm/@tailwindcss/browser@4"></script>

    <style type="text/tailwindcss">
        @import "tailwindcss";

        {!! $theme !!}
    </style>
</head>
<body class="h-full bg-page text-ink antialiased">
    {{-- The workbench's own bar, kept visually apart from the page below it: what
         is being measured is the markup in the fixture, and a strip of chrome that
         looked like part of it would make this page harder to read rather than
         easier. --}}
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 border-b border-wb-border bg-wb-panel px-4 py-2 text-sm">
        <span class="font-medium text-wb-accent">Workbench</span>

        @foreach ($pages as $slug => $item)
            <a
                href="{{ url('page/'.$slug) }}"
                @class([
                    'text-wb-ink hover:underline',
                    'font-semibold underline' => $slug === $current,
                ])
            >{{ $item['title'] }}</a>
        @endforeach

        <a href="{{ url('/') }}" class="ml-auto text-wb-ink-muted hover:underline">Component gallery →</a>
    </div>

    {{-- The fixture, rendered as the application would render it. `Blade::render`
         compiles a fresh template per request, which is a workbench affordance
         rather than how a real page behaves -- benchmarks/pages.php puts the same
         markup in one view compiled once, which is what production runs with. --}}
    {!! Blade::render($page['markup'], $data) !!}
</body>
</html>
