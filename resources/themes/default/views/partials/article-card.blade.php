@php
    $entryUrl = mipress_entry_url($entry);
    $modifier = ($variant ?? 'default') === 'feature' ? ' mp-article-card--feature' : '';
@endphp

@if ($entryUrl)
    <article class="mp-article-card{{ $modifier }}">
        <a href="{{ url($entryUrl) }}" class="mp-article-card__media">
            @if ($entry->featuredImage?->url)
                <img src="{{ $entry->featuredImage->url }}" alt="{{ $entry->title }}">
            @else
                <span class="mp-article-card__placeholder"></span>
            @endif
        </a>

        <div class="mp-article-card__body">
            @if (filled($entry->data['category'] ?? null))
                <p class="mp-eyebrow">{{ $entry->data['category'] }}</p>
            @endif

            <h3>
                <a href="{{ url($entryUrl) }}">{{ $entry->title }}</a>
            </h3>

            <p>{{ $entry->getExcerpt() }}</p>

            <div class="mp-article-card__meta">
                @if ($entry->published_at)
                    <span>{{ $entry->published_at->format('d.m.Y') }}</span>
                @endif
                <span>{{ $entry->getReadingTimeMinutes() }} min čtení</span>
            </div>
        </div>
    </article>
@endif
