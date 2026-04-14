<?php

declare(strict_types=1);

use MiPress\Core\Models\Collection;
use MiPress\Core\Models\Taxonomy;
use MiPress\Core\Models\Term;

it('soft deletes collections taxonomies and terms', function () {
    $collection = Collection::query()->create([
        'name' => 'Novinky',
        'handle' => 'novinky',
    ]);

    $taxonomy = Taxonomy::query()->create([
        'title' => 'Kategorie novinek',
        'is_hierarchical' => true,
        'collection_id' => $collection->id,
    ]);

    $term = Term::query()->create([
        'taxonomy_id' => $taxonomy->id,
        'title' => 'Produktové zprávy',
    ]);

    $collection->delete();
    $taxonomy->delete();
    $term->delete();

    expect(Collection::query()->find($collection->id))->toBeNull()
        ->and(Taxonomy::query()->find($taxonomy->id))->toBeNull()
        ->and(Term::query()->find($term->id))->toBeNull()
        ->and(Collection::withTrashed()->find($collection->id)?->trashed())->toBeTrue()
        ->and(Taxonomy::withTrashed()->find($taxonomy->id)?->trashed())->toBeTrue()
        ->and(Term::withTrashed()->find($term->id)?->trashed())->toBeTrue();
});
