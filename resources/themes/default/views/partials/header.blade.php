@php
    $collections = mipress_public_collections();
    $currentUrl = url()->current();
    $adminUrl = url('/'.trim((string) config('mipress.admin_path', 'mpcp'), '/'));
@endphp

<header class="mp-site-header">
    <div class="mp-container mp-header-inner">
        <a href="{{ url('/') }}" class="mp-brand" aria-label="{{ $siteName }}">
            <span class="mp-brand-mark">MP</span>
            <span class="mp-brand-copy">
                <strong>{{ $siteName }}</strong>
                <small>SaaS publishing experience</small>
            </span>
        </a>

        <nav class="mp-desktop-nav" aria-label="Primary navigation">
            <a href="{{ url('/') }}" @class(['is-active' => $currentUrl === url('/')])>Home</a>

            @foreach ($collections as $collection)
                @if (filled($archivePath = mipress_collection_archive_path($collection)))
                    <a href="{{ url($archivePath) }}" @class([
                        'is-active' => request()->is(ltrim($archivePath, '/').'*'),
                    ])>{{ $collection->name }}</a>
                @endif
            @endforeach
        </nav>

        <div class="mp-header-actions">
            <div class="mp-theme-toggle" role="group" aria-label="Color mode">
                <button type="button" class="mp-theme-button" data-theme-option="light">Light</button>
                <button type="button" class="mp-theme-button" data-theme-option="dark">Dark</button>
                <button type="button" class="mp-theme-button" data-theme-option="system">Auto</button>
            </div>

            <a href="{{ $adminUrl }}" class="mp-button mp-button--ghost mp-header-link">Open admin</a>

            <button
                type="button"
                class="mp-menu-button"
                data-menu-open
                aria-controls="mobile-navigation"
                aria-expanded="false"
            >
                Menu
            </button>
        </div>
    </div>
</header>

<div class="mp-mobile-overlay" id="mobile-navigation" data-mobile-overlay hidden aria-hidden="true">
    <div class="mp-mobile-overlay__backdrop" data-menu-close></div>
    <div class="mp-mobile-overlay__panel">
        <div class="mp-mobile-overlay__header">
            <span class="mp-mobile-overlay__eyebrow">Navigation</span>
            <button type="button" class="mp-mobile-overlay__close" data-menu-close aria-label="Close navigation">
                Close
            </button>
        </div>

        <nav class="mp-mobile-nav" aria-label="Mobile navigation">
            <a href="{{ url('/') }}">Home</a>

            @foreach ($collections as $collection)
                @if (filled($archivePath = mipress_collection_archive_path($collection)))
                    <a href="{{ url($archivePath) }}">{{ $collection->name }}</a>
                @endif
            @endforeach
        </nav>

        <div class="mp-mobile-overlay__footer">
            <div class="mp-theme-toggle mp-theme-toggle--overlay" role="group" aria-label="Color mode">
                <button type="button" class="mp-theme-button" data-theme-option="light">Light</button>
                <button type="button" class="mp-theme-button" data-theme-option="dark">Dark</button>
                <button type="button" class="mp-theme-button" data-theme-option="system">Auto</button>
            </div>

            <a href="{{ $adminUrl }}" class="mp-button mp-button--primary">Go to admin</a>
        </div>
    </div>
</div>
