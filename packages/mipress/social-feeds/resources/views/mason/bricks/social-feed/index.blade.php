<section class="mp-brick mp-brick--social-feed">
    <div class="mp-brick__container">
        @if(filled($heading))
            <div class="mp-section-heading">
                <h2 class="mp-brick__heading">{{ $heading }}</h2>
            </div>
        @endif

        @if($posts->isNotEmpty())
            <div class="sf-feed sf-feed--{{ $feed->layout->value }}"
                 data-feed-id="{{ $feed->id }}"
                 data-platform="{{ $feed->account?->platform->value }}">

                @include("social-feeds::components.layouts.{$feed->layout->value}", [
                    'posts' => $posts,
                    'feed' => $feed,
                ])
            </div>
        @else
            <p class="mp-brick__empty">Žádné příspěvky k zobrazení.</p>
        @endif
    </div>
</section>
