<div class="sf-list space-y-4">
    @foreach($posts as $post)
        @include('social-feeds::components.post-card', ['post' => $post, 'feed' => $feed])
    @endforeach
</div>
