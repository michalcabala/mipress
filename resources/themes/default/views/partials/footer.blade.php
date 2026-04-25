@php
    $collections = mipress_public_collections();
    $adminUrl = url('/'.trim((string) config('mipress.admin_path', 'mpcp'), '/'));
@endphp

<footer class="mp-site-footer">
    <div class="mp-container mp-footer-grid">
        <div class="mp-footer-copy">
            <span class="mp-footer-heading">MiPress theme</span>
            <h2>Composable CMS frontend with SaaS energy built in.</h2>
            <p>
                This default experience is designed to present MiPress as a modern publishing platform:
                marketing homepage, public pages, content collections, Mason-driven storytelling and
                a dark mode that feels native instead of bolted on.
            </p>
        </div>

        <div class="mp-footer-card">
            <span class="mp-footer-heading">Explore</span>
            <ul class="mp-footer-links">
                <li><a href="{{ url('/') }}">Home</a></li>
                @foreach ($collections as $collection)
                    @if (filled($path = mipress_collection_archive_path($collection)))
                        <li><a href="{{ url($path) }}">{{ $collection->name }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>

        <div class="mp-footer-card">
            <span class="mp-footer-heading">Operations</span>
            <ul class="mp-footer-links">
                <li><a href="{{ $adminUrl }}">Open admin</a></li>
                <li><span>Active theme: Default</span></li>
                <li><span>SEO-ready metadata and structured data included.</span></li>
                <li><span>&copy; {{ date('Y') }} {{ $siteName }}</span></li>
            </ul>
        </div>
    </div>
</footer>
