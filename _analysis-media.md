# Analýza Media systému miPress — 2026-04-12

## 1. Přehled architektury

### Co máme
| Vrstva | Soubor | Řádků | Stav |
|--------|--------|-------|------|
| Model | `Media.php` | 247 | OK — Spatie BaseMedia + focal point + manual overrides |
| Model | `Attachment.php` | 35 | OK — polymorfní owner pro Spatie |
| Config | `MediaConfig.php` | 530 | Over-engineered — 20+ metod, duplicitní iterace |
| Trait | `RegistersMiPressMediaConversions.php` | 108 | OK — strategy-aware |
| Service | `MediaConversionService.php` | 39 | OK — tenká delegace |
| Service | `MediaUrlGenerator.php` | 80 | Limitovaný — pouze original/conversion URL |
| Service | `MediaLibraryService.php` | 37 | OK — upload z temp |
| Job | `RegenerateMediaConversionsJob.php` | 29 | OK |
| Resource | `MediaResource.php` | 46 | OK |
| Form | `MediaForm.php` | 117 | Problémový — viz sekce 3 |
| Table | `MediaTable.php` | 107 | Minimální — chybí grid view |
| Edit page | `EditMedia.php` | 575 | Nafouklý — viz sekce 3 |
| Picker | `MediaPicker.php` | 200 | OK — ale chybí thumbnail ve výběru |
| Settings | `MediaConversionSettings.php` | 870 | Over-engineered — viz sekce 3 |
| Blade (3×) | focal-point views | ~580 | Duplicitní logika — viz sekce 3 |

### Stack
- **Spatie MediaLibrary** — storage, conversions, responsive images
- **Filament FileUpload** — Cropper.js built-in editor
- **Alpine.js** — interaktivní focal point picker
- **Disk** — `local_uploads` (local storage s public visibility)

---

## 2. Co funguje dobře

1. **Spatie integrace** — Media model, konverze, path generator (`YearMonthPathGenerator`), slug namer — solidní základ.
2. **MediaPicker** — čistý Filament Field s TableSelect + upload tab, dobrý pattern.
3. **Crop strategy architektura** — 4 strategie (none/center/focal_point/manual) s per-conversion nastavením.
4. **Manual override tracking** — `custom_properties.manual_conversion_overrides` pattern je správný.
5. **MediaConversionSettings** — admin stránka umožňuje plně definovat konverze bez deploymentu.
6. **Permission model** — Contributors vidí jen svá média.

---

## 3. Identifikované problémy

### 3.1 Focal Point nefunguje správně

**ROOT CAUSE:** Několik vzájemně propojených problémů:

a) **Entangle nefunguje na action modal:** V `makeFocalPointAction()` se focal point zobrazuje přes `modalContent()` s view `media-focal-point-editor.blade.php`. Jenže `$wire.entangle()` v action modalu cílí na `mountedActions.0.data.focal_point_x` — tento state path je nestabilní a závisí na pořadí mounted actions. Po kliknutí na jiné akce se index posouvá.

b) **Tři view soubory s duplicitní Alpine logikou:**
- `focal-point-picker.blade.php` (component) — 191 řádků
- `media-focal-point-field.blade.php` (form field view) — 204 řádků
- `media-focal-point-editor.blade.php` (action modal) — 229 řádků

Všechny tři **opakují** stejné Alpine.js metody (`cropStrategy()`, `usesLiveFocalPreview()`, `previewSource()`, `previewObjectPosition()`, `strategyLabel()`, `strategyClasses()` atd.) — cca 60 řádků identického JS kódu 3×. Jakákoliv oprava se musí provést na 3 místech.

c) **Preview po uložení neaktualizuje URL:** Konverze se regenerují na pozadí (queue job), ale UI ukazuje cached URL bez cache-bustu. Po uložení focal point je redirect na edit page, ale conversion URL v Blade šablonách nemají timestamp verzi → prohlížeč zobrazuje starý obrázek.

d) **`syncWire()` race condition:** V `media-focal-point-editor.blade.php` se volá `$wire.set()` manuálně při každém pointermove. Při rychlém tažení to generuje desítky Livewire requestů.

### 3.2 UI problémy

a) **MediaForm na edit stránce je prázdný pro obrázky:** Sekce "Soubor" (FileUpload) je `visible` jen při `create`. Na editaci se tedy zobrazí jen metadata (alt, title) + info placeholders + focal point preview. **Chybí náhled obrázku** v hlavním formuláři — editor musí kliknout na header action pro zobrazení.

b) **Chybí grid/tile view v table:** `MediaTable.php` má jen list view. Reference Curator má dual view (grid + list) s responsive grid, což je pro média daleko vhodnější.

c) **Příliš mnoho header actions:** Editace má 5+ header actions (Focal Point, Upravit originál, Upravit konverzi×N, Regenerovat, Smazat). Pro editora je to nepřehledné. Curator to řeší Tabs v jednom formuláři (Preview/Curation/Replace/Details).

d) **Focal point editor modal je fullscreen dark theme** — nesedí s Filament admin designem. Vypadá jako separátní aplikace místo součásti adminu.

e) **MediaPicker nepodporuje dark mode** v picker view — chybí `dark:` třídy na některých elementech.

f) **Žádná indikace uploadu/stavu** na samotném Media záznamu — chybí badge pro "konverze se regenerují" nebo "chybí konverze".

### 3.3 Over-engineering

a) **MediaConfig.php (530 řádků):**
- 20+ public static metod, mnohé jen filtrují stejný array jiným predikátem
- `builtInConversions()` — 80 řádků s match expression pro 4 konverze
- `configuredConversions()` → `configuredConversionsFromSettings()` → `normalizeConfiguredConversion()` — 3 úrovně indirection
- Mnoho metod iteruje `configuredConversions()` opakovaně (žádné interní caching)

b) **MediaConversionSettings.php (870 řádků):**
- Enormní formulář s 4 taby na přidání jedné konverze
- Helper texty, notes, pills, preview — vše inline v page třídě
- Rendering metody (`renderConversionOverview`, `renderConversionItemLabel`, helper texty) by měly být ve viewu

c) **EditMedia.php (575 řádků):**
- Kombinuje: focal point action, originál cropper action, N× conversion cropper action, replace logic, invalidation logic
- Metody `replaceImageFromTemporaryUpload()` a `replaceConversionFromTemporaryUpload()` by měly být v service/action třídě

### 3.4 Chybějící funkce

| Funkce | Stav | Reference (Curator/Statamic) |
|--------|------|------------------------------|
| Grid/tile view médií | ❌ chybí | Curator má dual layout (grid/list) |
| Náhled obrázku na edit stránce | ❌ chybí | Curator má Preview tab |
| Replace/swap soubor | ❌ jen přes cropper | Curator má Replace tab |
| Image metadata (EXIF) | ❌ chybí | Curator extrahuje + ukládá EXIF |
| Glide / on-the-fly resize | ❌ | Curator generuje URL přes Glide server |
| Curation presets | ❌ | Curator CurationPreset uložené v JSON |
| Drag & drop upload na list page | ❌ | Curator MultiUploadAction |
| Bulk metadata edit | ❌ | – |
| Správa adresářů/složek | ❌ | Curator directories, Statamic AssetContainer |

### 3.5 Technický dluh

a) **Tailwind 4 varování:** Blade views používají deprecated arbitrary values místo nových utilit TW4 (`auto-rows-[minmax(0,1fr)]` → `auto-rows-fr`, `aspect-[4/3]` → `aspect-4/3`, `w-[22rem]` → `w-88`).

b) **Žádný cache-bust na conversion URLs:** `media-focal-point-field.blade.php` posílá `version` timestamp, ale ten se v URL nepoužívá.

c) **`tmp/` cleanup:** Temporary files v `tmp/resource/` a `tmp/picker/` se mažou jen po úspěšném uložení. Neúspěšné uploady a opuštěné editory zanechávají orphan soubory.

d) **Conversions nerespektují disk visibility:** `FileUpload` v action modals nastavuje `->visibility('public')`, ale Spatie conversions mohou generovat soubory s jinou visibility.

e) **Media model `resolveRawCustomProperties()`** — privátní metoda, ale v kódu se nevolá (možná legacy).

---

## 4. Srovnání s referenčními implementacemi

### Curator approach (doporučený vzor)
- **Model Observer** pro lifecycle (creating: metadata extraction, updating: file replacement + Glide cache clear, deleted: cleanup)
- **Glide** pro on-the-fly image transformations → žádné předgenerované konverze, URL-based
- **CurationPreset** — pojmenované transformací uložené v `curations` JSON sloupci Media
- **Tabs na edit page** — Preview / Curation / Replace / Details → vše na jednom místě
- **Dual table layout** — grid (tiles) + list přepínání

### Statamic approach (inspirační vzor)
- **Imaging Manager** s preset systémem (`cp_thumbnail_small_landscape` atd.)
- **Asset Container** — logické složky pro organizaci
- **ExtractInfo** — EXIF + metadata extrakce jako samostatná concern

### Co vzít z reference pro miPress
1. **Grid view v table** — Curator dual layout pattern
2. **Tabs na edit page** místo N header actions
3. **Image preview v hlavním formuláři** (Curator Preview tab)
4. **Observer pattern** pro media lifecycle místo rozsypané logiky v EditMedia
5. **Temp file cleanup** command/job (Curator dělá cleanup v observer)

---

## 5. Doporučené kroky oprav

### Fáze 1: Stabilizace (kritické opravy)

**1.1 Opravit focal point workflow**
- Sjednotit Alpine.js logiku do jednoho Blade componentu (`<x-mipress::focal-point-picker />`)
- Odstranit duplicitní `media-focal-point-editor.blade.php` a `media-focal-point-field.blade.php` — obě budou používat jednu komponentu s prop-driven variantami (inline vs modal)
- Opravit state path na focal point v action modalu — použít stabilní state management místo `mountedActions.0.data`
- Přidat debounce na `syncWire()` (min 150ms)

**1.2 Přidat image preview na edit stránku**
- V `MediaForm.php` přidat Preview sekci s obrázkem, aktuálními rozměry a rychlým přehledem konverzí (vygenerované/čekající)
- Nahradit prázdnou sekci "Soubor" na editu za visuální preview

**1.3 Opravit cache-bust na conversion URLs**
- Na `<img>` tagy v preview přidat `?v={timestamp}` nebo Livewire polling po regeneraci
- Alternativa: vrátit conversion states jako Livewire data a po jobu aktualizovat

### Fáze 2: UI redesign

**2.1 Reorganizovat edit page na tab layout**
- Tab 1: **Náhled** — obrázek + metadata + rozměry + MIME info
- Tab 2: **Focal point & Konverze** — focal point picker + grid konverzí s akcemi
- Tab 3: **Nahradit soubor** — FileUpload pro swap originálu (místo header action "Upravit originál")
- Tab 4: **Metadata** — alt, title, (budoucí: description, caption, tags)
- Header actions: jen Delete + Regenerovat

**2.2 Přidat grid view do MediaTable**
- Implementovat duální layout (grid tiles / list) jako v Curator
- Grid: 2-4 columns, responsive, větší thumbnaily
- List: stávající tabulka

**2.3 Zjednodušit conversion cropper UX**
- Místo N header actions: mít "Ořez" tlačítko přímo u každé konverze v tab 2
- Po kliknutí otevřít inline modal s cropperem zaměřeným na konkrétní konverzi

### Fáze 3: Refaktoring

**3.1 Extrahovat media business logiku z EditMedia**
- `replaceImageFromTemporaryUpload()` → `MediaFileService::replaceOriginal()`
- `replaceConversionFromTemporaryUpload()` → `MediaFileService::replaceConversion()`
- `invalidateManualConversionOverrides()` → `Media::invalidateManualOverrides()`
- `createTemporaryEditorCopy()` → `MediaFileService::createEditorCopy()`

**3.2 Zjednodušit MediaConfig**
- Sloučit `conversions()`, `cropConversions()`, `editorConversions()`, `editorCropConversions()`, `editorManualCropConversions()`, `conversionsForJs()` do 2-3 metod s filtrovacím parametrem
- Přidat interní cache (`once()`) na `configuredConversions()` — volá se 10+× za request
- Přesunout built-in conversion metadata do config souboru nebo YAML místo PHP match expressions

**3.3 Přidat Media Observer**
- `creating` → extrakce metadata (dimensions, MIME validation)
- `updating` → invalidace starých konverzí, cache-bust
- `deleting` → cleanup souborů a temp cache
- Přesunout stávající Boot event logiku z modelu do observeru

**3.4 Temp file cleanup**
- Přidat `php artisan mipress:cleanup-temp-uploads` command
- Schedule daily, maže soubory starší 24h v `tmp/resource/` a `tmp/picker/`

### Fáze 4: Rozšíření (nice-to-have)

**4.1 EXIF metadata extrakce**
Při uploadu extrahovat a uložit EXIF data (camera, aperture, GPS) do `custom_properties.exif`.

**4.2 File swap/replace na edit page**
Dedikovaný tab "Nahradit soubor" místo cropperového workaround.

**4.3 Drag & drop upload na list page**
Dropzone na `ListMedia` stránku pro rychlý bulk upload.

**4.4 Složky/adresáře pro média**
Logické skupiny/tagy na médiích místo plochého listu. Nemusí být filesystem-based.

---

## 6. Prioritní roadmapa

| Pořadí | Krok | Odhadowana náročnost | Dopad |
|--------|------|---------------------|-------|
| 1 | Sjednotit FP Blade do jedné komponenty | Střední | Odstraní 3× duplicitu, opraví FP |
| 2 | Image preview na edit stránce | Nízká | Okamžitě lepší UX |
| 3 | Tab layout na edit page | Střední | Přehlednost, méně header actions |
| 4 | Grid view v MediaTable | Střední | Visual media management |
| 5 | Extrahovat logiku z EditMedia do service | Střední | Maintainability |
| 6 | Cache-bust na conversion URLs | Nízká | Opravu stale previews |
| 7 | Temp cleanup command | Nízká | Prevence orphan souborů |
| 8 | Zjednodušení MediaConfig | Střední | Less code, better perf |
| 9 | Media Observer | Nízká | Čistší lifecycle |
| 10 | File swap tab | Nízká | Better UX pro replace |

---

## 7. Co NEZACHOVÁME / Co ZMĚNÍME strategii

- **Nebudeme zavádět Glide** — Spatie conversions jsou dostatečné a už fungují. Glide by vyžadoval nový image server a přepis URL vrstvy.
- **Nebudeme kopírovat CurationPreset systém** — naše ConversionSettings s admin UI jsou flexibilnější.
- **Nebudeme měnit storage strategii** — `local_uploads` disk + `YearMonthPathGenerator` zůstane.
- **Nebudeme měnit Spatie model** — `Media extends BaseMedia` zůstane, jen přesuneme lifecycle logiku do observeru.

---

## 8. Soubory k úpravě (checklist)

- [ ] Nový: `packages/mipress/core/resources/views/components/focal-point-picker.blade.php` — přepsat jako jednotnou Alpine komponentu
- [ ] Smazat: `packages/mipress/core/resources/views/filament/actions/media-focal-point-editor.blade.php`
- [ ] Přepsat: `packages/mipress/core/resources/views/filament/forms/components/media-focal-point-field.blade.php` → používá `<x-mipress::focal-point-picker />`
- [ ] Přepsat: `app/Filament/Resources/MediaResource/Schemas/MediaForm.php` — tabs, preview
- [ ] Přepsat: `app/Filament/Resources/MediaResource/Pages/EditMedia.php` — tab layout, extracted services
- [ ] Přepsat: `app/Filament/Resources/MediaResource/Tables/MediaTable.php` — dual layout
- [ ] Nový: `packages/mipress/core/src/Services/MediaFileService.php` — extracted business logic
- [ ] Nový: `packages/mipress/core/src/Observers/MediaObserver.php` — lifecycle hooks
- [ ] Nový: `packages/mipress/core/src/Console/Commands/CleanupTempUploadsCommand.php`
- [ ] Refaktor: `packages/mipress/core/src/Media/MediaConfig.php` — simplifikace
- [ ] Fix: Tailwind 4 warnings v Blade views
