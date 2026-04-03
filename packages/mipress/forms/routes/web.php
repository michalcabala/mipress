<?php

declare(strict_types=1);

use Illuminate\Support\Facades\Route;
use MiPress\Forms\Http\Controllers\FormSubmissionController;

Route::post('/mipress/form/{form:handle}/submit', [FormSubmissionController::class, 'submit'])
    ->name('mipress.form.submit');
