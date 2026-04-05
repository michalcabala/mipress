@php
    $columns = $feed->settings['columns'] ?? 3;
@endphp
<div class="sf-grid grid gap-4" style="grid-template-columns: repeat({{ $columns }}, 1fr);">
    @foreach($posts as $post)
        @include('social-feeds::components.post-card', ['post' => $post, 'feed' => $feed])
    @endforeach
</div>
