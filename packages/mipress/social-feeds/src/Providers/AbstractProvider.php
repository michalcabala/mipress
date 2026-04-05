<?php

namespace MiPress\SocialFeeds\Providers;

use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\RequestException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use MiPress\SocialFeeds\Contracts\SocialProvider;
use MiPress\SocialFeeds\Models\SocialAccount;

abstract class AbstractProvider implements SocialProvider
{
    protected function httpClient(SocialAccount $account): PendingRequest
    {
        $timeout = config('social-feeds.refresh.timeout', 30);

        return Http::withToken($account->decrypted_token)
            ->timeout($timeout)
            ->retry(2, 500)
            ->throw();
    }

    protected function safeApiCall(SocialAccount $account, callable $callback): mixed
    {
        try {
            $result = $callback();
            $account->clearErrors();
            $account->touch('last_verified_at');

            return $result;
        } catch (RequestException $e) {
            $errorBody = $e->response?->json() ?? [];
            $message = $errorBody['error']['message']
                ?? $errorBody['error_description']
                ?? $e->getMessage();
            $code = (string) ($errorBody['error']['code'] ?? $e->getCode());

            $account->recordError($message, $code);

            Log::warning("Social API error [{$account->platform->value}]: {$message}", [
                'account_id' => $account->id,
                'code' => $code,
            ]);

            return null;
        }
    }
}
