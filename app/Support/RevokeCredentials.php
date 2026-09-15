<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RevokeCredentials
{
    public static function forUser(User $user, ?Request $request = null): void
    {
        // Database sessions are the project's configured session store in production.
        if (config('session.driver') === 'database') {
            DB::connection(config('session.connection'))
                ->table(config('session.table', 'sessions'))
                ->where('user_id', $user->getKey())->delete();
        }

        $user->tokens()->delete();
        $user->forceFill(['remember_token' => Str::random(60)])->saveQuietly();

        if ($request && Auth::guard('web')->id() === $user->getKey()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();
        }
    }
}
