<?php

declare(strict_types=1);

use App\Models\User;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Models\Blueprint;
use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Entry;
use MiPress\Core\Models\Taxonomy;
use MiPress\Core\Models\Term;
use MiPress\Core\Services\EntryTaxonomySyncService;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($admin);

    $this->blueprint = Blueprint::factory()->create([
        'handle' => 'page',
        'fields' => [],
    ]);

    $this->collection = Collection::factory()->create([
        'name' => 'Blog',
        'handle' => 'blog',
        'blueprint_id' => $this->blueprint->id,
    ]);

    $this->service = app(EntryTaxonomySyncService::class);
});

it('syncs selected taxonomy terms and preserves unrelated taxonomies', function () {
    $categoryTaxonomy = Taxonomy::create([
        'title' => 'Kategorie',
        'handle' => 'kategorie',
        'collection_id' => $this->collection->id,
    ]);

    $tagTaxonomy = Taxonomy::create([
        'title' => 'Štítky',
        'handle' => 'stitky',
        'collection_id' => $this->collection->id,
    ]);

    $otherTaxonomy = Taxonomy::create([
        'title' => 'Rubriky',
        'handle' => 'rubriky',
        'collection_id' => $this->collection->id,
    ]);

    $oldCategory = Term::create([
        'taxonomy_id' => $categoryTaxonomy->id,
        'title' => 'Stará kategorie',
        'slug' => 'stara-kategorie',
    ]);

    $newCategory = Term::create([
        'taxonomy_id' => $categoryTaxonomy->id,
        'title' => 'Nová kategorie',
        'slug' => 'nova-kategorie',
    ]);

    $oldTag = Term::create([
        'taxonomy_id' => $tagTaxonomy->id,
        'title' => 'Archiv',
        'slug' => 'archiv',
    ]);

    $otherTerm = Term::create([
        'taxonomy_id' => $otherTaxonomy->id,
        'title' => 'Ponechat',
        'slug' => 'ponechat',
    ]);

    $entry = Entry::factory()->create([
        'collection_id' => $this->collection->id,
        'blueprint_id' => $this->blueprint->id,
    ]);

    $entry->terms()->attach([$oldCategory->id, $oldTag->id, $otherTerm->id]);

    $this->service->syncFromFormState($entry, [
        'taxonomy__'.$categoryTaxonomy->id => [$newCategory->id],
        'taxonomy__'.$tagTaxonomy->id => [],
    ]);

    expect($entry->fresh()->terms()->pluck('terms.id')->all())
        ->toContain($newCategory->id)
        ->toContain($otherTerm->id)
        ->not->toContain($oldCategory->id)
        ->not->toContain($oldTag->id);
});
