<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Http\Requests\RegisterRequest;
use App\Models\User;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function register(RegisterRequest $request): JsonResponse
    {
        $user = DB::transaction(function () use ($request): User {
            $user = User::create($request->safe()->only(['name', 'email', 'password']));
            $user->profile()->create([
                'german_level' => 'none',
                'work_experience_years' => 0,
                'relocation_ready' => true,
            ]);

            return $user;
        });

        Auth::login($user);
        $request->session()->regenerate();
        $sent = true;
        try {
            $user->sendEmailVerificationNotification();
        } catch (\Throwable $exception) {
            $sent = false;
            Log::error('Registration verification mail delivery failed.', ['exception_class' => get_class($exception)]);
        }

        return response()->json([
            'message' => $sent ? 'حساب ساخته شد. لینک تأیید به ایمیل شما ارسال شد.' : 'حساب ساخته شد، ولی ارسال ایمیل انجام نشد. بعداً دوباره درخواست کنید.',
            'user' => $this->payload($user),
        ], 201);
    }

    public function login(LoginRequest $request): JsonResponse
    {
        $credentials = $request->safe()->only(['email', 'password']);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => ['ایمیل یا رمز عبور صحیح نیست.'],
            ]);
        }

        $request->session()->regenerate();

        return response()->json([
            'message' => 'ورود با موفقیت انجام شد.',
            'user' => $this->payload($request->user()),
        ]);
    }

    public function me(Request $request): JsonResponse
    {
        return response()->json([
            'user' => $this->payload($request->user()->loadMissing('profile')),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        Auth::guard('web')->logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return response()->json(['message' => 'از حساب خارج شدید.']);
    }

    private function payload(User $user): array
    {
        $user->loadMissing('profile');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'email_verified' => $user->hasVerifiedEmail(),
            'is_admin' => $user->is_admin,
            'profile_completed' => $user->profile?->german_level !== 'none',
        ];
    }
}
