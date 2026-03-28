---
applyTo: "tests/**"
---

# Pest PHP Testing Instructions

## Syntax

Always use Pest's functional syntax, never PHPUnit classes:

```php
// CORRECT
it('creates an entry', function () {
    $entry = Entry::factory()->create();

    expect($entry)->toBeInstanceOf(Entry::class)
        ->and($entry->title)->not->toBeEmpty();
});

// WRONG — never use this
class EntryTest extends TestCase
{
    public function test_creates_an_entry()
    {
        $this->assertTrue(true);
    }
}
```

## Structure

- Use `describe()` blocks to group related tests.
- Use `beforeEach()` for shared setup within a describe block.
- Test file names match: `ModelNameTest.php`, `ResourceNameTest.php`.

## Assertions

Prefer Pest's `expect()` API:

```php
expect($entry->status)->toBe(EntryStatus::Published);
expect($entries)->toHaveCount(3);
expect($response)->toHaveStatus(200);
expect($entry->slug)->toStartWith('test-');
```

## Filament Resource Tests

Test Filament resources using Livewire test helpers:

```php
use function Pest\Livewire\livewire;

it('can render the entry list page', function () {
    $this->actingAs(User::factory()->create());

    livewire(ListEntries::class)
        ->assertSuccessful();
});
```

## Database

- Use `RefreshDatabase` trait (defined in `Pest.php`).
- Use factories for all test data, never manual inserts.
- Test both happy paths and edge cases.
