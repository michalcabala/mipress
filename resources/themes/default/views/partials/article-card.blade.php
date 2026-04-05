@php
    $entryUrl = mipress_entry_url($entry);
    $isFeature = ($variant ?? 'default') === 'feature';
@endphp

@if ($entryUrl)
    <article @class([
        'group overflow-hidden rounded-3xl border bg-white/90 shadow-sm transition hover:-translate-y-0.5 hover:shadow-xl dark:bg-slate-900/90',
        'border-blue-200/70 shadow-blue-100/70 dark:border-blue-800/70 dark:shadow-none' => $isFeature,
        'border-slate-200 dark:border-slate-800' => ! $isFeature,
    ])>
        <a href="{{ url($entryUrl) }}" class="block overflow-hidden">
            @if ($entry->featuredImage?->url)
                <img src="{{ $entry->featuredImage->url }}" alt="{{ $entry->title }}" class="h-56 w-full object-cover transition duration-500 group-hover:scale-105 {{ $isFeature ? 'md:h-72' : '' }}">
            @else
                <span class="block h-56 w-full bg-linear-to-br from-blue-500 to-cyan-400 {{ $isFeature ? 'md:h-72' : '' }}"></span>
            @endif
        </a>

        <div class="p-6">
            @if (filled($entry->data['category'] ?? null))
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">{{ $entry->data['category'] }}</p>
            @endif

            <h3 class="mt-2 text-2xl font-semibold leading-tight text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">
                <a href="{{ url($entryUrl) }}">{{ $entry->title }}</a>
            </h3>

            <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $entry->getExcerpt() }}</p>

            <div class="mt-5 flex items-center gap-3 text-xs font-medium uppercase tracking-[0.16em] text-slate-500 dark:text-slate-400">
                @if ($entry->published_at)
                    <span>{{ $entry->published_at->format('d.m.Y') }}</span>
                @endif
                <span>{{ $entry->getReadingTimeMinutes() }} min čtení</span>
            </div>
        </div>
    </article>
@endif
