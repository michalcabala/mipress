<?php

declare(strict_types=1);

namespace MiPress\Forms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Http\UploadedFile;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use MiPress\Forms\Http\Requests\SubmitFormRequest;
use MiPress\Forms\Mail\FormAutoReply;
use MiPress\Forms\Mail\FormSubmissionNotification;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;
use MiPress\Forms\Models\FormSubmissionAttachment;
use MiPress\Forms\Notifications\NewFormSubmission;
use MiPress\Forms\Services\FormRenderer;
use MiPress\Forms\Services\SpamProtection;

class FormSubmissionController extends Controller
{
    public function submit(
        SubmitFormRequest $request,
        Form $form,
        FormRenderer $renderer,
        SpamProtection $spamProtection,
    ): RedirectResponse {
        abort_unless($form->is_active, 404);

        if ($spamProtection->check($request, $form)) {
            return back()->withErrors(['form' => 'Formular nebylo mozne odeslat.']);
        }

        $validated = validator($request->all(), $renderer->rules($form))->validate();

        $submissionData = collect($validated)
            ->reject(static fn (mixed $value): bool => $value instanceof UploadedFile)
            ->toArray();

        $submission = FormSubmission::query()->create([
            'form_id' => $form->getKey(),
            'data' => $submissionData,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        $this->storeAttachments($form, $submission, $validated);

        $recipientUsers = $form->recipientsQuery()->get();

        if ($recipientUsers->isNotEmpty()) {
            Mail::to($recipientUsers)->queue(new FormSubmissionNotification($form, $submission));
            Notification::send($recipientUsers, new NewFormSubmission($submission));
        }

        if ((bool) $form->auto_reply_enabled) {
            $email = $validated['email'] ?? null;

            if (is_string($email) && $email !== '') {
                Mail::to($email)->queue(new FormAutoReply($form, $submission));
            }
        }

        return back()->with('mipress_form_success', $form->success_message);
    }

    public function downloadAttachment(
        Request $request,
        FormSubmission $submission,
        FormSubmissionAttachment $attachment,
    ): Response {
        abort_unless($attachment->submission_id === $submission->getKey(), 404);

        $user = Auth::user();
        abort_unless($user !== null, 403);

        $isSuperAdmin = method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin();
        $isRecipient = in_array($user->getKey(), $submission->form->recipientIds(), true);

        abort_unless($isSuperAdmin || $isRecipient, 403);

        return Storage::disk('local')->download($attachment->path, $attachment->filename);
    }

    /**
     * @param  array<string, mixed>  $validated
     */
    private function storeAttachments(Form $form, FormSubmission $submission, array $validated): void
    {
        foreach ($validated as $fieldHandle => $value) {
            if (! $value instanceof UploadedFile) {
                continue;
            }

            $directory = sprintf('form-attachments/%d/%d', $form->getKey(), $submission->getKey());

            $storedPath = $value->storeAs(
                $directory,
                uniqid($fieldHandle.'_', true).'_'.$value->getClientOriginalName(),
                'local',
            );

            if (! is_string($storedPath)) {
                continue;
            }

            FormSubmissionAttachment::query()->create([
                'submission_id' => $submission->getKey(),
                'field_handle' => (string) $fieldHandle,
                'filename' => $value->getClientOriginalName(),
                'path' => $storedPath,
                'mime_type' => (string) ($value->getClientMimeType() ?: $value->getMimeType() ?: 'application/octet-stream'),
                'size' => (int) $value->getSize(),
            ]);
        }
    }
}
