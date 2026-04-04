# miPress — Kompletní analýza projektu & Roadmap k produkci

> **Datum:** 4. dubna 2026
> **Scope:** Celý projekt — `packages/mipress/core`, `packages/mipress/forms`, app skeleton, testy
> **Zaměření:** Backend (frontend šablona je mimo scope)

---

## 1. PŘEHLED PROJEKTU

**miPress** je modulární CMS budovaný jako **Laravel package** (`mipress/core` + `mipress/forms`) instalovaný přes Composer do Laravel 13 skeleton projektu. Cílí na české weby malých firem s admin panelem ve Filamentu 5.

### Tech Stack
| Technologie | Verze | Stav |
|-------------|-------|------|
| PHP | 8.4 | ✅ Aktuální |
| Laravel | 13 | ✅ Aktuální |
| Filament | 5 | ✅ Aktuální |
| Livewire | 4 | ✅ Aktuální |
| Tailwind CSS | 4 | ✅ Aktuální |
| Pest PHP | 4 | ✅ Aktuální |
| Mason (content blocks) | 3 | ✅ Aktuální |
| Curator (media) | 5 | ✅ Aktuální |
| Spatie Permission | 7 | ✅ Aktuální |

---

## 2. ARCHITEKTURA — CO JE HOTOVO

### 2.1 Modely (10 v core + 5 ve forms)

| Model | Tabulka | Traity | Stav |
|-------|---------|--------|------|
| **Entry** | `entries` | Auditable, HasFactory, HasRevisions, HasSeo, HasSlug, HasWorkflow, SoftDeletes | ✅ Plně funkční |
| **Page** | `pages` | Auditable, HasFactory, HasRevisions, HasSeo, HasSlug, HasWorkflow, SoftDeletes | ✅ Funkční |
| **Blueprint** | `blueprints` | HasFactory | ✅ Funkční |
| **Collection** | `collections` | HasFactory | ✅ Funkční |
| **Taxonomy** | `taxonomies` | HasFactory, SoftDeletes | ✅ Implementováno |
| **Term** | `terms` | HasFactory, HasSlug, SoftDeletes | ✅ Implementováno |
| **Revision** | `revisions` | — | ✅ Implementováno |
| **AuditLog** | `audit_logs` | — | ✅ Funkční |
| **GlobalSet** | `global_sets` | HasFactory | ✅ Funkční |
| **Setting** | `settings` | — | ✅ Funkční |

**Forms package modely:** Form, FormField, FormSubmission, FormSubmissionAttachment, FormNotificationSetting — všechny ✅ funkční.

### 2.2 Migrace

- **Core:** 22 migrací (blueprints, collections, entries, pages, taxonomies, terms, revisions, audit_logs, global_sets, settings, notifications + pivot tabulky + SEO/content sloupce)
- **Forms:** 6 migrací (forms, form_submissions, attachments, notifications)
- **App-level:** 5 migrací (users, cache, jobs, permissions, curator)
- **Celkem: 33 migrací** — logicky seřazené, chronologické

### 2.3 Enumy

| Enum | Hodnoty | Použití |
|------|---------|---------|
| `EntryStatus` | Draft, InReview, Published, Scheduled, Rejected | Entry & Page status workflow |
| `UserRole` | SuperAdmin, Admin, Editor, Contributor | Role-based access control |
| `SpamProtectionMode` (forms) | — | Anti-spam konfigurace |

### 2.4 Traity (5)

| Trait | Účel | Použito v |
|-------|------|-----------|
| `Auditable` | Automatický audit trail (created/updated/deleted/restored) | Entry, Page |
| `HasRoles` | Rozšíření Spatie HasRoles s helpery (isSuperAdmin, canAccessPanel) | User |
| `HasWorkflow` | Scopy (published, draft, scheduled), akce (publish, unpublish, submitForReview) | Entry, Page |
| `HasSeo` | SEO form schema, getSeoTitle/Description helpery | Entry, Page |
| `HasRevisions` | Content snapshots, restore, diff, resolvePublicVersion | Entry, Page |

### 2.5 Services (5)

| Service | Typ | Účel |
|---------|-----|------|
| `GlobalSetManager` | Singleton | Lazy-loading GlobalSet dat, helper `global_set()` |
| `BlueprintFieldResolver` | Singleton | Mapování Blueprint field types → Filament komponenty |
| `CurationGenerator` | Singleton | Generování media curations (thumbnail, medium, large, og) |
| `MediaCurationOrchestrator` | Singleton | Orchestrace media zpracování |
| `MediaPathGenerator` | Singleton | Generování storage paths pro media |

### 2.6 Filament Resources (9 v core + 2 ve forms)

| Resource | Model | Navigace | Stav |
|----------|-------|----------|------|
| **EntryResource** | Entry | Dynamicky per-collection v "Obsah" | ✅ Plně funkční, workflow, revize |
| **PageResource** | Page | Obsah / Stránky | ✅ Funkční |
| **BlueprintResource** | Blueprint | Nastavení / Šablony | ✅ Funkční |
| **CollectionResource** | Collection | Nastavení / Sekce | ✅ Funkční |
| **TaxonomyResource** | Taxonomy | Nastavení / Třídění | ✅ Implementováno |
| **TermResource** | Term | Nastavení / Štítky | ✅ Implementováno |
| **GlobalSetResource** | GlobalSet | Nastavení / Globální sady | ✅ Funkční |
| **MediaResource** | — | Curator integrovaný | ✅ Via Curator |
| **UserResource** | User | Uživatelé | ✅ Funkční |
| **FormResource** (forms pkg) | Form | Formuláře | ✅ Funkční |
| **FormSubmissionResource** (forms pkg) | FormSubmission | Odeslané formuláře | ✅ Funkční |

### 2.7 Policies (8)

Kompletní RBAC přes Spatie Permission pro všechny content modely. Matice oprávnění:

| Akce | SuperAdmin | Admin | Editor | Contributor |
|------|------------|-------|--------|-------------|
| viewAny / view | ✅ | ✅ | ✅ | ✅ |
| create | ✅ | ✅ | ✅ | ✅ (vlastní) |
| update | ✅ | ✅ | ✅ | ✅ (vlastní Draft/Rejected) |
| delete | ✅ | ✅ | ❌ | ❌ |
| forceDelete / restore | ✅ | ✅ | ❌ | ❌ |
| publish | ✅ | ✅ | ✅ | ❌ |

### 2.8 Mason Bricks (5)

- **NarrativeBrick** — narativní textový blok
- **CallToActionBrick** — CTA sekce
- **InsightGridBrick** — mřížka postřehů
- **PullQuoteBrick** — citát
- **FormBrick** (forms pkg) — vložení formuláře do obsahu

### 2.9 Controllers & Routes

| Route | Controller | Účel |
|-------|-----------|------|
| `GET /` | EntryController@home | Homepage (konfigurovaná stránka/entry nebo theme landing) |
| `GET /theme-files/{theme}/{path}` | EntryController@asset | Servírování theme souborů (CSS/JS/fonty) |
| `GET /preview/{entry}` | PreviewController | Signed preview pro nepublikovaný obsah |
| `GET /preview/page/{page}` | PagePreviewController | Signed preview pro stránky |
| `GET /{path}` | EntryController@show | Catch-all CMS routing (kolekce, archivy, stránky) |

### 2.10 Theme System

- `ThemeManager` — discovery, aktivace, view resolution, caching
- Podpora vlastních theme v `resources/themes/{slug}/`
- Asset serving s bezpečnostními kontrolami (path traversal prevention)
- Dark mode podpora

### 2.11 Artisan Commands (2)

- `PublishScheduledEntries` — publikuje naplánované entries v čase scheduled_at
- `PublishThemeAssets` — publikuje theme assety do public/

### 2.12 Forms Package

Kompletní formulářový systém:
- Definice formulářů s konfigurovatelným polem
- Příjem a ukládání submissions s přílohami
- Spam ochrana (honeypot, rate limiting, hCaptcha)
- Email notifikace (příjemci + auto-reply)
- Mason brick pro vkládání formulářů do obsahu
- SoftDeletes na všech modelech

---

## 3. KVALITA KÓDU — HODNOCENÍ

### 3.1 Architektura: 8/10

**Silné stránky:**
- ✅ Čistá package architektura (ne monolitická app)
- ✅ Oddělené balíčky (core + forms)
- ✅ Správné použití Traits pro cross-cutting concerns
- ✅ Service layer pro složitější logiku (BlueprintFieldResolver, GlobalSetManager)
- ✅ Morph relationships pro audit logs a revize (škálovatelné)
- ✅ Event-driven audit logging (boot hooks na modelech)
- ✅ Enums místo magic strings

**Slabé stránky:**
- ⚠ Chybí `config/mipress.php` — žádná konfigurace publikovatelná do host aplikace
- ⚠ Některé duplikace mezi EntryForm a PageForm (SEO sekce, workflow akce)

### 3.2 Datový model: 7.5/10

**Silné stránky:**
- ✅ Správné FK constraints s cascade delete
- ✅ JSON sloupce pro flexibilní data (Blueprint fields, Entry data, GlobaSet data)
- ✅ Multijazyčnost připravena (origin_id + locale)
- ✅ Hierarchie (parent_id na Entry, Page, Term)
- ✅ Pivot tabulky pro M:N (collection_taxonomy, entry_term)
- ✅ SEO sloupce na úrovni DB (meta_title, meta_description reálné sloupce)

**Slabé stránky:**
- ⚠ Slug uniqueness nezahrnuje locale (`UNIQUE(collection_id, slug)` místo `UNIQUE(collection_id, locale, slug)`)
- ⚠ Column naming: `name` místo `title` na Blueprint a Collection (nesoulad se spec)

### 3.3 Filament UI: 8/10

**Silné stránky:**
- ✅ Dynamická navigace per-collection (EntryResource)
- ✅ Badge s počtem entries ke schválení
- ✅ Kompletní workflow akce (publish, unpublish, submitForReview, reject)
- ✅ Revision comparison s diff UI
- ✅ Blueprint-driven formuláře (dynamické renderování polí podle šablony)
- ✅ CuratorPicker pro media
- ✅ Filtrování, řazení, vyhledávání na tabulkách
- ✅ Trash/restore (SoftDeletes)

**Slabé stránky:**
- ⚠ Page form nemá parent_id selector (hierarchie není plně v UI)
- ⚠ Taxonomy pickery v Entry sidebar zatím nejsou vidět (závisí na collection→taxonomy vazbě)

### 3.4 Bezpečnost: 8.5/10

**Silné stránky:**
- ✅ Signed URLs pro preview nepublikovaného obsahu
- ✅ RBAC přes Spatie Permission na každém resource
- ✅ SuperAdmin ochrana (nelze smazat, nelze vytvořit druhého)
- ✅ Path traversal prevention v theme asset servingu
- ✅ Honeypot + rate limiting na formulářích
- ✅ SuperAdmin guard na GlobalSet resource
- ✅ CSRF ochrana (Laravel default)
- ✅ Audit trail na vše

**Potenciální rizika:**
- ⚠ Chybí validace parent_id circular reference (stránka→rodič→sebe sama)
- ⚠ Catch-all route `/{path}` musí být poslední (nyní zajištěno správně)

### 3.5 Test Coverage: 7.5/10

**13 Feature testů + 2 Unit testy** — celkem ~2500+ řádků testů

| Oblast | Pokrytí | Hodnocení |
|--------|---------|-----------|
| EntryResource CRUD + workflow | Excelentní (~700 řádků) | 9/10 |
| UserResource + RBAC | Excelentní (~450 řádků) | 9/10 |
| Blueprint + Collection Resources | Dobré | 8/10 |
| Contributor workflow (Entry + Page) | Vynikající | 9/10 |
| Theme Manager | Důkladné | 8/10 |
| Public site routing | Dobré | 7/10 |
| Forms module | Dobré | 7/10 |
| GlobalSet | Dobré | 7/10 |
| Media authorization | Základní | 5/10 |
| **PageResource** | **Minimální** (jen index + draft) | **3/10** |
| **TaxonomyResource** | **Neexistuje** | **0/10** |
| **TermResource** | **Neexistuje** | **0/10** |
| **Multijazyčnost** | **Neexistuje** | **0/10** |
| **Revision system** | **Neexistuje** | **0/10** |
| **E2E flows** | **Neexistuje** | **0/10** |

**Test infra:** Funkční (Pest 4, RefreshDatabase, TestCase správně nakonfigurován).

### 3.6 Frontend Engine: 7/10

> Frontend šablona (default theme) je mimo scope této analýzy.

**Silné stránky:**
- ✅ Theme system s discovery a caching
- ✅ Dynamic routing přes DB (kolekce → route pattern → entry)
- ✅ Archive pagination
- ✅ Signed preview pro nepublikovaný obsah
- ✅ Dark mode support

**Backend mezery ovlivňující frontend:**
- ❌ Cache invalidace při publikaci obsahu chybí
- ❌ Data pro SEO/OG nejsou propagována do view v controlleru
- ❌ Sitemap generátor chybí
- ❌ RSS/Atom feed chybí

---

## 4. IDENTIFIKOVANÉ PROBLÉMY A TECHNICKÝ DLUH

### 4.1 Kritické (blokují produkci)

| # | Problém | Dopad |
|---|---------|-------|
| C1 | **Chybí site konfigurace** — žádné admin UI pro nastavení webu (homepage, site name, locale) | Konfigurace řešena přes GlobalSet `site` + dedikovanou SiteSettings stránku ve Filamentu |
| C2 | **Chybí cache invalidace** při publikaci/změně obsahu | Výkon na produkci, stale content |
| C3 | **Slug uniqueness nerespektuje locale** | Kolize slugů při multijazyčném obsahu |
| C4 | **Validace parent_id chybí** — circular reference možné (Entry, Page, Term) | Potenciální infinite loop v renderování |
| C5 | **SEO/OG data nepropagována** z controlleru do view | Frontend theme nemá odkud brát SEO data |
| C6 | **Chybí Sitemap generátor** | SEO penalizace, žádný discovery mechanismus |

### 4.2 Důležité (kvalita produkce)

| # | Problém | Dopad |
|---|---------|-------|
| D1 | **PageResource testy minimální** | Regrese při refactoru |
| D2 | **Taxonomy/Term Resource testy chybí** | Žádné safety net |
| D3 | **Revision system testy chybí** | Core feature bez testů |
| D4 | **Multijazyčnost netestována** | Klíčová feature bez ověření |
| D5 | **Page form nemá parent_id selector** v UI | Nelze budovat hierarchii stránek přes GUI |
| D6 | **Jmenná konvence `name` vs `title`** na Blueprint a Collection | Nesoulad se spec, matoucí API |
| D7 | **Chybí RSS/Atom feed** | Standard pro blogy/zprávy |
| D8 | **Chybí fulltext search** na backendu (Scout/DB driver) | Chybí search endpoint pro frontend |
| D9 | **Chybí Artisan `mipress:install`** command | Onboarding nových projektů manuální |

### 4.3 Nízká priorita (technický dluh)

| # | Problém |
|---|---------|
| L1 | Blueprint `fields` schema: vnořené sekce vs. spec (plochý array) — oba formáty v kódu fungují |
| L2 | AuditLogsRelationManager v Entry namespace — měl by být ve sdíleném namespace |
| L3 | `name` → `title` rename na Blueprint/Collection (breaking change) |
| L4 | Tabulky bez `mipress_` prefixu — dohodnutý trade-off, neměnit |
| L5 | Chybí N+1 query detection v testech |
| L6 | Chybí architecture testy (Pest arch()) |

---

## 5. STATISTIKY

| Metrika | Hodnota |
|---------|---------|
| **PHP soubory (core)** | ~70 |
| **PHP soubory (forms)** | ~25 |
| **Modely** | 15 (10 core + 5 forms) |
| **Migrace** | 33 celkem |
| **Filament Resources** | 11 (9 core + 2 forms) |
| **Policies** | 8 |
| **Traity** | 5 |
| **Services** | 5 |
| **Mason Bricks** | 5 |
| **Enums** | 3 |
| **Controllers** | 3 |
| **Artisan Commands** | 2 |
| **Test Files** | 15 |
| **Odhadovaný řádků kódu** | ~8000–10000 (PHP) |

---

## 6. ROADMAP K PRVNÍ PRODUKČNÍ VERZI (v1.0) — BACKEND FOCUS

> Frontend šablona (default theme, Blade views) je mimo scope. Roadmapa se zaměřuje na backend: stabilitu, data integrity, caching, testy, multijazyčnost a distribuci package.

### FÁZE 1: Stabilizace & Data Integrity

> Cíl: Opravit známé bugy a mezery v datovém modelu, zajistit integritu dat.

| # | Úkol | Priorita | Effort |
|---|------|----------|--------|
| 1.1 | **Vytvořit SiteSettings** — GlobalSet `site` + dedikovaná Filament Page pro nastavení webu (homepage, site name, locale, date format, per_page) + minimální `config/mipress.php` pro non-runtime hodnoty | 🔴 | S |
| 1.2 | **fix: Validace parent_id** — circular reference guard na Page, Entry, Term modely (self-reference + descendant check) | 🔴 | S |
| 1.3 | **fix: Slug uniqueness** — alter migrace: `UNIQUE(collection_id, locale, slug)` na entries, `UNIQUE(locale, slug)` na pages | 🔴 | S |
| ~~1.4~~ | ~~Contributor policy~~ — **RESOLVED**: Contributor MŮŽE editovat vlastní Published obsah, změny se automaticky odesílají ke schválení přes revision systém. Policy je záměrně správná. | ✅ | — |
| 1.5 | **fix: PageResource form** — přidat parent_id Select s tree structure, vyloučit self + descendants | 🟡 | S |
| 1.6 | **SEO data v controlleru** — propagovat `getSeoTitle()`, `getSeoDescription()`, og_image do view dat v EntryController | 🟡 | S |
| 1.7 | **Konsolidace:** Přesunout AuditLogsRelationManager do `src/Filament/RelationManagers/` | 🟢 | XS |
| 1.8 | **Pint & code style check** na celém codebase | 🟢 | XS |

### FÁZE 2: Cache, Performance & Backend Services

> Cíl: Produkční výkon, backend endpointy pro SEO a discovery.

| # | Úkol | Priorita | Effort |
|---|------|----------|--------|
| 2.1 | **Cache vrstva** — cachovat homepage, collection archives, entry detail (tagged cache) | 🔴 | M |
| 2.2 | **Cache invalidace** — observer/event na Entry/Page publish/unpublish → invalidate relevantní cache tagy | 🔴 | M |
| 2.3 | **Sitemap.xml** — automatický generátor route/controller z publikovaných entries a pages | 🔴 | M |
| 2.4 | **RSS/Atom feed** — controller + route pro datované kolekce (blog, aktuality) | 🟡 | S |
| 2.5 | **Eager loading audit** — review všech Filament resource queries a controller queries na N+1 | 🟡 | S |
| 2.6 | **Static asset versioning** — theme asset URLs s hash pro cache busting | 🟢 | S |

### FÁZE 3: Testy & Kvalita

> Cíl: 80%+ feature coverage, CI-ready test suite.

| # | Úkol | Priorita | Effort |
|---|------|----------|--------|
| 3.1 | **PageResource tests** — kompletní CRUD, hierarchie, workflow (mirror EntryResourceTest) | 🔴 | M |
| 3.2 | **TaxonomyResource tests** — CRUD, collection binding, authorization | 🔴 | M |
| 3.3 | **TermResource tests** — CRUD, hierarchie, entry binding | 🔴 | M |
| 3.4 | **Revision system tests** — snapshot creation, restore, diff, resolvePublicVersion | 🔴 | M |
| 3.5 | **E2E flow tests** — Entry vytvoření → publikace → controller response (status 200, correct data) | 🟡 | M |
| 3.6 | **Multijazyčnost testy** — origin/translation vytvoření, locale scopy, slug uniqueness | 🟡 | M |
| 3.7 | **Architecture testy** — Pest `arch()`: strict types, no dd(), correct namespaces | 🟢 | S |
| 3.8 | **N+1 detection** — `preventLazyLoading()` v testech | 🟢 | S |

### FÁZE 4: Multijazyčnost

> Cíl: Plná i18n podpora na úrovni modelů a admin UI.

| # | Úkol | Priorita | Effort |
|---|------|----------|--------|
| 4.1 | **Multijazyčné scopy** — `forLocale()`, `originals()` na Entry, Page, Term | 🟡 | S |
| 4.2 | **Locale switcher** v Filament admin UI | 🟡 | M |
| 4.3 | **Translation UI** v Entry/Page forms — odkaz na origin, status překladu, vytvoření varianty | 🟡 | M |
| 4.4 | **Locale-aware routing** — middleware/config pro URL prefix (`/en/...`) | 🟡 | M |
| 4.5 | **Hreflang data** — helper/service pro generování hreflang dat z modelu (pro použití v theme) | 🟡 | S |

### FÁZE 5: Installer, Dokumentace & CI

> Cíl: Package připravený k distribuci a nasazení.

| # | Úkol | Priorita | Effort |
|---|------|----------|--------|
| 5.1 | **Install command** — `php artisan mipress:install` (publikování config, migrace, seed demo data, vytvoření admin uživatele) | 🔴 | M |
| 5.2 | **README.md** — instalace, konfigurace, API přehled, theme development guide | 🔴 | M |
| 5.3 | **CI/CD pipeline** — GitHub Actions pro testy, Pint, static analysis | 🟡 | M |
| 5.4 | **PHPStan / Larastan** level 5+ | 🟡 | M |
| 5.5 | **CHANGELOG.md** | 🟢 | S |
| 5.6 | **License** (MIT nebo proprietární) | 🟢 | XS |

---

## 7. PRIORITNÍ MATICE — TOP 10 BACKEND ÚKOLŮ PRO v1.0

| Pořadí | Úkol | Fáze | Zdůvodnění |
|--------|------|------|------------|
| 1 | SiteSettings (GlobalSet + Filament Page) | F1 | Nelze konfigurovat základní nastavení |
| 2 | Cache vrstva + invalidace | F2 | Výkon na produkci |
| 3 | Validace parent_id (circular ref) | F1 | Bezpečnostní riziko, data integrity |
| 4 | Slug uniqueness fix (+ locale) | F1 | Data integrity |
| ~~5~~ | ~~Contributor policy fix~~ | — | ✅ Resolved — workflow funguje správně |
| 6 | SEO data propagace v controlleru | F1 | Backend musí poskytovat SEO data theme |
| 7 | PageResource testy | F3 | Regrese bez safety net |
| 8 | Taxonomy/Term testy | F3 | Nově implementované features bez testů |
| 9 | Sitemap.xml generátor | F2 | SEO základ |
| 10 | Install command | F5 | Onboarding nových projektů |

---

## 8. STAV vs. PRODUKČNÍ PŘIPRAVENOST (backend focus)

```
                          HOTOVO    ZBÝVÁ
Backend (models, DB)      ████████░░  85%
Admin UI (Filament)       ████████░░  80%
Authorization (RBAC)      █████████░  90%
Audit & Revisions         ████████░░  85%
Forms module              ████████░░  80%
Theme engine (backend)    ███████░░░  70%
Controllers & Routing     ███████░░░  70%
Testing                   ██████░░░░  60%
Multijazyčnost            ████░░░░░░  40%
Cache & Performance       ██░░░░░░░░  20%
Dokumentace               █░░░░░░░░░  10%
Installer & Distribution  █░░░░░░░░░  10%
─────────────────────────────────────────
BACKEND PŘIPRAVENOST      ███████░░░  65%
```

---

## 9. ZÁVĚR

**miPress má solidní backend základ** — datový model, admin UI, workflow, revision systém, audit logging, role-based access a forms modul jsou z 80–90% hotové a kvalitní. Kód je moderní, dobře strukturovaný, využívá aktuální verze všech technologií.

**Hlavní backend mezery:**
- Chybí `config/mipress.php` (package konfigurace)
- Cache vrstva a invalidace
- Data integrity guardy (parent_id circular ref, slug+locale uniqueness)
- Test coverage pro Page, Taxonomy, Term, Revision
- Installer command pro onboarding

**Doporučení:** Fáze 1 (stabilizace + data integrity fixes) → Fáze 2 (cache + backend services) → Fáze 3 (testy) → Fáze 4 (i18n) → Fáze 5 (installer + CI). Backend bude produkčně připraven po Fázi 3. Frontend šablona je nezávislá vrstva, kterou lze řešit paralelně.
