@php
    $collections = mipress_public_collections();
@endphp

<footer class="mp-site-footer">
    <div class="mp-container mp-footer-grid">
        <div class="mp-footer-copy">
            <p class="mp-eyebrow">MiPress Default</p>
            <h2>Jasný výchozí skeleton pro magazín, journal i obsahové landing pages.</h2>
            <p>
                Výchozí téma staví na výrazné typografii, Mason bricks a přepínání světlého, tmavého
                i systémového režimu bez závislosti na build pipeline tématu.
            </p>
        </div>

        <div>
            <p class="mp-footer-heading">Sekce</p>
            <ul class="mp-footer-links">
                <li><a href="{{ url('/') }}">Domů</a></li>
                @foreach ($collections as $collection)
                    @if (filled($path = mipress_collection_archive_path($collection)))
                        <li><a href="{{ url($path) }}">{{ $collection->name }}</a></li>
                    @endif
                @endforeach
            </ul>
        </div>

        <div>
            <p class="mp-footer-heading">Provoz</p>
            <ul class="mp-footer-links">
                <li><a href="{{ url('/admin') }}">Administrace</a></li>
                <li><span>Aktivní téma: Default</span></li>
                <li><span>&copy; {{ date('Y') }} {{ config('app.name') }}</span></li>
            </ul>
        </div>
    </div>
</footer>
