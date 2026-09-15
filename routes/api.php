<?php

use App\Http\Controllers\Api\V1\ApplicationClickController;
use App\Http\Controllers\Api\V1\AuthController;
use App\Http\Controllers\Api\V1\EmailVerificationController;
use App\Http\Controllers\Api\V1\PasswordController;
use App\Http\Controllers\Api\V1\FavoriteController;
use App\Http\Controllers\Api\V1\GermanCvController;
use App\Http\Controllers\Api\V1\MetaController;
use App\Http\Controllers\Api\V1\OpportunityController;
use App\Http\Controllers\Api\V1\ProfileController;
use App\Http\Controllers\Api\V1\ResumeController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/meta', MetaController::class);
    Route::get('/opportunities', [OpportunityController::class, 'index']);
    Route::get('/opportunities/{opportunity}', [OpportunityController::class, 'show']);

    Route::prefix('auth')->middleware('throttle:auth')->group(function (): void {
        Route::post('/register', [AuthController::class, 'register']);
        Route::post('/login', [AuthController::class, 'login']);
    });

    Route::get('/auth/email/verify/{id}/{hash}', [EmailVerificationController::class, 'verify'])
        ->middleware(['signed:relative', 'throttle:10,1'])->name('verification.verify');
    Route::post('/auth/forgot-password', [PasswordController::class, 'forgot'])
        ->middleware('throttle:password-recovery');
    Route::post('/auth/reset-password', [PasswordController::class, 'reset'])
        ->middleware('throttle:password-recovery');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);
        Route::get('/auth/email/status', [EmailVerificationController::class, 'status']);
        Route::post('/auth/email/resend', [EmailVerificationController::class, 'resend'])
            ->middleware('throttle:verification');
        Route::put('/auth/email', [EmailVerificationController::class, 'change'])
            ->middleware('throttle:auth');
        Route::put('/auth/password', [PasswordController::class, 'change'])
            ->middleware('throttle:auth');

        Route::middleware('verified')->group(function (): void {
            Route::get('/profile', [ProfileController::class, 'show']);
            Route::put('/profile', [ProfileController::class, 'update']);

            Route::get('/resumes', [ResumeController::class, 'index']);
            Route::post('/resumes', [ResumeController::class, 'store'])->middleware('throttle:10,1');
            Route::delete('/resumes/{resume}', [ResumeController::class, 'destroy']);

            Route::get('/favorites', [FavoriteController::class, 'index']);
            Route::put('/favorites/{opportunity}', [FavoriteController::class, 'store']);
            Route::delete('/favorites/{opportunity}', [FavoriteController::class, 'destroy']);

            Route::get('/german-cv', [GermanCvController::class, 'show']);
            Route::put('/german-cv', [GermanCvController::class, 'update']);
            Route::post('/application-clicks/{opportunity}', ApplicationClickController::class)
                ->middleware('throttle:30,1');
        });
    });
});
