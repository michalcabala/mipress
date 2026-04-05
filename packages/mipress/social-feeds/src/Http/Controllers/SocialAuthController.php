<?php

namespace MiPress\SocialFeeds\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Crypt;
use Laravel\Socialite\Facades\Socialite;
use MiPress\SocialFeeds\Enums\SocialPlatform;
use MiPress\SocialFeeds\Models\SocialAccount;

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

        return Socialite::driver($platform)
            ->scopes($scopes)
            ->redirect();
    }

    public function callback(string $platform, Request $request)
    {
        $enum = SocialPlatform::tryFrom($platform);

        if (! $enum) {
            abort(404);
        }

        try {
            $socialiteUser = Socialite::driver($platform)->user();
        } catch (\Exception $e) {
            return redirect()
                ->route('filament.admin.resources.social-accounts.index')
                ->with('error', "Připojení k {$enum->label()} selhalo: {$e->getMessage()}");
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

    private function resolveProviderClass(SocialPlatform $platform): string
    {
        return match ($platform) {
            SocialPlatform::Facebook => \MiPress\SocialFeeds\Providers\FacebookProvider::class,
            default => throw new \InvalidArgumentException('Provider nenalezen.'),
        };
    }
}
