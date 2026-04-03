# miPress/core — Analýza stávajícího stavu

> Datum: 3. dubna 2026  
> Scope: `packages/mipress/core` (src/, database/)

---

## 1. Modely

### Blueprint (`blueprints`)

| Sloupec | Typ | Poznámka |
|---------|-----|----------|
| id | bigint PK | |
| name | string | ⚠ spec říká `title` |
| handle | string unique | |
| fields | json nullable | |
| timestamps | — | |

**Casts:** `fields` → array  
**Vztahy:** `hasMany Collection`, `hasMany Entry`  
**Traity:** `HasFactory`  
**Chybí:** `title` (místo `name`), žádná navigace přes sections/order

---

### Collection (`collections`)

| Sloupec | Typ | Poznámka |
|---------|-----|----------|
| id | bigint PK | |
| name | string | ⚠ spec říká `title` |
| handle | string unique | |
| blueprint_id | FK blueprints nullable | |
| icon | string nullable | |
| route | string nullable | |
| dated | boolean | |
| slugs | boolean | |
| hierarchical | boolean | přidáno migrací 500000 |
| sort_direction | string | |
| sort_order | unsignedInt | |
| timestamps | — | |

**Casts:** `dated`, `slugs`, `hierarchical` → bool; `sort_order` → int  
**Vztahy:** `belongsTo Blueprint`, `hasMany Entry`  
**Traity:** `HasFactory`  
**Chybí:** `description` (text, nullable), `belongsToMany Taxonomy` (pivot `collection_taxonomy`), SoftDeletes

---

### Entry (`entries`)

| Sloupec | Typ | Poznámka |
|---------|-----|----------|
| id | bigint PK | |
| collection_id | FK collections | cascade delete |
| blueprint_id | FK blueprints nullable | ⚠ spec: blueprint přes collection |
| title | string | |
| slug | string nullable | unique(collection_id, slug) |
| data | json nullable | Blueprint-driven pole |
| status | string | default draft |
| published_at | timestamp nullable | |
| author_id | FK users | cascade delete |
| sort_order | unsignedInt | |
| origin_id | FK entries nullable | multijazyčnost |
| locale | string | default cs |
| review_note | text nullable | |
| featured_image_id | FK curator nullable | |
| parent_id | FK entries nullable | hierarchie |
| soft_deletes | — | |
| timestamps | — | |

**Casts:** `data` → array, `status` → EntryStatus, `published_at` → datetime, `sort_order`/`parent_id` → int  
**Vztahy:** `belongsTo Collection`, `Blueprint`, `User(author)`, `Entry(origin)`, `Entry(parent)`; `hasMany Entry(children)`; `belongsTo CuratorMedia(featuredImage)`  
**Traity:** `Auditable`, `HasFactory`, `HasSlug`, `SoftDeletes`  
**Chybí:**
- `meta_title`, `meta_description` jako reálné sloupce (nyní v `data` JSON přes `statePath('data')`)
- `og_image_id` (FK curator)
- `scheduled_at` (timestamp nullable)
- `morph revisions()` vztah
- `belongsToMany Term` (pivot `entry_term`)
- `HasWorkflow` trait
- `HasSeo` trait
- `HasRevisions` trait
- Unique constraint by měl zahrnovat locale: `UNIQUE(collection_id, locale, slug)`

---

### Page (`pages`)

| Sloupec | Typ | Poznámka |
|---------|-----|----------|
| id | bigint PK | |
| blueprint_id | FK blueprints nullable | ⚠ spec: Pages nejsou Blueprint-driven |
| title | string | |
| slug | string unique nullable | ⚠ spec: unique v rámci locale, ne globálně |
| data | json nullable | ⚠ spec: pole `content` (Mason JSON), ne `data` |
| status | string | default draft |
| published_at | timestamp nullable | |
| author_id | FK users | cascade delete |
| sort_order | unsignedInt | |
| parent_id | FK pages nullable | |
| featured_image_id | FK curator nullable | ⚠ spec: Pages nemají featured_image |
| locale | string | default cs |
| review_note | text nullable | |
| soft_deletes | — | |
| timestamps | — | |

**Casts:** `data` → array, `status` → EntryStatus, `published_at` → datetime, `sort_order`/`parent_id` → int  
**Vztahy:** `belongsTo Blueprint`, `User(author)`, `Page(parent)`; `hasMany Page(children)`; `belongsTo CuratorMedia(featuredImage)`  
**Traity:** `Auditable`, `HasFactory`, `HasSlug`, `SoftDeletes`  
**Chybí:**
- Sloupec `content` (místo `data`)
- Sloupec `meta_title`, `meta_description` jako reálné sloupce
- Sloupec `scheduled_at`
- Sloupec `origin_id` (FK pages, nullable) — multijazyčnost
- `morph revisions()` vztah
- `HasWorkflow` trait
- `HasSeo` trait
- `HasRevisions` trait
- Guard: nedovolí smazat stránku s potomky (žádná validace)
- Guard: parent_id nesmí být sám sebe ani potomek (žádná validace)

---

### AuditLog (`audit_logs`)

| Sloupec | Typ |
|---------|-----|
| id | bigint PK |
| user_id | FK users nullable |
| auditable_type | string (morph) |
| auditable_id | bigint (morph) |
| action | string |
| old_values | json nullable |
| new_values | json nullable |
| note | text nullable |
| created_at | timestamp nullable |

**Casts:** `old_values`, `new_values` → array  
**Vztahy:** `belongsTo User`, `morphTo auditable`  
**Účel:** Audit trail (vytvořeno/upraveno/smazáno/obnoveno/změna stavu)  
**Poznámka:** Toto je audit log, nikoliv `Revision` (content snapshot). Target spec požaduje obojí odděleně.

---

### GlobalSet (`global_sets`)

| Sloupec | Typ |
|---------|-----|
| id | bigint PK |
| handle | string unique |
| title | string |
| data | json nullable |
| timestamps | — |

**Traity:** `HasFactory`  
**Poznámka:** Mimo target architectural scope ale funkční, ponechat.

---

### Setting (`settings`)

| Sloupec | Typ |
|---------|-----|
| key | string PK |
| value | text nullable |
| timestamps | — |

**Poznámka:** Mimo target architectural scope ale funkční, ponechat.

---

## 2. Filament Resources

### BlueprintResource

- **Model:** Blueprint
- **Navigation:** Nastavení / sort 20 / icon `fal-pen-ruler`
- **Form fields:** `name`, `handle` (unique), Repeater pro **sekce** (section.name + nested Repeater pro **pole** s handle, label, type, required)
- **Field types v options:** text, textarea, richtext, mason, number, select, checkbox, toggle, radio, datetime, date, media, color, tags, repeater, keyvalue, markdown, hidden
- **Table columns:** name, handle
- **Pages:** List, Create, Edit
- **Konflikty:**
  - `name` místo `title` (spec)
  - Struktura fields v JSON je `[{section, fields:[{handle,label,type,required}]}]` — vnořené sekce, ale spec očekává plochý array `[{handle,type,label,required,config,order}]`
  - Chybí: počet polí v table, použito v N kolekcích

---

### CollectionResource

- **Model:** Collection
- **Navigation:** Nastavení / sort 10 / icon `fal-layer-group`
- **Form fields:** `name`, `handle` (unique), `blueprint_id` (Select), `icon`, `dated`, `slugs`, `hierarchical`, `route`, `sort_direction`, `sort_order`
- **Table columns:** name, handle
- **Pages:** List, Create, Edit
- **Chybí:**
  - `description` pole ve formuláři
  - CheckboxList pro přiřazení Taxonomií (taxonomy systém neexistuje)
  - Počet entries v table
  - `name` → `title` rename

---

### EntryResource

- **Model:** Entry
- **Navigation:** dynamicky z Collections (bez `handle=pages`), per-collection navigation items s badge pro in-review počty
- **Form (EntryForm):** title (live slug gen), slug, Blueprint-driven sekce přes dynamický renderovací kód, SEO sekce (statePath('data') → meta_title, meta_description), sidebar: status/workflow akce/details/featured_image
- **Blueprint renderování:** Inline v EntryForm metodou `buildBlueprintSections()` a `buildFieldComponents()` — mapuje type → Filament component
- **Workflow akce (v sidebar sekci 'Workflow'):** `saveAndPublish`, `saveDraft`, `submitForReview` — ale sekce je `visible(false)` (Workflow sekce je skrytá, zbývá jen pro Entry zatím nefunkční?)
- **Sidebar sekce 'Stav':** status badge, published_at, review_note, moveToTrash, deletePermanently, duplicate, history link, details (id, created, updated)
- **featured_image:** CuratorPicker v sidebar
- **Table columns:** featuredImage (CuratorColumn), title, status (badge), author, published_at, updated_at
- **Filtry:** author, created_at_range, created_month, status, trashed
- **Pages:** List, Create, Edit, EntryHistory
- **RelationManagers:** AuditLogsRelationManager
- **Chybí:**
  - Featured image SEO fallback chain (og_image)
  - Taxonomy pickery v sidebar (taxonomy neexistuje)
  - Filtry pro taxonomie

---

### PageResource

- **Model:** Page
- **Navigation:** Obsah / sort 1 / icon `fal-file-lines`
- **Form (PageForm):** title (live slug gen), slug, Blueprint-driven sekce (stejná logika jako EntryForm), SEO sekce (statePath('data') → meta_title, meta_description), sidebar: status/workflow akce/details/featured_image
- **Sidebar sekce 'Stav':** status badge, published_at, review_note, moveToTrash, deletePermanently, duplicate, history link, details
- **featured_image:** CuratorPicker v sidebar
- **Table columns:** featuredImage (CuratorColumn), title, parent.title, status (badge), author, published_at, updated_at
- **Filtry:** author, created_at_range, created_month, status, trashed
- **Pages:** List, Create, Edit, PageHistory
- **RelationManagers:** `EntryResource\RelationManagers\AuditLogsRelationManager` (sdílený z Entry namespace)
- **Chybí:**
  - parent_id Select (volba rodičovské stránky) — formulář ho NEMÁ
  - `content` (Mason) místo Blueprint-driven `data`
  - SEO jako reálné sloupce (ne v data JSON)
  - Odsazení titulku v tabulce dle hloubky
  - Validace: nelze smazat stránku s potomky

---

### GlobalSetResource

- **Model:** GlobalSet
- **Navigation:** Nastavení / sort 30 / icon `fal-globe`
- **Přístup:** pouze SuperAdmin a Admin
- **Pages:** List, Create, Edit

---

### UserResource

- **Model:** User (app\Models\User)
- **Navigation:** Uživatelé / sort 10 / icon `fal-user-group-crown`
- **Chybí:** výčet všech fields (nestudováno do hloubky, mimo scope)

---

## 3. Traity, Enumy, Services

### Traity

| Název | Umístění | Popis | Chybí |
|-------|----------|-------|-------|
| `Auditable` | `Traits/Auditable.php` | Boot hooks: created/updated/deleted/restored → loguje do audit_logs. Metody: `logAudit()`, `auditLogs()` morph. Exclude pole přes `auditExclude`. | — |
| `HasRoles` | `Traits/HasRoles.php` | Extends Spatie HasRoles. Přidává: `isSuperAdmin()`, `isAdmin()`, `isEditor()`, `isContributor()`, `hasMfaEnabled()`, `canAccessPanel()`. Boot: zakazuje smazat SuperAdmin. | — |
| `HasWorkflow` | **NEEXISTUJE** | Workflow logika je inline v EntryForm a PageForm jako Filament Actions | Celý trait chybí |
| `HasSeo` | **NEEXISTUJE** | SEO pole (meta_title, meta_desc) jsou v `data` JSON přes statePath | Celý trait chybí |
| `HasRevisions` | **NEEXISTUJE** | Žádný systém content snapshots | Celý trait + Revision model chybí |

### Enumy

| Název | Umístění | Hodnoty | Implementuje |
|-------|----------|---------|--------------|
| `EntryStatus` | `Enums/EntryStatus.php` | Draft, InReview, Published, Scheduled, Rejected | `HasLabel`, `getColor()` |
| `UserRole` | `Enums/UserRole.php` | SuperAdmin, Admin, Editor, Contributor | `HasLabel` |

### Services

| Název | Umístění | Popis |
|-------|----------|-------|
| `GlobalSetManager` | `Services/GlobalSetManager.php` | Singleton. Lazy-loads všechny GlobalSet záznamy, poskytuje `all()`, `find(handle)`, `get(handle, key, default)`. |
| `BlueprintFieldResolver` | **NEEXISTUJE** | Chybí — dynamické renderování Blueprint polí je nyní inline v EntryForm a PageForm |

### Mason

| Soubor | Popis |
|--------|-------|
| `Mason/EditorialBrickCollection.php` | Registruje sadu Mason bloků |
| `Mason/Bricks/CallToActionBrick.php` | Mason blok: CTA |
| `Mason/Bricks/InsightGridBrick.php` | Mason blok: mřížka postřehů |
| `Mason/Bricks/NarrativeBrick.php` | Mason blok: narativní text |
| `Mason/Bricks/PullQuoteBrick.php` | Mason blok: citát |

---

## 4. Migrace — tabulky a sloupce

### Pořadí migrací (mipress/core/database/migrations/)

| Soubor | Akce |
|--------|------|
| `2026_03_28_000001` | Přidává `soft_deletes` do `users` |
| `2026_03_28_100000` | Vytváří `blueprints` |
| `2026_03_28_100001` | Vytváří `collections` |
| `2026_03_28_100002` | Vytváří `entries` (incl. soft_deletes, origin_id, unique collection+slug) |
| `2026_03_28_100003` | Přidává `review_note` do entries; vytváří `audit_logs` |
| `2026_03_28_100004` | Přidává `featured_image_id` do entries (FK → curator) |
| `2026_03_29_120000` | Vytváří Laravel `notifications` tabulku |
| `2026_03_29_200000` | Vytváří `settings` |
| `2026_03_29_300000` | Vytváří `global_sets` |
| `2026_03_29_400000` | Přidává `has_email_authentication` do users |
| `2026_03_29_500000` | Přidává `hierarchical` do collections; přidává `parent_id` do entries |
| `2026_03_30_100000` | Vytváří `pages`; migruje záznamy z entries kde collection.handle='pages' |

### Výsledné tabulky (z mipress/core migrací)

**`blueprints`:** id, name, handle(uq), fields(json), timestamps  
**`collections`:** id, name, handle(uq), blueprint_id(FK), icon, route, dated, slugs, hierarchical, sort_direction, sort_order, timestamps  
**`entries`:** id, collection_id(FK), blueprint_id(FK), title, slug, data(json), status, published_at, author_id(FK), sort_order, origin_id(FK), locale, review_note, featured_image_id(FK), parent_id(FK), timestamps, deleted_at  
**`audit_logs`:** id, user_id(FK), auditable_type, auditable_id, action, old_values(json), new_values(json), note, created_at  
**`settings`:** key(PK), value(text), timestamps  
**`global_sets`:** id, handle(uq), title, data(json), timestamps  
**`pages`:** id, blueprint_id(FK), title, slug(uq), data(json), status, published_at, author_id(FK), sort_order, parent_id(FK), featured_image_id(FK), locale, review_note, timestamps, deleted_at  

**Tabulky z hlavní `database/migrations/` (app-level):**  
`users`, `cache`, `jobs`, `roles/permissions` (Spatie), `curator` (awcodes)

---

## 5. GAP analýza — co chybí oproti cílové architektuře

### A. Zcela chybějící modely / tabulky

| Co chybí | Priorita | Popis |
|----------|----------|-------|
| `Taxonomy` model + tabulka | 🔴 kritická | Model s title, handle, is_hierarchical, blueprint_id, description |
| `Term` model + tabulka | 🔴 kritická | Model s taxonomy_id, title, slug, data, parent_id, sort_order, origin_id, locale |
| Pivot `collection_taxonomy` | 🔴 kritická | Vazba Collection → Taxonomy |
| Pivot `entry_term` | 🔴 kritická | Vazba Entry → Term |
| `Revision` model + tabulka | 🔴 kritická | Content snapshots: revisionable morph, user_id, data(json), note |

### B. Chybějící traity

| Trait | Priorita | Popis |
|-------|----------|-------|
| `HasWorkflow` | 🔴 kritická | Workflow logika je inline ve form schemas, přesunout do traitu |
| `HasSeo` | 🟡 střední | `seoFormSchema()` jako sdílený Filament Section schema |
| `HasRevisions` | 🔴 kritická | `createRevision()`, `restoreRevision()`, model event `updating` |

### C. Chybějící services

| Service | Priorita | Popis |
|---------|----------|-------|
| `BlueprintFieldResolver` | 🟡 střední | `resolve(field)` → Filament component; `resolveAll(fields)` → array; extrahovat z inline kódu v EntryForm/PageForm |

### D. Chybějící Filament Resources

| Resource | Priorita |
|----------|----------|
| `TaxonomyResource` | 🔴 kritická |
| `TermResource` | 🔴 kritická |

### E. Chybějící políčka v existujících modelech/tabulkách

| Model | Chybějící sloupec | Priorita |
|-------|-------------------|----------|
| Page | `content` (json/Mason) — náhrada za `data` | 🔴 |
| Page | `meta_title` (string nullable) | 🔴 |
| Page | `meta_description` (text nullable) | 🔴 |
| Page | `scheduled_at` (timestamp nullable) | 🟡 |
| Page | `origin_id` (FK pages nullable) | 🟡 |
| Entry | `meta_title` (string nullable) | 🔴 |
| Entry | `meta_description` (text nullable) | 🔴 |
| Entry | `og_image_id` (FK curator nullable) | 🟡 |
| Entry | `scheduled_at` (timestamp nullable) | 🟡 |
| Collection | `description` (text nullable) | 🟡 |
| Collection | relationship `belongsToMany Taxonomy` | 🔴 |

### F. Chybějící scopy / helpery na modelech

| Model | Chybí |
|-------|-------|
| Page | `forLocale()`, `originals()` scopy |
| Page | `translations()`, `origin()` vztahy |
| Entry | `forLocale()`, `originals()` scopy |
| Entry | `translations()` vztah (jen `origin()` existuje) |
| Entry | `terms()` belongsToMany |
| Term | `parent()`, `children()`, `translations()`, `origin()` |

### G. Chybějící business logika

| Kde | Co chybí |
|-----|----------|
| PageResource | parent_id Select (volba rodičovské stránky) ve formuláři |
| Page model/observer | Guard: smazání stránky s potomky → error |
| Page model | Validace: parent_id nesmí být sám sebe ani potomek |
| Page tabulka | Odsazení titulku v tabulce dle hloubky stromu |
| EntryResource | Taxonomy pickery v sidebar (per-collection) |
| EntryResource | Filter podle taxonomií |
| EntryResource | SEO: og_image CuratorPicker |
| PageResource/EntryResource | Revize tab (chronolog. seznam, Obnovit, Porovnat diff) |

---

## 6. KONFLIKT analýza — co existuje ale je v rozporu

### K1 — Tabulka `pages` je Blueprint-driven, spec říká opak

**Stav:** Tabulka `pages` má `blueprint_id` (FK), `featured_image_id` (FK), `data` (JSON — Blueprint pole).  
**Spec:** Pages jsou statické, mají pevnou strukturu. Mají `content` (Mason JSON), **žádný** blueprint, **žádny** featured_image, **žádné** dynamické pole v data.  
**Dopad:** Nutné alter migrace (přidat `content`, odstranit/ignorovat `blueprint_id` a `featured_image_id`), přeučit formulář.  
**Riziko:** Data existujících stránek jsou v `data` JSON poli jako Blueprint-driven; přechod na `content` = Mason JSON vyžaduje datovou migraci.

---

### K2 — SEO pole jsou v JSON `data`, spec chce reálné sloupce

**Stav:** `PageForm` a `EntryForm` používají `statePath('data')` pro SEO sekci — `meta_title` a `meta_description` jsou fyzicky uloženy jako klíče uvnitř JSON sloupce `data`.  
**Spec:** `meta_title` a `meta_description` mají být samostatné DB sloupce (umožňuje indexaci, validaci, HasSeo trait).  
**Dopad:** Alter migrace přidá sloupce; nutná datová migrace hodnot z JSON do sloupců; formulář upravit na přímé mapování.

---

### K3 — Tabulka `entries` sdílí `unique(collection_id, slug)` bez locale

**Stav:** Unique constraint je `(collection_id, slug)`.  
**Spec:** Pro multijazyčnost slug musí být unikátní v rámci `(collection_id, locale, slug)`.  
**Dopad:** Alter migrace: drop starý unique index, přidat nový `unique(collection_id, locale, slug)`.

---

### K4 — Jmenná konvence: `name` vs. `title`

**Stav:** Blueprint a Collection používají sloupec `name`.  
**Spec:** Obě specifikace říkají `title`.  
**`models.instructions.md`:** Neuvedena preference, ale pattern příkladu používá `title`.  
**Dopad:** Rename sloupce + aktualizace fillable, casts, form fields, table columns v obou resources + factory.  
**Riziko:** Existující data v DB mají sloupec `name` — nutná rename migrace.

---

### K5 — BlueprintResource: struktura fields (vnořené sekce vs. plochý array)

**Stav:** Formulář pro Blueprint ukládá `fields` jako `[{section: string, fields: [{handle, label, type, required}]}]` — tedy pole sekcí, každá obsahuje pole polí.  
**Spec:** `fields` jako plochý array `[{handle, type, label, required, config, order}]`.  
**Dopad:** `BlueprintFieldResolver` musí umět zpracovat oba formáty NEBO migrace dat sjednotí na spec formát. Forme musí být upravena.

---

### K6 — `PageHistory` / `EntryHistory` = AuditLogs, spec chce Revisions

**Stav:** `PageHistory` a `EntryHistory` jsou stránky typu `ManageRelatedRecords` zobrazující `auditLogs` (který je audit trail, ne content snapshot). `AuditLogsRelationManager` zobrazuje created/updated/deleted akce.  
**Spec:** Revize = chronologický seznam content snapshotů s možností Obnovit (restore) a Porovnat (diff). To vyžaduje `Revision` model.  
**Dopad:** Audit log (stávající) zůstane jako audit trail. Nový `HasRevisions` trait bude vytvářet snapshots při `updating`. History pages přepracovat nebo duplikovat pro revize.

---

### K7 — `EntryPolicy` a `PagePolicy`: Contributor může editovat Published

**Stav:** Policy pro Contributor povoluje edit vlastních záznamů ve statusech Draft, Rejected, InReview **a Published**.  
**Spec:** Contributor: "vlastní Draft/Rejected" — pouze tyto dva stavy.  
**Dopad:** Odebrat `EntryStatus::InReview` a `EntryStatus::Published` z Contributor podmínky (nebo ponechat InReview pro Contributor, záleží na produktovém rozhodnutí). Published určitě odebrat.

---

### K8 — Namespace `AuditLogsRelationManager` pod EntryResource, používán i PageResource

**Stav:** `PageResource` includuje `MiPress\Core\Filament\Resources\EntryResource\RelationManagers\AuditLogsRelationManager`.  
**Spec / Best practice:** RelationManager sdílený oběma resources by měl být v sdíleném namespace (např. `Filament\RelationManagers\`).  
**Dopad:** Přesunout do `src/Filament/RelationManagers/AuditLogsRelationManager.php`.

---

### K9 — Tabulky NEMAJÍ `mipress_` prefix

**Stav:** Existující tabulky: `entries`, `collections`, `blueprints`, `pages`, `audit_logs`, etc.  
**`models.instructions.md`:** Říká, že tabulky mají mít prefix `mipress_` (např. `mipress_entries`).  
**Realita:** Prefix NIKDY nebyl použit a data v DB jsou bez prefixu.  
**Dohodnutý postup:** **Prefix neměnit** — retroaktivní rename všech tabulek by byl destruktivní a zbytečný. Tato odchylka od instructions.md je akceptovaný technical debt.

---

### K10 — `pages` migrace přesouvá data z entries (pages collection) do pages tabulky

**Stav:** Migrace `2026_03_30_100000` při aplikaci zkopírovala záznamy z `entries` (kde `collection_id` = ID kolekce s handle='pages') do nové `pages` tabulky. EntryResource filtruje `handle != 'pages'`.  
**Riziko:** Pokud byl `pages` handle entries ještě v databázi při migraci, mohlo dojít k ID konfliktům nebo duplikacím. Starý `pages` collection záznam v `collections` tabulce stále existuje. Doporučit kontrolu DB stavu.

---

## Shrnutí priorit pro Fázi 2

### Musí být hotovo (blocker pro funkčnost)

1. **Taxonomy + Term** modely, migrace, Resources, Policy
2. **Pivot tabulky** `collection_taxonomy` a `entry_term`
3. **Page model refactor**: přidat `content`, `meta_title`, `meta_description`, `scheduled_at`, `origin_id`; zbavit se Blueprint-driven přístupu (blueprint_id, featured_image_id volitelně ponechat pro zpětnou kompatibilitu)
4. **Entry model doplnit**: `meta_title`, `meta_description`, `og_image_id`, `scheduled_at`
5. **Revision model + HasRevisions trait**
6. **Collection.description** sloupec

### Mělo by být hotovo (zvyšuje kvalitu)

7. **HasSeo trait** s `seoFormSchema()`
8. **HasWorkflow trait** (extrahovat inline Filament akce)
9. **BlueprintFieldResolver service** (extrahovat inline renderovací logiku)
10. **PageResource form**: přidat parent_id Select, přejít na Mason `content` field, SEO jako reálné sloupce
11. **EntryResource**: taxonomy pickery, og_image, revize tab
12. **Přesunout AuditLogsRelationManager** do sdíleného namespace
13. **EntryPolicy/PagePolicy**: opravit Contributor (odebrat Published ze stavů prostupných editaci)

### Technický dluh (low priority)

14. `name` → `title` rename v Blueprint a Collection (breaking change, opatrně)
15. Blueprint `fields` schéma: sjednotit na plochý array dle spec
16. Entry unique slug: přidat `locale` do constraint
17. Multijazyčné scopy (`forLocale`, `originals`) na modelech
