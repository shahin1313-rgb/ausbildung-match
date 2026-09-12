<?php

use App\Http\Controllers\Api\V1\ApplicationClickController;
use App\Http\Controllers\Api\V1\AuthController;
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

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('/auth/me', [AuthController::class, 'me']);
        Route::post('/auth/logout', [AuthController::class, 'logout']);

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
