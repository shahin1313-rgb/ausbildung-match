<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Support\RevokeCredentials;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class EmailVerificationController extends Controller
{
    public function status(Request $request): JsonResponse
    {
        return response()->json(['email' => $request->user()->email, 'email_verified' => $request->user()->hasVerifiedEmail()]);
    }

    public function resend(Request $request): JsonResponse
    {
        if (! $request->user()->hasVerifiedEmail()) {
            $request->user()->sendEmailVerificationNotification();
        }

        return response()->json(['message' => 'اگر ایمیل شما هنوز تأیید نشده باشد، لینک جدید ارسال شد.']);
    }

    public function verify(Request $request, string $id, string $hash): JsonResponse
    {
        $user = User::find($id);
        if (! $user || ! hash_equals(sha1($user->getEmailForVerification()), $hash)) {
            return response()->json(['message' => 'لینک تأیید معتبر نیست.'], 403);
        }

        if (! $user->hasVerifiedEmail() && $user->markEmailAsVerified()) {
            event(new Verified($user));
        }

        return response()->json(['message' => 'ایمیل شما تأیید شد.']);
    }

    public function change(Request $request): JsonResponse
    {
        $data = $request->validate([
            'email' => ['required', 'email:rfc', 'max:190', Rule::unique('users')->ignore($request->user()->id)],
            'current_password' => ['required', 'string'],
        ]);

        if (! Hash::check($data['current_password'], $request->user()->password)) {
            throw ValidationException::withMessages(['current_password' => ['رمز عبور فعلی صحیح نیست.']]);
        }

        if ($request->user()->email !== $data['email']) {
            $user = $request->user();
            $user->email = $data['email'];
            $user->save(); // User model resets verification and revokes all database sessions and tokens.
            try {
                $user->sendEmailVerificationNotification();
            } catch (\Throwable $exception) {
                Log::error('Email change verification delivery failed.', ['exception_class' => get_class($exception)]);
            }
            RevokeCredentials::forUser($user, $request);
        }

        return response()->json(['message' => 'آدرس ایمیل ثبت شد. برای ورود دوباره، ایمیل جدید را تأیید کنید.']);
    }
}
