@php
    $collections = mipress_public_collections();
@endphp

<footer class="mt-20 border-t border-slate-200 bg-gradient-to-br from-blue-950 via-blue-900 to-cyan-900 text-blue-50 dark:border-slate-800">
    <div class="mx-auto grid w-full max-w-7xl gap-10 px-4 py-12 sm:px-6 lg:grid-cols-3 lg:px-8">
        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">MiPress Default</p>
            <h2 class="mt-3 text-2xl font-semibold leading-tight" style="font-family: 'Space Grotesk', sans-serif;">Prezentační frontend připravený pro obsah i konverzi.</h2>
            <p class="mt-4 text-sm leading-7 text-blue-100/85">
                Modrá identita, výrazná typografie, Mason bricks a přehledná informační architektura
                tvoří moderní základ pro firemní i obsahové weby.
            </p>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Sekce</p>
            <ul class="mt-4 space-y-2 text-sm text-blue-100/90">
                <li><a href="{{ url('/') }}" class="transition hover:text-white">Domů</a></li>
                @foreach ($collections as $collection)
                    @if (filled($path = mipress_collection_archive_path($collection)))
                        <li><a href="{{ url($path) }}" class="transition hover:text-white">{{ $collection->name }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>

        <div>
            <p class="text-xs font-semibold uppercase tracking-[0.22em] text-cyan-200">Provoz</p>
            <ul class="mt-4 space-y-2 text-sm text-blue-100/90">
                <li><a href="{{ url('/admin') }}" class="transition hover:text-white">Administrace</a></li>
                <li><span>Aktivní téma: Default</span></li>
                <li><span>&copy; {{ date('Y') }} {{ config('app.name') }}</span></li>
            </ul>
        </div>
    </div>
</footer>
