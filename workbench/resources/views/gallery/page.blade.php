@extends('gallery.layout')

@section('content')
    <header class="mb-10">
        <h1 class="text-3xl font-bold tracking-tight">{{ $page['title'] }}</h1>
        <p class="mt-2 text-wb-ink-muted">{{ $page['summary'] }}</p>
    </header>

    {{-- Each entry renders the real component and shows the source tag beside it.
         The examples come from workbench/resources/gallery in plain PHP so the
         branded tag strings survive the compile-time preprocessor. --}}
    <div class="space-y-6">
        @foreach ($page['examples'] as $example)
            <section class="overflow-hidden rounded-xl border border-wb-border bg-wb-panel shadow-sm">
                <div class="border-b border-wb-hairline px-5 py-3">
                    <h2 class="text-sm font-semibold text-wb-ink">{{ $example['title'] }}</h2>
                </div>

                {{-- The stage names its own scheme, so it does not move when the
                     chrome around it does: a preview that changed with the page
                     would make every row a moving target to compare against. The
                     default is a light stage; an example asks for a dark one by
                     setting `surface`, which is how the dark rows below stay dark
                     on a light page and light-scheme rows stay light on a dark
                     one. `light` and `dark` are shape.css's own subtree classes.

                     text-wb-ink is restated here rather than inherited from the
                     body: light-dark() resolves where the property is declared,
                     so inheriting it hands a light stage the chrome's colour and
                     an icon on currentColor comes out white on white. --}}
                <div class="flex flex-wrap items-center gap-4 px-5 py-8 text-wb-ink {{ $example['surface'] ?? 'light bg-white' }}">
                    {{-- The rendered component --}}
                    {!! Blade::render($example['source']) !!}
                </div>

                <pre class="overflow-x-auto border-t border-wb-hairline bg-wb-code px-5 py-4 text-sm text-wb-code-ink"><code>{{ $example['source'] }}</code></pre>
            </section>
        @endforeach
    </div>
@endsection
