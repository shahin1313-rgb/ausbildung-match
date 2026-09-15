<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RevokeCredentials;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Password;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\Validation\Rules\Password as PasswordRule;

class PasswordController extends Controller
{
    public function forgot(Request $request): JsonResponse
    {
        $data = $request->validate(['email' => ['required', 'email:rfc', 'max:190']]);
        // Broker handles token expiry, storage, and throttling; its result is never exposed.
        try {
            Password::sendResetLink($data);
        } catch (\Throwable $exception) {
            // A transport failure must not distinguish an existing address from an unknown one.
            Log::error('Password reset mail delivery failed.', ['exception_class' => get_class($exception)]);
        }

        return response()->json(['message' => 'اگر این ایمیل ثبت شده باشد، لینک بازیابی برای آن ارسال می‌شود.']);
    }

    public function reset(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190'],
            'token' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        $status = Password::reset($data, function (User $user, string $password) use ($request): void {
            $user->forceFill(['password' => $password])->save();
            RevokeCredentials::forUser($user, $request);
        });

        if ($status !== Password::PASSWORD_RESET) {
            throw ValidationException::withMessages(['token' => ['لینک بازیابی نامعتبر یا منقضی شده است.']]);
        }

        return response()->json(['message' => 'رمز عبور تغییر کرد. دوباره وارد شوید.']);
    }

    public function change(Request $request): JsonResponse
    {
        $data = $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'confirmed', PasswordRule::min(8)->letters()->numbers()],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => ['رمز عبور فعلی صحیح نیست.']]);
        }

        $user = $request->user();
        $user->forceFill(['password' => $data['password']])->save();
        RevokeCredentials::forUser($user, $request);

        return response()->json(['message' => 'رمز عبور تغییر کرد. دوباره وارد شوید.']);
    }
}
