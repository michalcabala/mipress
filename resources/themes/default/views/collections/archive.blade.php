@extends('layouts.app')

@section('title', $collection->name.' | '.config('app.name'))
@section('meta_description', 'Archiv kolekce '.$collection->name.' ve výchozí miPress šabloně.')

@section('content')
    <section class="mp-archive-hero">
        <div class="mp-container mp-archive-hero__grid">
            <div>
                <p class="mp-eyebrow">Archiv</p>
                <h1 class="mp-display">{{ $collection->name }}</h1>
                <p class="mp-hero-copy">
                    Výpis je stavěný pro editorial obsah. Podporuje feature kartu, přehledné metainformace
                    i stránkování bez závislosti na Tailwind komponentách.
                </p>
            </div>

            <div class="mp-archive-summary">
                <span>{{ $entries->total() }} publikovaných položek</span>
                <span>Řazení: {{ $collection->dated ? 'nejnovější nahoře' : 'vlastní pořadí' }}</span>
                <span>Route: {{ $collection->route }}</span>
            </div>
        </div>
    </section>

    <section class="mp-section">
        <div class="mp-container">
            @if ($entries->count() > 0)
                <div class="mp-article-grid">
                    @foreach ($entries as $entry)
                        @include('partials.article-card', [
                            'entry' => $entry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>

                @if ($entries->hasPages())
                    <nav class="mp-pagination" aria-label="Stránkování archivu">
                        @if ($entries->previousPageUrl())
                            <a href="{{ $entries->previousPageUrl() }}" class="mp-button mp-button--ghost">
                                Novější položky
                            </a>
                        @else
                            <span></span>
                        @endif

                        <span>Strana {{ $entries->currentPage() }} z {{ $entries->lastPage() }}</span>

                        @if ($entries->nextPageUrl())
                            <a href="{{ $entries->nextPageUrl() }}" class="mp-button mp-button--ghost">
                                Starší položky
                            </a>
                        @endif
                    </nav>
                @endif
            @else
                <div class="mp-empty-state">
                    <p class="mp-eyebrow">Archiv je prázdný</p>
                    <h2>Kolekce zatím neobsahuje žádný publikovaný obsah.</h2>
                    <p>
                        Jakmile publikujete první položku, objeví se zde zvýrazněná feature karta i celý
                        seznam článků.
                    </p>
                </div>
            @endif
        </div>
    </section>
@endsection
