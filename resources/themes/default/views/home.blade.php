@extends('layouts.app')

@section('title', config('app.name').' | Prezentační web')
@section('meta_description', 'Výchozí prezentační šablona miPress s moderním blue designem, Mason bricks a obsahovými sekcemi.')

@section('content')
    <section class="pt-16 sm:pt-20">
        <div class="mx-auto grid w-full max-w-7xl items-start gap-10 px-4 sm:px-6 lg:grid-cols-[1.2fr_0.8fr] lg:px-8">
            <div>
                <p class="inline-flex items-center gap-2 rounded-full border border-blue-200 bg-blue-50 px-4 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 dark:border-blue-800 dark:bg-blue-950/60 dark:text-blue-200">
                    Výchozí miPress šablona
                </p>
                <h1 class="mt-6 max-w-[14ch] text-4xl font-semibold leading-tight text-slate-900 sm:text-5xl lg:text-6xl dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">
                    Prezentační web pro moderní obsahové projekty.
                </h1>
                <p class="mt-6 max-w-2xl text-base leading-8 text-slate-600 dark:text-slate-300">
                    Téma staví na modré vizuální identitě, čisté typografii, Mason brick skladbě obsahu
                    a připravených stránkách pro homepage, archiv i detail článku.
                </p>

                <div class="mt-8 flex flex-wrap items-center gap-3">
                    @if (($featuredEntries ?? collect())->isNotEmpty())
                        @php $firstEntryUrl = mipress_entry_url($featuredEntries->first()); @endphp
                        @if ($firstEntryUrl)
                            <a href="{{ url($firstEntryUrl) }}" class="inline-flex items-center rounded-xl bg-blue-600 px-5 py-3 text-sm font-semibold text-white shadow-lg shadow-blue-500/25 transition hover:bg-blue-700">
                                Otevřít ukázkový obsah
                            </a>
                        @endif
                    @endif

                    <a href="{{ url('/admin') }}" class="inline-flex items-center rounded-xl border border-slate-300 bg-white px-5 py-3 text-sm font-semibold text-slate-700 transition hover:border-blue-300 hover:text-blue-700 dark:border-slate-700 dark:bg-slate-900 dark:text-slate-200 dark:hover:border-blue-600 dark:hover:text-blue-300">
                        Otevřít administraci
                    </a>
                </div>
            </div>

            <div class="grid gap-4">
                <article class="rounded-3xl border border-slate-200 bg-white/90 p-6 shadow-sm dark:border-slate-800 dark:bg-slate-900/85">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Struktura</p>
                    <h2 class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Landing, archiv i detail sdílí jeden design systém.</h2>
                    <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Komponenty jsou sjednocené, aby web působil konzistentně napříč obsahem,
                        sbírkami i Mason bricks.
                    </p>
                </article>

                <article class="rounded-3xl border border-blue-200 bg-linear-to-br from-blue-600 to-cyan-500 p-6 text-white shadow-lg shadow-blue-500/25 dark:border-blue-700">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-100">Mason</p>
                    <h2 class="mt-3 text-2xl font-semibold" style="font-family: 'Space Grotesk', sans-serif;">Bricky jsou registrované přes core kolekci a externí balíčky.</h2>
                    <p class="mt-3 text-sm leading-7 text-blue-50/95">
                        Narrative, Quote, Insight Grid, CTA i Social Feed jsou připravené pro produkční použití bez dalších kroků.
                    </p>
                </article>
            </div>
        </div>
    </section>

    <section class="mt-20">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Obsahové scénáře</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Co je připravené hned po instalaci</h2>
                <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                    Výchozí setup pokrývá stránky, články i prezentační sekce. Archiv i detail obsahují
                    typografii a komponenty připravené pro reálné nasazení.
                </p>
            </div>

            <div class="mt-8 grid gap-4 md:grid-cols-3">
                <article class="rounded-2xl border border-slate-200 bg-white/85 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <strong class="text-lg text-slate-900 dark:text-white">Pages</strong>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Landing pages, nabídky služeb, reference i obecné stránky webu.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white/85 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <strong class="text-lg text-slate-900 dark:text-white">Articles</strong>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Článkový tok s metadaty, Mason obsahem a souvisejícími položkami.</p>
                </article>
                <article class="rounded-2xl border border-slate-200 bg-white/85 p-5 shadow-sm dark:border-slate-800 dark:bg-slate-900/80">
                    <strong class="text-lg text-slate-900 dark:text-white">Theme mode</strong>
                    <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">Světlý, tmavý a systémový mód napříč frontendem i admin preview.</p>
                </article>
            </div>
        </div>
    </section>

    <section class="mt-20">
        <div class="mx-auto w-full max-w-7xl px-4 sm:px-6 lg:px-8">
            <div class="max-w-3xl">
                <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Nejnovější obsah</p>
                <h2 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Karty pro konzistentní obsahový rytmus</h2>
                <p class="mt-4 text-base leading-8 text-slate-600 dark:text-slate-300">
                    První článek může být zvýrazněný, zbytek tvoří flexibilní síť vhodnou pro novinky i case studies.
                </p>
            </div>

            @if (($featuredEntries ?? collect())->isNotEmpty())
                <div class="mt-8 grid gap-5 md:grid-cols-2 xl:grid-cols-3">
                    @foreach ($featuredEntries as $featuredEntry)
                        @include('partials.article-card', [
                            'entry' => $featuredEntry,
                            'variant' => $loop->first ? 'feature' : 'default',
                        ])
                    @endforeach
                </div>
            @else
                <div class="mt-8 rounded-3xl border border-dashed border-slate-300 bg-white/80 p-10 text-center dark:border-slate-700 dark:bg-slate-900/70">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-600 dark:text-blue-300">Zatím bez publikovaného obsahu</p>
                    <h2 class="mt-3 text-2xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Vytvoř první stránku nebo článek a nastav homepage v administraci.</h2>
                    <p class="mx-auto mt-4 max-w-2xl text-sm leading-7 text-slate-600 dark:text-slate-300">
                        Design je připravený i bez dat. Jakmile publikuješ první obsah, karty, archiv i detail se doplní automaticky.
                    </p>
                </div>
            @endif
        </div>
    </section>

    @if (($collections ?? collect())->isNotEmpty())
        <section class="mt-20 pb-20">
            <div class="mx-auto w-full max-w-7xl rounded-3xl border border-blue-200 bg-blue-50/65 px-4 py-10 sm:px-6 lg:px-8 dark:border-blue-900/60 dark:bg-blue-950/30">
                <div class="max-w-3xl">
                    <p class="text-xs font-semibold uppercase tracking-[0.2em] text-blue-700 dark:text-blue-300">Veřejné sekce</p>
                    <h2 class="mt-3 text-3xl font-semibold text-slate-900 dark:text-white" style="font-family: 'Space Grotesk', sans-serif;">Navigace je odvozená z kolekcí s veřejným archivem</h2>
                </div>

                <div class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-3">
                    @foreach ($collections as $collection)
                        @if (filled($archivePath = mipress_collection_archive_path($collection)))
                            <a href="{{ url($archivePath) }}" class="rounded-2xl border border-blue-200 bg-white/90 p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-blue-400 hover:shadow-md dark:border-blue-800 dark:bg-slate-900/80 dark:hover:border-blue-500">
                                <strong class="text-slate-900 dark:text-white">{{ $collection->name }}</strong>
                                <span class="mt-2 block text-sm text-slate-600 dark:text-slate-300">{{ $archivePath }}</span>
                            </a>
                        @endif
                    @endforeach
                </div>
            </div>
        </section>
    @endif
@endsection
