@php
    $collections = mipress_public_collections();
@endphp

<header class="sticky top-0 z-40 border-b border-slate-200/80 bg-white/85 backdrop-blur-xl dark:border-slate-800 dark:bg-slate-950/85">
    <div class="mx-auto flex w-full max-w-7xl items-center justify-between gap-4 px-4 py-3 sm:px-6 lg:px-8">
        <a href="{{ url('/') }}" class="group inline-flex items-center gap-3" aria-label="{{ config('app.name') }}">
            <span class="inline-flex h-11 w-11 items-center justify-center rounded-2xl bg-gradient-to-br from-blue-600 to-cyan-500 text-sm font-bold tracking-wide text-white shadow-lg shadow-blue-500/25">
                MP
            </span>
            <span>
                <strong class="block text-base font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">{{ config('app.name') }}</strong>
                <small class="block text-xs text-slate-500 dark:text-slate-400">Presentation Blue Theme</small>
            </span>
        </a>

        <button
            type="button"
            class="inline-flex items-center justify-center rounded-xl border border-slate-300 px-3 py-2 text-sm font-medium text-slate-700 transition hover:border-blue-300 hover:text-blue-700 md:hidden dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-500 dark:hover:text-blue-300"
            data-menu-toggle
            aria-controls="site-navigation-mobile"
            aria-expanded="false"
        >
            Menu
        </button>

        <div class="hidden items-center gap-3 md:flex" id="site-navigation" data-site-menu>
            <nav class="flex items-center gap-1 rounded-2xl border border-slate-200 bg-white/70 p-1 shadow-sm shadow-slate-200/50 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-none" aria-label="Hlavní navigace">
                <a href="{{ url('/') }}" @class([
                    'rounded-xl px-4 py-2 text-sm font-medium transition',
                    'bg-blue-600 text-white shadow-sm' => request()->url() === url('/'),
                    'text-slate-600 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300' => request()->url() !== url('/'),
                ])>
                    Domů
                </a>

                @foreach ($collections as $collection)
                    @php
                        $archivePath = mipress_collection_archive_path($collection);
                        $isActive = $archivePath && request()->is(ltrim($archivePath, '/').'*');
                    @endphp

                    @if ($archivePath)
                        <a href="{{ url($archivePath) }}" @class([
                            'rounded-xl px-4 py-2 text-sm font-medium transition',
                            'bg-blue-600 text-white shadow-sm' => $isActive,
                            'text-slate-600 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-300 dark:hover:bg-slate-800 dark:hover:text-blue-300' => ! $isActive,
                        ])>
                            {{ $collection->name }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="inline-flex items-center gap-1 rounded-2xl border border-slate-200 bg-white/70 p-1 text-sm shadow-sm shadow-slate-200/50 dark:border-slate-700 dark:bg-slate-900/70 dark:shadow-none" role="group" aria-label="Přepínač vzhledu">
                <button type="button" class="rounded-xl px-3 py-2 font-medium text-slate-600 transition dark:text-slate-300" data-theme-option="light">
                    Světlý
                </button>
                <button type="button" class="rounded-xl px-3 py-2 font-medium text-slate-600 transition dark:text-slate-300" data-theme-option="dark">
                    Tmavý
                </button>
                <button type="button" class="rounded-xl px-3 py-2 font-medium text-slate-600 transition dark:text-slate-300" data-theme-option="system">
                    Systém
                </button>
            </div>
        </div>
    </div>

    <div id="site-navigation-mobile" class="hidden border-t border-slate-200 bg-white/95 px-4 py-3 md:hidden dark:border-slate-800 dark:bg-slate-950/95" data-site-menu-mobile>
        <nav class="flex flex-col gap-2" aria-label="Mobilní navigace">
            <a href="{{ url('/') }}" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-blue-300">Domů</a>
            @foreach ($collections as $collection)
                @if (filled($archivePath = mipress_collection_archive_path($collection)))
                    <a href="{{ url($archivePath) }}" class="rounded-xl px-3 py-2 text-sm font-medium text-slate-700 hover:bg-blue-50 hover:text-blue-700 dark:text-slate-200 dark:hover:bg-slate-800 dark:hover:text-blue-300">{{ $collection->name }}</a>
                @endif
            @endforeach
        </nav>
    </div>
</header>
