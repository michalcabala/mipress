@extends('layouts.app')

@section('title', config('app.name').' | SaaS CMS frontend')
@section('meta_description', 'MiPress default theme redesigned as a SaaS presentation website with dark mode, Mason storytelling and SEO-ready public pages.')

@section('content')
    @php
        $collections = $collections ?? mipress_public_collections();
        $featuredEntries = $featuredEntries ?? collect();
        $firstEntryUrl = $featuredEntries->isNotEmpty() ? mipress_entry_url($featuredEntries->first()) : null;
    @endphp

    <section class="mp-home-hero">
        <div class="mp-container mp-home-hero__grid">
            <div class="mp-hero-copy" data-reveal>
                <span class="mp-eyebrow">MiPress default theme</span>
                <h1 class="mp-display">A SaaS presentation layer for your CMS, not just another blog skin.</h1>
                <p class="mp-lead">
                    MiPress now ships with a product-facing frontend that can sell the CMS itself while still
                    handling public pages, entries, archives, Mason storytelling and SEO metadata across the site.
                </p>

                <div class="mp-button-row">
                    @if ($firstEntryUrl)
                        <a href="{{ url($firstEntryUrl) }}" class="mp-button mp-button--primary">View sample content</a>
                    @endif

                    <a href="{{ url('/admin') }}" class="mp-button mp-button--ghost">Open admin</a>
                </div>

                <div class="mp-proof-strip">
                    <span>Pages + entries</span>
                    <span>Mason bricks</span>
                    <span>Dark mode</span>
                    <span>SEO ready</span>
                </div>
            </div>

            <div class="mp-home-hero__stack">
                <article class="mp-feature-panel" data-reveal>
                    <span class="mp-eyebrow">Frontend system</span>
                    <h2>One visual language for homepage, archives, details and public pages.</h2>
                    <p>
                        The redesign shares a single SaaS-ready design system across product marketing sections,
                        editorial listings and long-form Mason content.
                    </p>
                </article>

                <article class="mp-feature-panel mp-feature-panel--accent" data-reveal>
                    <span class="mp-eyebrow">Mason</span>
                    <h2>Bricks now feel like landing-page sections instead of isolated content blocks.</h2>
                    <p>
                        Narrative, quote, insight grid and CTA sections inherit the same rhythm, spacing and
                        contrast system as the rest of the public frontend.
                    </p>
                </article>

                <div class="mp-stat-grid" data-reveal>
                    <article class="mp-stat-card">
                        <strong>01</strong>
                        <span>Fullscreen mobile overlay menu</span>
                    </article>
                    <article class="mp-stat-card">
                        <strong>02</strong>
                        <span>System, light and dark display modes</span>
                    </article>
                    <article class="mp-stat-card">
                        <strong>03</strong>
                        <span>SEO metadata, canonicals and structured data</span>
                    </article>
                </div>
            </div>
        </div>
    </section>

    <section class="mp-section" id="product">
        <div class="mp-container">
            <div class="mp-section-heading" data-reveal>
                <span class="mp-eyebrow">Built for publishing products</span>
                <h2>Everything needed to present MiPress like a modern SaaS platform.</h2>
                <p>
                    The default theme now acts like a polished product website first, while still staying fully
                    compatible with collections, archive routes, detail pages and editorial workflows.
                </p>
            </div>

            <div class="mp-pillars">
                <article class="mp-pillar" data-reveal>
                    <span class="mp-card-kicker">Pages</span>
                    <h3>Structured pages with campaign-grade sections</h3>
                    <p>Hero areas, rich Mason content, internal links and featured visuals work for company pages and product landing pages alike.</p>
                </article>

                <article class="mp-pillar" data-reveal>
                    <span class="mp-card-kicker">Entries</span>
                    <h3>Editorial content that still looks like part of the product site</h3>
                    <p>Collections, reading metadata, author context and related content fit into the same visual system as the homepage.</p>
                </article>

                <article class="mp-pillar" data-reveal>
                    <span class="mp-card-kicker">SEO</span>
                    <h3>Canonical tags, social metadata and schema already flow through the layout</h3>
                    <p>The theme respects MiPress SEO settings and keeps public pages indexable, shareable and presentation-ready.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="mp-section mp-section--soft" id="stories">
        <div class="mp-container">
            <div class="mp-section-heading" data-reveal>
                <span class="mp-eyebrow">Recent content</span>
                <h2>Public entries inherit the same premium product aesthetic.</h2>
                <p>Featured stories can lead the grid while the rest of the archive keeps a clean, performance-friendly rhythm.</p>
            </div>

            @if ($featuredEntries->isNotEmpty())
                <div class="mp-article-grid">
                    @foreach ($featuredEntries as $featuredEntry)
                        @include('partials.article-card', [
                            'entry' => $featuredEntry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>
            @else
                <section class="mp-empty-state" data-reveal>
                    <span class="mp-eyebrow">Waiting for content</span>
                    <h2>Publish a page or an entry and the frontend will populate itself.</h2>
                    <p>
                        The theme is ready even before launch content exists. Once content is published,
                        cards, archive screens and detail pages automatically pick it up.
                    </p>
                </section>
            @endif
        </div>
    </section>

    @if ($collections->isNotEmpty())
        <section class="mp-section" id="collections">
            <div class="mp-container">
                <div class="mp-section-heading" data-reveal>
                    <span class="mp-eyebrow">Public collections</span>
                    <h2>Navigation and archives are generated directly from public collection routes.</h2>
                    <p>
                        This keeps the SaaS shell dynamic: collections become public hubs automatically,
                        while still allowing custom homepage pages and direct entry routes.
                    </p>
                </div>

                <div class="mp-collection-grid">
                    @foreach ($collections as $collection)
                        @if (filled($archivePath = mipress_collection_archive_path($collection)))
                            <a href="{{ url($archivePath) }}" class="mp-collection-card" data-reveal>
                                <span class="mp-card-kicker">Collection</span>
                                <strong>{{ $collection->name }}</strong>
                                <span>{{ $collection->description ?: 'Public archive generated from the collection route.' }}</span>
                                <span class="mp-collection-card__path">{{ $archivePath }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    <section class="mp-section mp-section--cta">
        <div class="mp-container">
            <div class="mp-cta mp-cta--homepage" data-reveal>
                <div>
                    <span class="mp-eyebrow">Ready to launch</span>
                    <h2>Use the default theme as your product site, then shape every page with Mason.</h2>
                    <p class="mp-cta__text">
                        MiPress now starts with a frontend that can explain the product, publish content and support discoverability
                        from day one.
                    </p>
                </div>

                <div class="mp-cta__actions">
                    <a href="{{ url('/admin') }}" class="mp-button mp-button--primary">Configure the site</a>
                    @if ($firstEntryUrl)
                        <a href="{{ url($firstEntryUrl) }}" class="mp-button mp-button--ghost">Open public example</a>
                    @endif
                </div>
            </div>
        </div>
    </section>
@endsection
