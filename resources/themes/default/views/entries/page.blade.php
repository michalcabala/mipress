@extends('layouts.app')

@section('title', $entry->title)
@section('meta_description', $entry->data['meta_description'] ?? $entry->getExcerpt())

@section('content')
    @php
        $heroExcerpt = $entry->data['perex'] ?? $entry->data['excerpt'] ?? null;
        $bodyFallbackKeys = ['meta_title', 'meta_description', 'excerpt', 'perex', 'intro', 'summary', 'category', 'reading_time', 'content'];
        $entryUrl = mipress_entry_url($entry);
    @endphp

    <article class="pb-20 pt-14 sm:pt-18">
        <section>
            <div class="mx-auto grid w-full max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1.15fr_0.85fr] lg:px-8">
                <div>
                    <div class="flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                        <a href="{{ url('/') }}" class="hover:text-blue-700 dark:hover:text-blue-300">Domů</a>
                        @if (filled($archivePath = mipress_collection_archive_path($collection)))
                            <span>/</span>
                            <a href="{{ url($archivePath) }}" class="hover:text-blue-700 dark:hover:text-blue-300">{{ $collection->name }}</a>
                        @endif
                    </div>

                    @if (filled($entry->data['category'] ?? null))
                        <p class="mt-5 text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">{{ $entry->data['category'] }}</p>
                    @endif

                    <h1 class="mt-3 text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">{{ $entry->title }}</h1>

                    @if (filled($heroExcerpt))
                        <p class="mt-5 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">{{ $heroExcerpt }}</p>
                    @endif

                    <div class="mt-6 flex flex-wrap items-center gap-3 text-xs font-semibold uppercase tracking-[0.15em] text-slate-500 dark:text-slate-400">
                        @if ($entry->published_at)
                            <span>{{ $entry->published_at->format('d.m.Y') }}</span>
                        @endif

                        <span>{{ $entry->getReadingTimeMinutes() }} min čtení</span>

                        @if (filled($entry->author?->name))
                            <span>{{ $entry->author?->name }}</span>
                        @endif

                        @if (filled($entryUrl))
                            <span class="rounded-md bg-blue-50 px-2 py-1 text-[10px] text-blue-700 dark:bg-blue-950/50 dark:text-blue-200">{{ $entryUrl }}</span>
                        @endif
                    </div>
                </div>

                <div>
                    @if ($entry->featuredImage?->url)
                        <img
                            src="{{ $entry->featuredImage->url }}"
                            alt="{{ $entry->title }}"
                            class="h-full max-h-107.5 w-full rounded-3xl border border-slate-200 object-cover shadow-lg shadow-slate-300/30 dark:border-slate-800 dark:shadow-none"
                        >
                    @else
                        <div class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                            <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Editorial focus</p>
                            <h2 class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">{{ $collection->name ?? $entry->title }}</h2>
                            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">Šablona článku je připravená pro výrazný perex, obrázek, Mason obsah i související doporučení z téže kolekce.</p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="mx-auto mt-10 grid w-full max-w-7xl gap-8 px-4 sm:px-6 lg:grid-cols-[1fr_320px] lg:px-8">
            <div class="space-y-8">
                @if ($entry->hasMasonContent())
                    {!! $entry->renderMasonContent() !!}
                @else
                    <section class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                        <div class="prose prose-slate max-w-none prose-headings:font-semibold prose-headings:text-slate-900 prose-p:leading-8 dark:prose-invert" style="font-family: 'Plus Jakarta Sans', sans-serif;">
                                @foreach ($entry->data ?? [] as $key => $value)
                                    @continue(in_array($key, $bodyFallbackKeys, true))
                                    @continue(! is_string($value) || blank(trim(strip_tags($value))))

                                    <div>{!! nl2br(e($value)) !!}</div>
                                @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside class="space-y-4">
                <div class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Z článku</p>
                    <h2 class="mt-3 text-xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">{{ $entry->getExcerpt(18) }}</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">Tato šablona počítá s textovými bloky, citacemi, insight gridy i CTA bloky složenými přes Mason.</p>
                </div>

                @if (($relatedEntries ?? collect())->isNotEmpty())
                    <div class="rounded-3xl border border-slate-200 bg-white/90 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                        <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Další čtení</p>
                        <div class="mt-3 space-y-3">
                            @foreach ($relatedEntries as $relatedEntry)
                                @if (filled($relatedUrl = mipress_entry_url($relatedEntry)))
                                    <a href="{{ url($relatedUrl) }}" class="block rounded-xl border border-slate-200 px-3 py-3 transition hover:border-blue-300 hover:bg-blue-50/60 dark:border-slate-700 dark:hover:border-blue-600 dark:hover:bg-blue-950/30">
                                        <strong class="block text-sm text-slate-900 dark:text-white">{{ $relatedEntry->title }}</strong>
                                        <span class="mt-1 block text-xs text-slate-500 dark:text-slate-400">{{ $relatedEntry->getReadingTimeMinutes() }} min čtení</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </div>
                @endif
            </aside>
        </div>
    </article>
@endsection
