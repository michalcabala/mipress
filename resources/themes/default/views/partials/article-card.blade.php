@php
    $entryUrl = mipress_entry_url($entry);
    $isFeature = ($variant ?? 'default') === 'feature';
    $featuredImageUrl = mipress_media_url($entry->featuredImage, 'card');
@endphp

@if ($entryUrl)
    <article @class([
        'mp-article-card',
        'mp-article-card--feature' => $isFeature,
    ])>
        <a href="{{ url($entryUrl) }}" class="mp-article-card__media">
            @if ($featuredImageUrl)
                <img src="{{ $featuredImageUrl }}" alt="{{ $entry->title }}">
            @else
                <span class="mp-article-card__placeholder"></span>
            @endif
        </a>

        <div class="mp-article-card__body">
            <span class="mp-card-kicker">{{ $entry->data['category'] ?? ($entry->collection?->name ?? 'Entry') }}</span>

            <h3>
                <a href="{{ url($entryUrl) }}">{{ $entry->title }}</a>
            </h3>

            <p>{{ $entry->getExcerpt() }}</p>

            <div class="mp-article-card__meta">
                @if ($entry->published_at)
                    <span>{{ $entry->published_at->format('d.m.Y') }}</span>
                @endif

                <span>{{ $entry->getReadingTimeMinutes() }} min read</span>

                @if (filled($entry->author?->name))
                    <span>{{ $entry->author?->name }}</span>
                @endif
            </div>
        </div>
    </article>
@endif
