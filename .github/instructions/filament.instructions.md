---
applyTo: "src/Filament/**"
---

# Filament 5 Development Instructions

## Resource Structure

Every Filament Resource must follow this pattern:

```php
declare(strict_types=1);

namespace MiPress\Core\Filament\Resources;

use Filament\Resources\Resource;

class EntryResource extends Resource
{
    protected static ?string $model = Entry::class;
    protected static ?string $navigationIcon = 'fal-file-lines';
    protected static ?string $navigationGroup = 'Obsah';
    protected static ?string $modelLabel = 'Položka';
    protected static ?string $pluralModelLabel = 'Položky';
    protected static ?int $navigationSort = 1;
}
```

## Czech Navigation Groups

Use these Czech labels consistently:
- `'Obsah'` — for Entry, Collection resources
- `'Taxonomie'` — for Taxonomy, Term resources
- `'Média'` — for media/Curator resources
- `'Menu'` — for menu management
- `'Nastavení'` — for settings pages
- `'Uživatelé'` — for user management

## Form Best Practices

- Always wrap forms in `Section` or `Tabs` components for visual grouping.
- Use `Grid::make(2)` for side-by-side fields.
- Place SEO fields (title, description, OG image) in a collapsible `Section` labeled `'SEO'`.
- Place publishing controls (status, published_at) in a sidebar `Section`.
- Use `Curator\CuratorPicker` for all image fields.
- Use `Mason\Mason` for the main content field.
- Use `TranslatableField::make()` for any field that needs translation support.

## Table Best Practices

- Always include a search column (usually `title` or `name`).
- Include `created_at` and `updated_at` as sortable, toggleable columns.
- Use `TextColumn::make()->badge()` for status fields.
- Add bulk actions for delete and status change.
- Default sort by `created_at` descending.

## Authorization

Check permissions using Spatie Permission with UserRole enum:
- `SuperAdmin` — full access to everything
- `Admin` — manage all content and users (except SuperAdmin)
- `Editor` — create and edit content
- `Contributor` — create content, edit own content only
