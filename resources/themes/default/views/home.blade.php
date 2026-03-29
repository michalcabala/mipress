@extends('layouts.app')

@section('title', config('app.name').' | Editorial skeleton')
@section('meta_description', 'Výchozí public theme pro MiPress se silnou typografií, článkovým archivem a Mason obsahem.')

@section('content')
    <section class="mp-home-hero">
        <div class="mp-container mp-home-hero__grid">
            <div>
                <p class="mp-eyebrow">Výchozí miPress šablona</p>
                <h1 class="mp-display">
                    Public theme pro magazín, journal i obsahové stránky bez kompromisu v editoru.
                </h1>
                <p class="mp-hero-copy">
                    Skeleton stojí na výrazné titulkové typografii, čistém archivu článků, Mason bricks
                    pro dlouhý obsah a trojstavovém přepínači světlého, tmavého i systémového režimu.
                </p>

                <div class="mp-button-row">
                    @if (($featuredEntries ?? collect())->isNotEmpty())
                        @php $firstEntryUrl = mipress_entry_url($featuredEntries->first()); @endphp
                        @if ($firstEntryUrl)
                            <a href="{{ url($firstEntryUrl) }}" class="mp-button mp-button--primary">
                                Otevřít ukázkový obsah
                            </a>
                        @endif
                    @endif

                    <a href="{{ url('/admin') }}" class="mp-button mp-button--ghost">
                        Otevřít administraci
                    </a>
                </div>
            </div>

            <div class="mp-home-hero__stack">
                <article class="mp-feature-panel">
                    <p class="mp-eyebrow">Struktura</p>
                    <h2>Home, archiv a single článek používají stejný design system.</h2>
                    <p>
                        Navigace, karty, prose sazba i CTA bloky mají sdílené proměnné a stejné chování
                        v light i dark módu.
                    </p>
                </article>

                <article class="mp-feature-panel mp-feature-panel--accent">
                    <p class="mp-eyebrow">Mason</p>
                    <h2>Obsah se skládá z bricks v `core`, šablona zůstává v `resources/themes`.</h2>
                    <p>
                        Oddělení je čisté: reusable editor a render logika v balíčku, výchozí vizuální
                        identita v skeletonu aplikace.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="mp-section">
        <div class="mp-container">
            <div class="mp-section-heading">
                <p class="mp-eyebrow">Obsahové scénáře</p>
                <h2>Co je připravené hned po instalaci</h2>
                <p>
                    Seeder založí blueprinty pro stránky i články. Články používají archiv `/journal`
                    a single route `/journal/{slug}`, stránky běží samostatně na `/{slug}`.
                </p>
            </div>

            <div class="mp-pillars">
                <article class="mp-pillar">
                    <strong>Pages</strong>
                    <p>Landing pages, manifesto, about page nebo homepage vybraná v nastavení tématu.</p>
                </article>
                <article class="mp-pillar">
                    <strong>Articles</strong>
                    <p>Článkový tok s rubrikou, perexem, Mason obsahem a čtecím časem.</p>
                </article>
                <article class="mp-pillar">
                    <strong>Theme mode</strong>
                    <p>Světlý, tmavý i systémový mód se stejným chováním na webu i v Mason preview.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="mp-section">
        <div class="mp-container">
            <div class="mp-section-heading">
                <p class="mp-eyebrow">Nejnovější obsah</p>
                <h2>Archivové karty připravené pro magazínový rytmus</h2>
                <p>
                    Výchozí zobrazení dává prvnímu článku větší důraz a zbytek skládá do konzistentní mřížky.
                </p>
            </div>

            @if (($featuredEntries ?? collect())->isNotEmpty())
                <div class="mp-article-grid">
                    @foreach ($featuredEntries as $featuredEntry)
                        @include('partials.article-card', [
                            'entry' => $featuredEntry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>
            @else
                <div class="mp-empty-state">
                    <p class="mp-eyebrow">Zatím bez publikovaného obsahu</p>
                    <h2>Vytvoř první stránku nebo článek a nastav homepage v administraci.</h2>
                    <p>
                        Design je připravený i bez dat. Jakmile publikuješ první obsah, karty, archiv i
                        single article se doplní automaticky.
                    </p>
                </div>
            @endif
        </div>
    </section>

    @if (($collections ?? collect())->isNotEmpty())
        <section class="mp-section mp-section--soft">
            <div class="mp-container">
                <div class="mp-section-heading">
                    <p class="mp-eyebrow">Veřejné sekce</p>
                    <h2>Navigace je odvozená z kolekcí s veřejným archivem</h2>
                </div>

                <div class="mp-collection-grid">
                    @foreach ($collections as $collection)
                        @if (filled($archivePath = mipress_collection_archive_path($collection)))
                            <a href="{{ url($archivePath) }}" class="mp-collection-card">
                                <strong>{{ $collection->name }}</strong>
                                <span>{{ $archivePath }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
