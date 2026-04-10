<?php

declare(strict_types=1);

use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use MiPress\Core\Database\Seeders\PermissionSeeder;
use MiPress\Core\Enums\UserRole;
use MiPress\Forms\Filament\Pages\FormNotificationSettings;
use MiPress\Forms\Filament\Resources\FormResource;
use MiPress\Forms\Filament\Resources\FormSubmissionResource;
use MiPress\Forms\Mail\FormAutoReply;
use MiPress\Forms\Mail\FormSubmissionNotification;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;
use MiPress\Forms\Models\FormSubmissionAttachment;
use MiPress\Forms\Notifications\NewFormSubmission;

use function Pest\Laravel\actingAs;
use function Pest\Laravel\seed;

beforeEach(function (): void {
    seed(PermissionSeeder::class);

    $admin = User::factory()->create();
    $admin->assignRole(UserRole::SuperAdmin->value);

    actingAs($admin);
});

function makeForm(array $overrides = []): Form
{
    return Form::query()->create(array_merge([
        'title' => 'Kontaktni formular',
        'handle' => 'kontaktni-formular',
        'fields' => [
            [
                'handle' => 'name',
                'type' => 'text',
                'label' => 'Jmeno a prijmeni',
                'required' => true,
                'config' => [
                    'placeholder' => 'Jan Novak',
                ],
                'order' => 1,
            ],
            [
                'handle' => 'email',
                'type' => 'email',
                'label' => 'Email',
                'required' => true,
                'config' => [],
                'order' => 2,
            ],
            [
                'handle' => 'message',
                'type' => 'textarea',
                'label' => 'Zprava',
                'required' => true,
                'config' => [
                    'rows' => 5,
                ],
                'order' => 3,
            ],
        ],
        'recipients' => [],
        'spam_protection' => 'honeypot',
        'is_active' => true,
    ], $overrides));
}

describe('submit flow', function () {
    it('creates form submission with validated data', function () {
        $form = makeForm();

        $response = $this->post(route('mipress.form.submit', ['form' => $form->handle]), [
            '_form_started_at' => time() - 5,
            'website' => '',
            'name' => 'Jan Novak',
            'email' => 'jan@example.com',
            'message' => 'Test zprava',
        ]);

        $response->assertRedirect();

        $submission = FormSubmission::query()->where('form_id', $form->getKey())->first();

        expect($submission)->not->toBeNull()
            ->and($submission->is_spam)->toBeFalse()
            ->and($submission->data['name'])->toBe('Jan Novak')
            ->and($submission->data['email'])->toBe('jan@example.com');
    });

    it('blocks honeypot spam submissions', function () {
        $form = makeForm();

        $response = $this->from('/')
            ->post(route('mipress.form.submit', ['form' => $form->handle]), [
                '_form_started_at' => time() - 5,
                'website' => 'bot-filled',
                'name' => 'Jan Novak',
                'email' => 'jan@example.com',
                'message' => 'Spam',
            ]);

        $response->assertRedirect('/');

        expect(FormSubmission::query()->count())->toBe(0);
    });

    it('queues recipient notification and auto reply when enabled', function () {
        Mail::fake();
        Notification::fake();

        $recipient = User::factory()->create();
        $recipient->assignRole(UserRole::Editor->value);

        $form = makeForm([
            'recipients' => [$recipient->getKey()],
            'auto_reply_enabled' => true,
            'auto_reply_subject' => 'Dekuji',
            'auto_reply_body' => 'Ozveme se brzy.',
        ]);

        $this->post(route('mipress.form.submit', ['form' => $form->handle]), [
            '_form_started_at' => time() - 5,
            'website' => '',
            'name' => 'Jan Novak',
            'email' => 'jan@example.com',
            'message' => 'Test zprava',
        ])->assertRedirect();

        Mail::assertQueued(FormSubmissionNotification::class);
        Mail::assertQueued(FormAutoReply::class);

        Notification::assertSentTo($recipient, NewFormSubmission::class);
    });

    it('stores uploaded attachment in private storage and database', function () {
        Storage::fake('local');

        $form = makeForm([
            'fields' => [
                [
                    'handle' => 'email',
                    'type' => 'email',
                    'label' => 'Email',
                    'required' => true,
                    'config' => [],
                    'order' => 1,
                ],
                [
                    'handle' => 'attachment',
                    'type' => 'file',
                    'label' => 'Priloha',
                    'required' => true,
                    'config' => [
                        'accepted' => '.pdf',
                        'max_size_mb' => 2,
                    ],
                    'order' => 2,
                ],
            ],
        ]);

        $file = UploadedFile::fake()->create('brief.pdf', 200, 'application/pdf');

        $this->post(route('mipress.form.submit', ['form' => $form->handle]), [
            '_form_started_at' => time() - 5,
            'website' => '',
            'email' => 'jan@example.com',
            'attachment' => $file,
        ])->assertRedirect();

        $submission = FormSubmission::query()->where('form_id', $form->getKey())->first();
        $attachment = FormSubmissionAttachment::query()->where('submission_id', $submission->getKey())->first();

        expect($attachment)->not->toBeNull();
        Storage::disk('local')->assertExists($attachment->path);
    });
});

describe('attachment authorization', function () {
    it('returns not found when attachment does not belong to submission', function () {
        $user = User::factory()->create();
        $user->assignRole(UserRole::Editor->value);

        $form = makeForm();

        $submission = FormSubmission::query()->create([
            'form_id' => $form->getKey(),
            'data' => ['email' => 'jan@example.com'],
            'ip_address' => '127.0.0.1',
            'user_agent' => 'Pest',
        ]);

        $otherSubmission = FormSubmission::query()->create([
            'form_id' => $form->getKey(),
            'data' => ['email' => 'other@example.com'],
            'ip_address' => '127.0.0.2',
            'user_agent' => 'Pest',
        ]);

        $path = 'form-attachments/'.$form->getKey().'/'.$otherSubmission->getKey().'/doc.pdf';
        Storage::disk('local')->put($path, 'pdf-content');

        $attachment = FormSubmissionAttachment::query()->create([
            'submission_id' => $otherSubmission->getKey(),
            'field_handle' => 'attachment',
            'filename' => 'doc.pdf',
            'path' => $path,
            'mime_type' => 'application/pdf',
            'size' => 11,
        ]);

        $this->actingAs($user)
            ->get(route('mipress.form.attachments.download', [
                'submission' => $submission,
                'attachment' => $attachment,
            ]))
            ->assertNotFound();
    });
});

describe('admin authorization', function () {
    it('forbids contributor from forms resources and settings page', function () {
        $contributor = User::factory()->create();
        $contributor->assignRole(UserRole::Contributor->value);

        $this->actingAs($contributor)
            ->get(FormResource::getUrl())
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(FormSubmissionResource::getUrl())
            ->assertForbidden();

        $this->actingAs($contributor)
            ->get(FormNotificationSettings::getUrl())
            ->assertForbidden();
    });

    it('allows editor to access forms resources and settings page', function () {
        $editor = User::factory()->create();
        $editor->assignRole(UserRole::Editor->value);

        $this->actingAs($editor)
            ->get(FormResource::getUrl())
            ->assertSuccessful();

        $this->actingAs($editor)
            ->get(FormSubmissionResource::getUrl())
            ->assertSuccessful();

        $this->actingAs($editor)
            ->get(FormNotificationSettings::getUrl())
            ->assertSuccessful();
    });
});
