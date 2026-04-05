<?php

use Illuminate\Support\Facades\Route;
use MiPress\SocialFeeds\Http\Controllers\SocialAuthController;

Route::middleware(['web', 'auth'])
    ->prefix('mpcp/social')
    ->group(function () {
        Route::get('connect/{platform}', [SocialAuthController::class, 'redirect'])
            ->name('social.auth.redirect');
        Route::get('callback/{platform}', [SocialAuthController::class, 'callback'])
            ->name('social.auth.callback');
    });
