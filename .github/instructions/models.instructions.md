---
applyTo: "src/Models/**"
---

# Eloquent Model Instructions

## Base Pattern

Every model must follow this structure:

```php
declare(strict_types=1);

namespace MiPress\Core\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Spatie\Sluggable\HasSlug;
use Spatie\Sluggable\SlugOptions;
use Spatie\Translatable\HasTranslations;

class Entry extends Model
{
    use HasFactory, HasSlug, HasTranslations, SoftDeletes;

    protected $table = 'mipress_entries';

    protected $fillable = [
        'title',
        'slug',
        'content',
        'status',
        'collection_id',
        'published_at',
    ];

    protected $casts = [
        'content' => 'array',
        'status' => EntryStatus::class,
        'published_at' => 'datetime',
    ];

    public array $translatable = ['title', 'content'];
}
```

## Table Naming

All miPress tables use the `mipress_` prefix to avoid conflicts with the host application:
- `mipress_entries`
- `mipress_collections`
- `mipress_taxonomies`
- `mipress_terms`
- `mipress_menus`
- `mipress_menu_items`
- `mipress_settings`

## Required Traits

- `HasFactory` — always, for testing
- `SoftDeletes` — on content models (Entry, Collection, Taxonomy, Term)
- `HasSlug` — on models with a slug field, implement `getSlugOptions()`
- `HasTranslations` — on models with translatable content, define `$translatable` array

## Relationships

- Always type-hint return types on relationship methods.
- Use `->cascadeOnDelete()` on foreign keys for child records.
- Define inverse relationships on both sides.

## Scopes

Define commonly used query scopes:
- `scopePublished()` — filter by published status and date
- `scopeDraft()` — filter drafts
- `scopeOrdered()` — default ordering

## Factories

Every model must have a corresponding factory in `database/factories/` using realistic Czech fake data where applicable (names, addresses).
