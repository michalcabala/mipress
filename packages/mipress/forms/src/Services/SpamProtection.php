<?php

declare(strict_types=1);

namespace MiPress\Forms\Services;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\RateLimiter;
use MiPress\Forms\Models\Form;
use MiPress\Forms\Models\FormField;

class SpamProtection
{
    public function check(Request $request, Form $form): bool
    {
        if ($this->isRateLimited($request)) {
            return true;
        }

        $mode = (string) ($form->spam_protection ?? FormField::SPAM_HONEYPOT);

        $honeypotSpam = in_array($mode, [FormField::SPAM_HONEYPOT, FormField::SPAM_BOTH], true)
            && $this->isHoneypotSpam($request);

        $recaptchaSpam = in_array($mode, [FormField::SPAM_RECAPTCHA, FormField::SPAM_BOTH], true)
            && $this->isRecaptchaSpam($request, $form);

        return $honeypotSpam || $recaptchaSpam;
    }

    private function isRateLimited(Request $request): bool
    {
        $key = 'mipress-forms:submit:'.$request->ip();

        if (RateLimiter::tooManyAttempts($key, 5)) {
            return true;
        }

        RateLimiter::hit($key, 900);

        return false;
    }

    private function isHoneypotSpam(Request $request): bool
    {
        if (filled($request->input('website'))) {
            return true;
        }

        $startedAt = (int) $request->input('_form_started_at', 0);

        if ($startedAt === 0) {
            return true;
        }

        return (time() - $startedAt) < 3;
    }

    private function isRecaptchaSpam(Request $request, Form $form): bool
    {
        $token = (string) $request->input('g-recaptcha-response');

        if ($token === '' || blank($form->recaptcha_secret_key)) {
            return true;
        }

        $response = Http::asForm()
            ->post('https://www.google.com/recaptcha/api/siteverify', [
                'secret' => $form->recaptcha_secret_key,
                'response' => $token,
                'remoteip' => $request->ip(),
            ]);

        if (! $response->ok()) {
            return true;
        }

        $payload = $response->json();

        if (! is_array($payload)) {
            return true;
        }

        if (! ((bool) ($payload['success'] ?? false))) {
            return true;
        }

        $score = (float) ($payload['score'] ?? 0.0);
        $action = (string) ($payload['action'] ?? '');

        if ($score < 0.5) {
            return true;
        }

        return ! in_array($action, ['', 'form_submit'], true);
    }
}
