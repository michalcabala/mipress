@php
    $columns = $feed->settings['columns'] ?? 3;
@endphp
<div class="sf-masonry columns-{{ $columns }} gap-4">
    @foreach($posts as $post)
        <div class="break-inside-avoid mb-4">
            @include('social-feeds::components.post-card', ['post' => $post, 'feed' => $feed])
        </div>
    @endforeach
</div>
