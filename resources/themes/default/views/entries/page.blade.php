@extends('layouts.app')

@section('title', $entry->title)
@section('meta_description', $entry->data['meta_description'] ?? $entry->getExcerpt())

@section('content')
    @php
        $isPage = $entry instanceof \MiPress\Core\Models\Page;
        $heroExcerpt = $entry->data['perex'] ?? $entry->data['excerpt'] ?? null;
        $bodyFallbackKeys = ['meta_title', 'meta_description', 'excerpt', 'perex', 'intro', 'summary', 'category', 'reading_time', 'content'];
        $resourceUrl = mipress_entry_url($entry);
        $heroImageUrl = mipress_media_url($entry->featuredImage, 'hero');
        $terms = $entry instanceof \MiPress\Core\Models\Entry
            ? ($entry->relationLoaded('terms') ? $entry->terms : $entry->terms()->with('taxonomy')->ordered()->get())
            : collect();
        $groupedTerms = $terms
            ->groupBy(fn (\MiPress\Core\Models\Term $term): string => $term->taxonomy?->title ?? 'Topics')
            ->filter(fn ($group): bool => $group->isNotEmpty());
        $collections = mipress_public_collections();
    @endphp

    <article class="mp-page-detail">
        <section class="mp-page-hero">
            <div class="mp-container mp-page-hero__grid">
                <div data-reveal>
                    <div class="mp-breadcrumbs">
                        <a href="{{ url('/') }}">Home</a>
                        @if (! $isPage && filled($archivePath = mipress_collection_archive_path($collection)))
                            <span>/</span>
                            <a href="{{ url($archivePath) }}">{{ $collection->name }}</a>
                        @endif
                    </div>

                    <span class="mp-eyebrow">
                        {{ $isPage ? 'Public page' : ($entry->data['category'] ?? ($collection?->name ?? 'Entry')) }}
                    </span>
                    <h1 class="mp-display mp-display--compact">{{ $entry->title }}</h1>

                    @if (filled($heroExcerpt))
                        <p class="mp-lead">{{ $heroExcerpt }}</p>
                    @endif

                    <div class="mp-article-meta">
                        @if ($entry->published_at)
                            <span class="mp-article-meta__pill">{{ $entry->published_at->format('d.m.Y') }}</span>
                        @endif

                        @if (! $isPage)
                            <span class="mp-article-meta__pill">{{ $entry->getReadingTimeMinutes() }} min read</span>
                        @endif

                        @if (filled($entry->author?->name))
                            <span class="mp-article-meta__pill">{{ $entry->author?->name }}</span>
                        @endif

                        @if (filled($resourceUrl))
                            <span class="mp-article-meta__pill">{{ $resourceUrl }}</span>
                        @endif
                    </div>
                </div>

                <div data-reveal>
                    @if ($heroImageUrl)
                        <img src="{{ $heroImageUrl }}" alt="{{ $entry->title }}" class="mp-page-hero__image">
                    @else
                        <div class="mp-page-hero__panel">
                            <span class="mp-eyebrow">{{ $isPage ? 'Structured page' : 'Editorial detail' }}</span>
                            <h2>{{ $isPage ? 'Pages can now look like campaign pages.' : 'Entries now inherit the same SaaS design language.' }}</h2>
                            <p>
                                {{ $isPage
                                    ? 'Use MiPress pages for product, company and feature narratives without losing support for Mason blocks, featured imagery or SEO metadata.'
                                    : 'Article templates blend story content, metadata, taxonomies and related navigation into a polished public-facing experience.' }}
                            </p>

                            @if ($groupedTerms->isNotEmpty())
                                <div class="mp-tag-groups">
                                    @foreach ($groupedTerms as $taxonomyTitle => $taxonomyTerms)
                                        <div class="mp-tag-group">
                                            <span class="mp-card-kicker">{{ $taxonomyTitle }}</span>
                                            <div class="mp-tag-list">
                                                @foreach ($taxonomyTerms as $term)
                                                    <span class="mp-tag">{{ $term->title }}</span>
                                                @endforeach
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>
        </section>

        <div class="mp-container mp-article-layout">
            <div class="mp-article-body">
                @if ($entry->hasMasonContent())
                    <div class="mp-mason-flow">
                        {!! $entry->renderMasonContent() !!}
                    </div>
                @else
                    <section class="mp-prose-panel">
                        <div class="mp-prose">
                            @foreach ($entry->data ?? [] as $key => $value)
                                @continue(in_array($key, $bodyFallbackKeys, true))
                                @continue(! is_string($value) || blank(trim(strip_tags($value))))

                                <p>{!! nl2br(e($value)) !!}</p>
                            @endforeach
                        </div>
                    </section>
                @endif
            </div>

            <aside>
                <section class="mp-sidebar-card">
                    <span class="mp-eyebrow">{{ $isPage ? 'Page summary' : 'Story summary' }}</span>
                    <h2>{{ $entry->getExcerpt(18) }}</h2>
                    <p>
                        {{ $isPage
                            ? 'This page template supports public pages, homepage takeovers and Mason-driven landing sections inside the same theme shell.'
                            : 'This layout is ready for narrative blocks, quotes, insight grids, CTA sections and related reading modules.' }}
                    </p>
                </section>

                @if (($relatedEntries ?? collect())->isNotEmpty())
                    <section class="mp-sidebar-card">
                        <span class="mp-eyebrow">Related reading</span>
                        <div class="mp-sidebar-list">
                            @foreach ($relatedEntries as $relatedEntry)
                                @if (filled($relatedUrl = mipress_entry_url($relatedEntry)))
                                    <a href="{{ url($relatedUrl) }}" class="mp-sidebar-link">
                                        <strong>{{ $relatedEntry->title }}</strong>
                                        <span>{{ $relatedEntry->getReadingTimeMinutes() }} min read</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($groupedTerms->isNotEmpty())
                    <section class="mp-sidebar-card">
                        <span class="mp-eyebrow">Taxonomies</span>
                        <div class="mp-tag-groups">
                            @foreach ($groupedTerms as $taxonomyTitle => $taxonomyTerms)
                                <div class="mp-tag-group">
                                    <span class="mp-card-kicker">{{ $taxonomyTitle }}</span>
                                    <div class="mp-tag-list">
                                        @foreach ($taxonomyTerms as $term)
                                            <span class="mp-tag">{{ $term->title }}</span>
                                        @endforeach
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </section>
                @endif

                @if ($isPage && $collections->isNotEmpty())
                    <section class="mp-sidebar-card">
                        <span class="mp-eyebrow">Explore collections</span>
                        <div class="mp-sidebar-list">
                            @foreach ($collections as $siteCollection)
                                @if (filled($archivePath = mipress_collection_archive_path($siteCollection)))
                                    <a href="{{ url($archivePath) }}" class="mp-sidebar-link">
                                        <strong>{{ $siteCollection->name }}</strong>
                                        <span>{{ $siteCollection->description ?: 'Open the public archive.' }}</span>
                                    </a>
                                @endif
                            @endforeach
                        </div>
                    </section>
                @endif
            </aside>
        </div>
    </article>
@endsection
