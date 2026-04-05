@if($posts->isNotEmpty())
<div class="sf-feed sf-feed--{{ $feed->layout->value }}"
     data-feed-id="{{ $feed->id }}"
     data-platform="{{ $feed->account->platform->value }}">

    @include("social-feeds::components.layouts.{$feed->layout->value}", [
        'posts' => $posts,
        'feed' => $feed,
    ])
</div>
@endif
