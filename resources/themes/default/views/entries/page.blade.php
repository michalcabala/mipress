@extends('layouts.app')

@section('title', $entry->title)
@section('meta_description', $entry->data['meta_description'] ?? $entry->getExcerpt())

@section('content')
    @php
        $heroExcerpt = $entry->data['perex'] ?? $entry->data['excerpt'] ?? null;
        $bodyFallbackKeys = ['meta_title', 'meta_description', 'excerpt', 'perex', 'intro', 'summary', 'category', 'reading_time', 'content'];
        $entryUrl = mipress_entry_url($entry);
    @endphp

    <article class="mp-article-shell">
        <section class="mp-page-hero">
            <div class="mp-container mp-page-hero__grid">
                <div>
                    <div class="mp-breadcrumbs">
                        <a href="{{ url('/') }}">Domů</a>
                        @if (filled($archivePath = mipress_collection_archive_path($collection)))
                            <span>/</span>
                            <a href="{{ url($archivePath) }}">{{ $collection->name }}</a>
                        @endif
                    </div>

                    @if (filled($entry->data['category'] ?? null))
                        <p class="mp-eyebrow">{{ $entry->data['category'] }}</p>
                    @endif

                    <h1 class="mp-display">{{ $entry->title }}</h1>

                    @if (filled($heroExcerpt))
                        <p class="mp-hero-copy">{{ $heroExcerpt }}</p>
                    @endif

                    <div class="mp-article-meta">
                        @if ($entry->published_at)
                            <span>{{ $entry->published_at->format('d.m.Y') }}</span>
                        @endif

                        <span>{{ $entry->getReadingTimeMinutes() }} min čtení</span>

                        @if (filled($entry->author?->name))
                            <span>{{ $entry->author?->name }}</span>
                        @endif

                        @if (filled($entryUrl))
                            <span class="mp-article-meta__pill">{{ $entryUrl }}</span>
                        @endif
                    </div>
                </div>

                <div class="mp-page-hero__aside">
                    @if ($entry->featuredImage?->url)
                        <img
                            src="{{ $entry->featuredImage->url }}"
                            alt="{{ $entry->title }}"
                            class="mp-page-hero__image"
                        >
                    @else
                        <div class="mp-page-hero__panel">
                            <p class="mp-eyebrow">Editorial focus</p>
                            <h2>{{ $collection->name }}</h2>
                            <p>
                                Šablona článku je připravená pro výrazný perex, obrázek, Mason obsah
                                i související doporučení z téže kolekce.
                            </p>
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="mp-container mp-article-layout">
            <div class="mp-article-body">
                @if ($entry->hasMasonContent())
                    {!! $entry->renderMasonContent() !!}
                @else
                    <section class="mp-brick mp-brick--narrative">
                        <div class="mp-brick__container">
                            <div class="mp-prose">
                                @foreach ($entry->data ?? [] as $key => $value)
                                    @continue(in_array($key, $bodyFallbackKeys, true))
                                    @continue(! is_string($value) || blank(trim(strip_tags($value))))

                                    <div>{!! nl2br(e($value)) !!}</div>
                                @endforeach
                            </div>
                        </div>
                    </section>
                @endif
            </div>

            <aside class="mp-article-sidebar">
                <div class="mp-sidebar-card">
                    <p class="mp-eyebrow">Z článku</p>
                    <h2>{{ $entry->getExcerpt(18) }}</h2>
                    <p>
                        Tato šablona počítá s textovými bloky, citacemi, insight gridy i CTA bloky
                        složenými přes Mason.
                    </p>
                </div>

                @if (($relatedEntries ?? collect())->isNotEmpty())
                    <div class="mp-sidebar-card">
                        <p class="mp-eyebrow">Další čtení</p>
                        <div class="mp-sidebar-list">
                            @foreach ($relatedEntries as $relatedEntry)
                                @if (filled($relatedUrl = mipress_entry_url($relatedEntry)))
                                    <a href="{{ url($relatedUrl) }}" class="mp-sidebar-link">
                                        <strong>{{ $relatedEntry->title }}</strong>
                                        <span>{{ $relatedEntry->getReadingTimeMinutes() }} min čtení</span>
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
