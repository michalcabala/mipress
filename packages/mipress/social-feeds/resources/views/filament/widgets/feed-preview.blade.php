<x-filament-widgets::widget>
    <x-filament::section heading="Náhled feedu" icon="heroicon-o-eye" collapsible>
        @if($feed && $posts->isNotEmpty())
            <div class="sf-preview rounded-lg border border-gray-200 dark:border-gray-700 bg-white dark:bg-gray-900 p-4 overflow-auto max-h-[600px]">
                <div class="mb-3 flex items-center justify-between">
                    <span class="text-xs text-gray-500 dark:text-gray-400">
                        Layout: <strong>{{ $feed->layout->label() }}</strong> · {{ $posts->count() }} příspěvků
                    </span>
                    <span class="text-xs text-gray-400">
                        <code>&lt;x-social-feed slug="{{ $feed->slug }}" /&gt;</code>
                    </span>
                </div>

                <div class="sf-preview__content">
                    @foreach($posts->take($feed->posts_count ?? 5) as $post)
                        @php
                            $post = is_array($post) ? (object) $post : $post;
                            $media = is_array($post->media ?? null) ? $post->media : [];
                            $engagement = is_array($post->engagement ?? null) ? (object) $post->engagement : (object) [];
                        @endphp
                        <div class="sf-preview__post border-b border-gray-100 dark:border-gray-800 pb-4 mb-4 last:border-0 last:mb-0 last:pb-0">
                            {{-- Header --}}
                            <div class="flex items-center gap-2 mb-2">
                                @if($post->author_avatar_url ?? false)
                                    <img src="{{ $post->author_avatar_url }}" alt="" class="w-8 h-8 rounded-full">
                                @else
                                    <div class="w-8 h-8 rounded-full bg-gray-200 dark:bg-gray-700 flex items-center justify-center">
                                        <x-heroicon-s-user class="w-4 h-4 text-gray-400" />
                                    </div>
                                @endif
                                <div>
                                    <div class="font-medium text-sm text-gray-900 dark:text-gray-100">{{ $post->author_name ?? $feed->account->name }}</div>
                                    @if($post->posted_at ?? false)
                                        <time class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($post->posted_at)->diffForHumans() }}</time>
                                    @endif
                                </div>
                            </div>

                            {{-- Content --}}
                            @if($post->content ?? false)
                                <p class="text-sm text-gray-700 dark:text-gray-300 mb-2">{!! nl2br(e(\Illuminate\Support\Str::limit($post->content, 200))) !!}</p>
                            @endif

                            {{-- Media thumbnail --}}
                            @if(!empty($media))
                                <div class="flex gap-2 mb-2">
                                    @foreach(array_slice($media, 0, 3) as $item)
                                        <img src="{{ $item['url'] ?? '' }}" alt="" class="w-20 h-20 object-cover rounded" loading="lazy">
                                    @endforeach
                                    @if(count($media) > 3)
                                        <div class="w-20 h-20 rounded bg-gray-100 dark:bg-gray-800 flex items-center justify-center text-xs text-gray-500">+{{ count($media) - 3 }}</div>
                                    @endif
                                </div>
                            @endif

                            {{-- Engagement --}}
                            <div class="flex gap-3 text-xs text-gray-500">
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

                            {{-- Permalink --}}
                            @if($post->permalink ?? false)
                                <a href="{{ $post->permalink }}" target="_blank" rel="noopener" class="text-xs text-primary-600 hover:underline mt-1 inline-block">
                                    Zobrazit na {{ $feed->account->platform->label() }} →
                                </a>
                            @endif
                        </div>
                    @endforeach
                </div>
            </div>
        @elseif($feed)
            <div class="text-sm text-gray-500 dark:text-gray-400 py-8 text-center">
                <x-heroicon-o-inbox class="w-12 h-12 mx-auto mb-2 text-gray-300 dark:text-gray-600" />
                <p>Zatím žádné příspěvky.</p>
                <p class="text-xs mt-1">Klikněte na „Obnovit" pro načtení dat z API.</p>
            </div>
        @else
            <div class="text-sm text-gray-500 dark:text-gray-400 py-4 text-center">
                Nejprve uložte feed pro zobrazení náhledu.
            </div>
        @endif
    </x-filament::section>
</x-filament-widgets::widget>
