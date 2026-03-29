<?php

use Illuminate\Support\Facades\Route;
use MiPress\Core\Http\Controllers\EntryController;

Route::get('/', [EntryController::class, 'home'])
    ->name('home');
