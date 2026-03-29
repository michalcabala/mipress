@php
    $collections = mipress_public_collections();
@endphp

<header class="mp-site-header">
    <div class="mp-container mp-header-inner">
        <a href="{{ url('/') }}" class="mp-brand" aria-label="{{ config('app.name') }}">
            <span class="mp-brand-mark">MP</span>
            <span>
                <strong>{{ config('app.name') }}</strong>
                <small>Editorial skeleton</small>
            </span>
        </a>

        <button
            type="button"
            class="mp-menu-toggle"
            data-menu-toggle
            aria-controls="site-navigation"
            aria-expanded="false"
        >
            Menu
        </button>

        <div class="mp-header-actions" id="site-navigation" data-site-menu>
            <nav class="mp-site-nav" aria-label="Hlavní navigace">
                <a href="{{ url('/') }}" @class(['is-active' => request()->url() === url('/')])>
                    Domů
                </a>

                @foreach ($collections as $collection)
                    @php
                        $archivePath = mipress_collection_archive_path($collection);
                        $isActive = $archivePath && request()->is(ltrim($archivePath, '/').'*');
                    @endphp

                    @if ($archivePath)
                        <a href="{{ url($archivePath) }}" @class(['is-active' => $isActive])>
                            {{ $collection->name }}
                        </a>
                    @endif
                @endforeach
            </nav>

            <div class="mp-mode-toggle" role="group" aria-label="Přepínač vzhledu">
                <button type="button" class="mp-mode-button" data-theme-option="light">
                    Světlý
                </button>
                <button type="button" class="mp-mode-button" data-theme-option="dark">
                    Tmavý
                </button>
                <button type="button" class="mp-mode-button" data-theme-option="system">
                    Systém
                </button>
            </div>
        </div>
    </div>
</header>
