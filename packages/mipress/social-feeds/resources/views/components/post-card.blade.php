@php
    $post = is_array($post) ? (object) $post : $post;
    $media = is_array($post->media ?? null) ? $post->media : [];
    $engagement = is_array($post->engagement ?? null) ? (object) $post->engagement : (object) [];
    $platform = $feed->account->platform;
@endphp

<article class="sf-post sf-post--{{ $post->post_type ?? 'text' }}">
    {{-- Header --}}
    @if($post->author_name ?? false)
    <div class="sf-post__header flex items-center gap-3 mb-2">
        @if($post->author_avatar_url ?? false)
            <img src="{{ $post->author_avatar_url }}" alt="" class="w-10 h-10 rounded-full">
        @endif
        <div>
            <div class="font-semibold text-sm">{{ $post->author_name }}</div>
            @if($post->posted_at ?? false)
                <time class="text-xs text-gray-500" datetime="{{ $post->posted_at }}">
                    {{ \Carbon\Carbon::parse($post->posted_at)->diffForHumans() }}
                </time>
            @endif
        </div>
    </div>
    @endif

    {{-- Obsah --}}
    @if($post->content ?? false)
    <div class="sf-post__content mb-3">
        <p>{!! nl2br(e(Str::limit($post->content, 300))) !!}</p>
    </div>
    @endif

    {{-- Média --}}
    @if(! empty($media))
    <div class="sf-post__media mb-3">
        @foreach(array_slice($media, 0, 4) as $item)
            @if(($item['type'] ?? '') === 'video')
                <video src="{{ $item['url'] }}" poster="{{ $item['thumbnail_url'] ?? '' }}" controls class="w-full rounded"></video>
            @else
                <img src="{{ $item['url'] }}" alt="" class="w-full rounded" loading="lazy">
            @endif
        @endforeach
    </div>
    @endif

    {{-- Engagement --}}
    <div class="sf-post__engagement flex gap-4 text-sm text-gray-500">
        @if(($engagement->reactions ?? 0) > 0 || ($engagement->likes ?? 0) > 0)
            <span>👍 {{ $engagement->reactions ?? $engagement->likes ?? 0 }}</span>
        @endif
        @if(($engagement->comments ?? 0) > 0)
            <span>💬 {{ $engagement->comments }}</span>
        @endif
        @if(($engagement->shares ?? 0) > 0)
            <span>↗️ {{ $engagement->shares }}</span>
        @endif
    </div>

    {{-- Odkaz na originál --}}
    @if($post->permalink ?? false)
    <a href="{{ $post->permalink }}" target="_blank" rel="noopener"
       class="sf-post__link text-sm mt-2 inline-block hover:underline"
       style="color: {{ $platform->color() }}">
        Zobrazit na {{ $platform->label() }} →
    </a>
    @endif
</article>
