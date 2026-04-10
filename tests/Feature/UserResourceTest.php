<?php

declare(strict_types=1);

use App\Filament\Pages\EditProfile as ProfilePage;
use App\Models\User;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Livewire\Livewire;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Core\Filament\Resources\UserResource;
use MiPress\Core\Filament\Resources\UserResource\Pages\CreateUser;
use MiPress\Core\Filament\Resources\UserResource\Pages\EditUser;
use MiPress\Core\Filament\Resources\UserResource\Pages\ListUsers;
use MiPress\Core\Notifications\WelcomeNotification;

beforeEach(function () {
    $this->seed(PermissionSeeder::class);

    $this->admin = User::factory()->create();
    $this->admin->assignRole(UserRole::SuperAdmin->value);
    $this->actingAs($this->admin);
});

// --- List Page ---

describe('list page', function () {
    it('can render', function () {
        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('can list users', function () {
        $users = User::factory()->count(3)->create();

        Livewire::test(ListUsers::class)
            ->assertCanSeeTableRecords($users);
    });

    it('can search by name', function () {
        $target = User::factory()->create(['name' => 'Specifický Uživatel']);
        $other = User::factory()->create(['name' => 'Jiný Člověk']);

        Livewire::test(ListUsers::class)
            ->searchTable('Specifický')
            ->assertCanSeeTableRecords([$target])
            ->assertCanNotSeeTableRecords([$other]);
    });

    it('can search by email', function () {
        $target = User::factory()->create(['email' => 'hledany@example.com']);
        User::factory()->create(['email' => 'jiny@example.com']);

        Livewire::test(ListUsers::class)
            ->searchTable('hledany@example.com')
            ->assertCanSeeTableRecords([$target]);
    });
});

// --- Create Page ---

describe('create page', function () {
    it('can render', function () {
        $this->get(UserResource::getUrl('create'))
            ->assertSuccessful();
    });

    it('can create a user with admin role', function () {
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Nový Uživatel',
                'email' => 'novy@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'novy@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->name)->toBe('Nový Uživatel')
            ->and($user->hasRole(UserRole::Admin->value))->toBeTrue()
            ->and($user->email_verified_at)->toBeNull();

        Notification::assertSentTo($user, WelcomeNotification::class);
    });

    it('can create a user with avatar', function () {
        Notification::fake();

        Storage::fake('public');
        Storage::disk('public')->put('avatars/users/avatar-user.webp', 'avatar');

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Avatar User',
                'email' => 'avatar-user@example.com',
                'avatar_path' => ['avatars/users/avatar-user.webp'],
                'role' => UserRole::Admin->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'avatar-user@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->avatar_path)->toBe('avatars/users/avatar-user.webp')
            ->and($user->getFilamentAvatarUrl())->toContain('avatars/users/avatar-user.webp');
    });

    it('can create a user with editor role', function () {
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Editor Test',
                'email' => 'editor@example.com',
                'role' => UserRole::Editor->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'editor@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->hasRole(UserRole::Editor->value))->toBeTrue();
    });

    it('can create a user with contributor role', function () {
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Přispěvatel Test',
                'email' => 'contributor@example.com',
                'role' => UserRole::Contributor->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'contributor@example.com')->first();

        expect($user)->not->toBeNull()
            ->and($user->hasRole(UserRole::Contributor->value))->toBeTrue();
    });

    it('validates required fields', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => null,
                'email' => null,
                'role' => null,
            ])
            ->call('create')
            ->assertHasFormErrors([
                'name' => 'required',
                'email' => 'required',
                'role' => 'required',
            ]);
    });

    it('validates email format', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Test',
                'email' => 'not-an-email',
                'role' => UserRole::Admin->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'email']);
    });

    it('validates email uniqueness', function () {
        User::factory()->create(['email' => 'existing@example.com']);

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Duplicate',
                'email' => 'existing@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->call('create')
            ->assertHasFormErrors(['email' => 'unique']);
    });

    it('sets random password and does not auto-verify email', function () {
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Auto Verify Test',
                'email' => 'auto@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'auto@example.com')->first();

        expect($user->password)->not->toBeNull()
            ->and($user->email_verified_at)->toBeNull();
    });

    it('sends verification and set-password emails after creation', function () {
        Notification::fake();

        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Emails Test',
                'email' => 'emails@example.com',
                'role' => UserRole::Editor->value,
            ])
            ->call('create')
            ->assertHasNoFormErrors();

        $user = User::where('email', 'emails@example.com')->first();

        Notification::assertSentTo($user, WelcomeNotification::class);
    });

    it('prevents creating a second SuperAdmin', function () {
        Livewire::test(CreateUser::class)
            ->fillForm([
                'name' => 'Second SA',
                'email' => 'second-sa@example.com',
                'role' => UserRole::SuperAdmin->value,
            ])
            ->call('create')
            ->assertNotified();

        expect(User::where('email', 'second-sa@example.com')->exists())->toBeFalse();
    });
});

// --- Edit Page ---

describe('edit page', function () {
    it('can render', function () {
        $user = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertSuccessful();
    });

    it('can fill form with existing data', function () {
        $user = User::factory()->create([
            'name' => 'Existující',
            'email' => 'existujici@example.com',
        ]);
        $user->assignRole(UserRole::Editor->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->assertFormSet([
                'name' => 'Existující',
                'email' => 'existujici@example.com',
            ]);
    });

    it('can update user name and email', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Admin->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Upravený',
                'email' => 'upraveny@example.com',
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->name)->toBe('Upravený')
            ->and($user->email)->toBe('upraveny@example.com');
    });

    it('can update user avatar', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Admin->value);

        Storage::fake('public');
        Storage::disk('public')->put('avatars/users/updated-avatar.webp', 'avatar');

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'avatar_path' => ['avatars/users/updated-avatar.webp'],
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->avatar_path)->toBe('avatars/users/updated-avatar.webp')
            ->and($user->getFilamentAvatarUrl())->toContain('avatars/users/updated-avatar.webp');
    });

    it('deletes previous avatar file when avatar changes', function () {
        Storage::fake('public');

        Storage::disk('public')->put('avatars/users/original-avatar.webp', 'old-avatar');
        Storage::disk('public')->put('avatars/users/new-avatar.webp', 'new-avatar');

        $user = User::factory()->create([
            'avatar_path' => 'avatars/users/original-avatar.webp',
        ]);
        $user->assignRole(UserRole::Admin->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => $user->name,
                'email' => $user->email,
                'avatar_path' => ['avatars/users/new-avatar.webp'],
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        Storage::disk('public')->assertMissing('avatars/users/original-avatar.webp');
        Storage::disk('public')->assertExists('avatars/users/new-avatar.webp');
    });

    it('can change a user role', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Editor->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'role' => UserRole::Contributor->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->hasRole(UserRole::Contributor->value))->toBeTrue()
            ->and($user->hasRole(UserRole::Editor->value))->toBeFalse();
    });

    it('can update password', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Admin->value);
        $oldPassword = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'password' => 'noveheslo123',
                'password_confirmation' => 'noveheslo123',
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->password)->not->toBe($oldPassword);
    });

    it('does not change password when field is empty', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Admin->value);
        $oldPassword = $user->password;

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->fillForm([
                'name' => 'Updated Name',
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $user->refresh();

        expect($user->password)->toBe($oldPassword);
    });

    it('prevents changing SuperAdmin role', function () {
        Livewire::test(EditUser::class, ['record' => $this->admin->getRouteKey()])
            ->fillForm([
                'role' => UserRole::Admin->value,
            ])
            ->call('save')
            ->assertNotified();

        $this->admin->refresh();

        expect($this->admin->hasRole(UserRole::SuperAdmin->value))->toBeTrue();
    });
});

// --- Delete ---

describe('delete', function () {
    it('can soft-delete a user', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Editor->value);

        Livewire::test(EditUser::class, ['record' => $user->getRouteKey()])
            ->callAction('delete');

        expect($user->fresh()->trashed())->toBeTrue();
    });

    it('cannot delete SuperAdmin', function () {
        expect(UserResource::canDelete($this->admin))->toBeFalse();
    });

    it('cannot force-delete SuperAdmin', function () {
        expect(UserResource::canForceDelete($this->admin))->toBeFalse();
    });
});

// --- Access Control ---

describe('access control', function () {
    it('allows SuperAdmin to access user management', function () {
        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('allows Admin to access user management', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);
        $this->actingAs($admin);

        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('allows Editor to view user list (read-only)', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('allows Contributor to view user list (read-only)', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $this->get(UserResource::getUrl('index'))
            ->assertSuccessful();
    });

    it('prevents Editor from creating users', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        expect(UserResource::canCreate())->toBeFalse();

        $this->get(UserResource::getUrl('create'))
            ->assertForbidden();
    });

    it('prevents Contributor from creating users', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        expect(UserResource::canCreate())->toBeFalse();

        $this->get(UserResource::getUrl('create'))
            ->assertForbidden();
    });

    it('prevents Contributor from accessing any edit page', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);
        $this->actingAs($contributor);

        $this->get(UserResource::getUrl('edit', ['record' => $contributor]))
            ->assertForbidden();

        $user = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertForbidden();
    });

    it('prevents Editor from editing any user', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        $otherUser = User::factory()->create();

        expect(UserResource::canEdit($editor))->toBeFalse()
            ->and(UserResource::canEdit($otherUser))->toBeFalse();
    });

    it('prevents Editor from accessing any edit page', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        $this->get(UserResource::getUrl('edit', ['record' => $editor]))
            ->assertForbidden();

        $user = User::factory()->create();

        $this->get(UserResource::getUrl('edit', ['record' => $user]))
            ->assertForbidden();
    });

    it('prevents Editor from deleting users', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);
        $this->actingAs($editor);

        $user = User::factory()->create();

        expect(UserResource::canDelete($user))->toBeFalse();
    });

    it('allows Admin to edit non-SuperAdmin users', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);
        $this->actingAs($admin);

        $user = User::factory()->create();

        expect(UserResource::canEdit($user))->toBeTrue()
            ->and(UserResource::canCreate())->toBeTrue()
            ->and(UserResource::canDelete($user))->toBeTrue();
    });

    it('prevents Admin from editing SuperAdmin', function () {
        $admin = User::factory()->create();
        $admin->assignRole(UserRole::Admin->value);
        $this->actingAs($admin);

        expect(UserResource::canEdit($this->admin))->toBeFalse();

        $this->get(UserResource::getUrl('edit', ['record' => $this->admin]))
            ->assertForbidden();
    });
});

describe('profile page', function () {
    it('can render the custom profile page', function () {
        $this->get('/mpcp/profile')
            ->assertSuccessful()
            ->assertSee('Avatar');
    });

    it('can save an avatar from the profile page', function () {
        Storage::fake('public');
        Storage::disk('public')->put('avatars/users/profile-avatar.webp', 'avatar');

        Livewire::test(ProfilePage::class)
            ->fillForm([
                'name' => $this->admin->name,
                'email' => $this->admin->email,
                'avatar_path' => ['avatars/users/profile-avatar.webp'],
            ])
            ->call('save')
            ->assertHasNoFormErrors();

        $this->admin->refresh();

        expect($this->admin->avatar_path)->toBe('avatars/users/profile-avatar.webp')
            ->and($this->admin->getFilamentAvatarUrl())->toContain('avatars/users/profile-avatar.webp');
    });
});
