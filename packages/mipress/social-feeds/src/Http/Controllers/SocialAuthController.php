<?php

namespace MiPress\SocialFeeds\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Laravel\Socialite\Facades\Socialite;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Models\SocialAccount;
use MiPress\SocialFeeds\Providers\FacebookProvider;
use Throwable;

class SocialAuthController extends Controller
{
    public function redirect(string $platform)
    {
        $enum = SocialPlatform::tryFrom($platform);

        if (! $enum || ! in_array($enum, SocialPlatform::enabled())) {
            abort(404, "Platforma [{$platform}] není povolena.");
        }

        $providerClass = $this->resolveProviderClass($enum);
        $scopes = app($providerClass)->requiredScopes();

        $driver = Socialite::driver($platform);

        if ((bool) config("social-feeds.providers.{$platform}.stateless", false)) {
            $driver = $driver->stateless();
        }

        return $driver
            ->setScopes($scopes)
            ->redirect();
    }

    public function callback(string $platform, Request $request)
    {
        $enum = SocialPlatform::tryFrom($platform);

        if (! $enum) {
            abort(404);
        }

        try {
            $driver = Socialite::driver($platform);

            if ((bool) config("social-feeds.providers.{$platform}.stateless", false)) {
                $driver = $driver->stateless();
            }

            $socialiteUser = $driver->user();
        } catch (Throwable $e) {
            Log::error('Social OAuth callback failed.', [
                'platform' => $platform,
                'message' => $e->getMessage(),
                'exception' => get_class($e),
                'request_url' => $request->fullUrl(),
                'request_query' => $request->query(),
            ]);

            $errorMessage = str($e->getMessage())->contains('state')
                ? 'Neplatný OAuth stav. Zkuste zapnout SOCIAL_FACEBOOK_STATELESS=true nebo zkontrolujte session/cookies.'
                : $e->getMessage();

            return redirect()
                ->route('filament.admin.resources.social-accounts.index')
                ->with('error', "Připojení k {$enum->label()} selhalo: {$errorMessage}");
        }

        // For Facebook, fetch managed pages and store page-level tokens
        if ($enum === SocialPlatform::Facebook) {
            return $this->handleFacebookPages($socialiteUser, $enum);
        }

        SocialAccount::updateOrCreate(
            [
                'platform' => $enum,
                'platform_account_id' => $socialiteUser->getId(),
            ],
            [
                'name' => $socialiteUser->getName() ?? $socialiteUser->getNickname() ?? 'Neznámý',
                'username' => $socialiteUser->getNickname(),
                'access_token' => Crypt::encryptString($socialiteUser->token),
                'refresh_token' => $socialiteUser->refreshToken
                    ? Crypt::encryptString($socialiteUser->refreshToken)
                    : null,
                'token_expires_at' => $socialiteUser->expiresIn
                    ? now()->addSeconds($socialiteUser->expiresIn)
                    : null,
                'avatar_url' => $socialiteUser->getAvatar(),
                'meta' => $socialiteUser->getRaw(),
                'connected_by' => auth()->id(),
            ]
        );

        return redirect()
            ->route('filament.admin.resources.social-accounts.index')
            ->with('success', "{$enum->label()} účet úspěšně propojen.");
    }

    private function handleFacebookPages(mixed $socialiteUser, SocialPlatform $enum)
    {
        $userToken = $socialiteUser->token;
        $version = config('social-feeds.providers.facebook.graph_version', 'v23.0');

        try {
            $response = Http::withToken($userToken)
                ->get("https://graph.facebook.com/{$version}/me/accounts", [
                    'fields' => 'id,name,access_token,picture{url},category,link',
                    'limit' => 100,
                ]);

            $pages = $response->json('data', []);
        } catch (Throwable $e) {
            Log::error('Facebook: Failed to fetch managed pages.', [
                'message' => $e->getMessage(),
            ]);

            return redirect()
                ->route('filament.admin.resources.social-accounts.index')
                ->with('error', "Nepodařilo se načíst spravované stránky: {$e->getMessage()}");
        }

        if (empty($pages)) {
            // Fallback: store user account if no pages found
            SocialAccount::updateOrCreate(
                [
                    'platform' => $enum,
                    'platform_account_id' => $socialiteUser->getId(),
                ],
                [
                    'name' => $socialiteUser->getName() ?? 'Neznámý',
                    'username' => $socialiteUser->getNickname(),
                    'access_token' => Crypt::encryptString($userToken),
                    'token_expires_at' => $socialiteUser->expiresIn
                        ? now()->addSeconds($socialiteUser->expiresIn)
                        : null,
                    'avatar_url' => $socialiteUser->getAvatar(),
                    'meta' => $socialiteUser->getRaw(),
                    'connected_by' => auth()->id(),
                ]
            );

            return redirect()
                ->route('filament.admin.resources.social-accounts.index')
                ->with('warning', 'Žádné spravované stránky nenalezeny. Uložen uživatelský účet.');
        }

        // Store pages in cache for selection page
        $cacheKey = 'social-feeds:facebook-pages:'.auth()->id();
        Cache::put($cacheKey, [
            'pages' => $pages,
            'user_id' => $socialiteUser->getId(),
            'user_name' => $socialiteUser->getName(),
            'connected_by' => auth()->id(),
        ], now()->addMinutes(15));

        return redirect('/mpcp/select-facebook-pages');
    }

    private function resolveProviderClass(SocialPlatform $platform): string
    {
        return match ($platform) {
            SocialPlatform::Facebook => FacebookProvider::class,
            default => throw new \InvalidArgumentException('Provider nenalezen.'),
        };
    }
}
