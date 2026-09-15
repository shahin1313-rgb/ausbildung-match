<?php

use Illuminate\Support\Facades\Route;

Route::redirect('/email/verify', '/account')->name('verification.notice');

Route::view('/{path?}', 'app')
    ->where('path', '^(?!admin|api|sanctum|livewire|filament).*$')
    ->name('frontend');
