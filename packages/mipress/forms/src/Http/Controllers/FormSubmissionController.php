<?php

declare(strict_types=1);

namespace MiPress\Forms\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Mail;
use MiPress\Forms\Http\Requests\SubmitFormRequest;
use MiPress\Forms\Mail\FormAutoReply;
use MiPress\Forms\Mail\FormSubmissionNotification;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormSubmission;
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

        $submission = FormSubmission::query()->create([
            'form_id' => $form->getKey(),
            'data' => $validated,
            'ip_address' => $request->ip(),
            'user_agent' => (string) $request->userAgent(),
        ]);

        Mail::to(config('mail.from.address'))->queue(new FormSubmissionNotification($form, $submission));

        if ((bool) $form->auto_reply_enabled) {
            $email = $validated['email'] ?? null;

            if (is_string($email) && $email !== '') {
                Mail::to($email)->queue(new FormAutoReply($form, $submission));
            }
        }

        return back()->with('mipress_form_success', $form->success_message);
    }
}
