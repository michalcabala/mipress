@extends('layouts.app')

@section('title', $collection->name.' | '.config('app.name'))
@section('meta_description', 'Archiv kolekce '.$collection->name.' v prezentační blue šabloně miPress.')

@section('content')
    <section class="pt-16 sm:pt-20">
        <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8">
            <div>
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Archiv</p>
                <h1 class="mt-3 text-4xl font-semibold text-slate-900 sm:text-5xl dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">{{ $collection->name }}</h1>
                <p class="mt-4 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">
                    Výpis je navržený pro prezentační i obsahové weby. Podporuje zvýrazněný první příspěvek,
                    konzistentní karty a přehledné stránkování.
                </p>
            </div>

            <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                <div class="space-y-3 text-sm text-slate-600 dark:text-slate-300">
                    <p><strong class="text-slate-900 dark:text-white">{{ $entries->total() }}</strong> publikovaných položek</p>
                    <p>Řazení: {{ $collection->dated ? 'nejnovější nahoře' : 'vlastní pořadí' }}</p>
                    <p class="rounded-lg bg-blue-50 px-3 py-2 text-xs font-medium text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">Route: {{ $collection->route }}</p>
                </div>
            </div>
        </div>
    </section>

    <section class="mt-14 pb-20">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            @if ($entries->count() > 0)
                <div class="grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($entries as $entry)
                        @include('partials.article-card', [
                            'entry' => $entry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>

                @if ($entries->hasPages())
                    <nav class="mt-10 flex items-center justify-between rounded-2xl border border-slate-200 bg-white/90 px-5 py-4 dark:border-slate-800 dark:bg-slate-900/85" aria-label="Stránkování archivu">
                        @if ($entries->previousPageUrl())
                            <a href="{{ $entries->previousPageUrl() }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-600 dark:hover:text-blue-300">
                                Novější položky
                            </a>
                        @else
                            <span></span>
                        @endif

                        <span class="text-sm font-medium text-slate-600 dark:text-slate-300">Strana {{ $entries->currentPage() }} z {{ $entries->lastPage() }}</span>

                        @if ($entries->nextPageUrl())
                            <a href="{{ $entries->nextPageUrl() }}" class="inline-flex items-center rounded-xl border border-slate-300 px-4 py-2 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-slate-700 dark:text-slate-200 dark:hover:border-blue-600 dark:hover:text-blue-300">
                                Starší položky
                            </a>
                        @endif
                    </nav>
                @endif
            @else
                <div class="rounded-3xl border border-dashed border-slate-300 bg-white/85 p-10 text-center dark:border-slate-700 dark:bg-slate-900/80">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Archiv je prázdný</p>
                    <h2 class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Kolekce zatím neobsahuje žádný publikovaný obsah.</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Jakmile publikujete první položku, objeví se zde zvýrazněná feature karta i celý seznam článků.
                    </p>
                </div>
            @endif
        </div>
    </section>
@endsection
