@extends('layouts.app')

@section('title', $collection->name.' | '.config('app.name'))
@section('meta_description', $collection->description ?: 'Public archive for the '.$collection->name.' collection in the MiPress SaaS presentation theme.')

@section('content')
    <section class="mp-archive-hero">
        <div class="mp-container mp-archive-hero__grid">
            <div data-reveal>
                <span class="mp-eyebrow">Collection archive</span>
                <h1 class="mp-display mp-display--compact">{{ $collection->name }}</h1>
                <p class="mp-lead">
                    {{ $collection->description ?: 'A public archive designed to feel like a premium knowledge hub inside the same SaaS presentation layer.' }}
                </p>
            </div>

            <aside class="mp-archive-summary" data-reveal>
                <div class="mp-archive-summary__item">
                    <span class="mp-card-kicker">Published items</span>
                    <strong>{{ $entries->total() }}</strong>
                </div>
                <div class="mp-archive-summary__item">
                    <span class="mp-card-kicker">Ordering</span>
                    <strong>{{ $collection->dated ? 'Newest first' : 'Manual order' }}</strong>
                </div>
                <div class="mp-archive-summary__item">
                    <span class="mp-card-kicker">Route</span>
                    <strong>{{ $collection->route }}</strong>
                </div>
            </aside>
        </div>
    </section>

    <section class="mp-section mp-section--soft">
        <div class="mp-container">
            @if ($entries->count() > 0)
                <div class="mp-article-grid">
                    @foreach ($entries as $entry)
                        @include('partials.article-card', [
                            'entry' => $entry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>

                @if ($entries->hasPages())
                    <nav class="mp-pagination" aria-label="Archive pagination">
                        @if ($entries->previousPageUrl())
                            <a href="{{ $entries->previousPageUrl() }}" class="mp-button mp-button--ghost">Newer entries</a>
                        @else
                            <span></span>
                        @endif

                        <span>Page {{ $entries->currentPage() }} of {{ $entries->lastPage() }}</span>

                        @if ($entries->nextPageUrl())
                            <a href="{{ $entries->nextPageUrl() }}" class="mp-button mp-button--ghost">Older entries</a>
                        @else
                            <span></span>
                        @endif
                    </nav>
                @endif
            @else
                <section class="mp-empty-state" data-reveal>
                    <span class="mp-eyebrow">Archive is empty</span>
                    <h2>No published entries are available in this collection yet.</h2>
                    <p>As soon as new content is published, the archive grid and pagination will be populated automatically.</p>
                </section>
            @endif
        </div>
    </section>
@endsection
