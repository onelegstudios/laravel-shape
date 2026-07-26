<!DOCTYPE html>
<html lang="en" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Shape — Component Gallery</title>

    {{-- Dev-only styling for the workbench chrome. Not part of the package. --}}
    <script src="https://cdn.tailwindcss.com"></script>
</head>
<body class="h-full bg-slate-50 text-slate-900 antialiased">
    <div class="mx-auto max-w-3xl px-6 py-16">
        <header class="mb-12">
            <p class="text-sm font-medium text-indigo-600">Workbench</p>
            <h1 class="mt-1 text-3xl font-bold tracking-tight">Shape Component Gallery</h1>
            <p class="mt-2 text-slate-600">
                A live sandbox for Shape's Blade components, served from the package's
                Testbench workbench. Edit a component and refresh to see it here.
            </p>
        </header>

        {{-- Each entry renders the real component and shows the source tag beside it.
             $examples is provided by the route in plain PHP so the branded tag
             strings survive the compile-time preprocessor. --}}
        <div class="space-y-6">
            @foreach ($examples as $example)
                <section class="overflow-hidden rounded-xl border border-slate-200 bg-white shadow-sm">
                    <div class="border-b border-slate-100 px-5 py-3">
                        <h2 class="text-sm font-semibold text-slate-700">{{ $example['title'] }}</h2>
                    </div>

                    <div class="flex items-center gap-4 px-5 py-8">
                        {{-- The rendered component --}}
                        {!! Blade::render($example['source']) !!}
                    </div>

                    <pre class="overflow-x-auto border-t border-slate-100 bg-slate-900 px-5 py-4 text-sm text-slate-100"><code>{{ $example['source'] }}</code></pre>
                </section>
            @endforeach
        </div>

        <footer class="mt-12 text-sm text-slate-400">
            onelegstudios/laravel-shape · <code>composer serve</code>
        </footer>
    </div>
</body>
</html>
