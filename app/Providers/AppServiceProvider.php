<?php

namespace App\Providers;

use Illuminate\Cache\RateLimiting\Limit;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\URL;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        //
    }

    public function boot(): void
    {
        Model::preventLazyLoading(! app()->isProduction());

        RateLimiter::for('auth', function (Request $request): Limit {
            return Limit::perMinute(10)->by($request->ip());
        });

        RateLimiter::for('password-recovery', function (Request $request): array {
            $key = hash('sha256', strtolower(trim((string) $request->input('email'))));

            return [
                Limit::perMinute(5)->by('recovery-ip:'.$request->ip()),
                Limit::perMinute(2)->by('recovery-email:'.$request->ip().':'.$key),
            ];
        });

        RateLimiter::for('verification', function (Request $request): array {
            return [
                Limit::perMinute(5)->by('verify-ip:'.$request->ip()),
                Limit::perMinute(1)->by('verify-user:'.$request->user()->getAuthIdentifier()),
            ];
        });

        VerifyEmail::createUrlUsing(function ($notifiable): string {
            $signed = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
                'id' => $notifiable->getKey(),
                'hash' => sha1($notifiable->getEmailForVerification()),
            ], absolute: false);
            $parts = parse_url($signed);
            parse_str($parts['query'] ?? '', $query);
            $query['id'] = $notifiable->getKey();
            $query['hash'] = sha1($notifiable->getEmailForVerification());

            return rtrim(config('frontend.url'), '/').'/verify-email?'.http_build_query($query);
        });

        VerifyEmail::toMailUsing(function ($notifiable, string $url): MailMessage {
            return (new MailMessage)
                ->subject('تأیید ایمیل حساب Ausbildung Match')
                ->line('برای تأیید آدرس ایمیل حساب خود، روی دکمه زیر بزنید.')
                ->action('تأیید ایمیل', $url)
                ->line('این لینک تا ۶۰ دقیقه معتبر است. اگر شما این حساب را نساخته‌اید، این پیام را نادیده بگیرید.');
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            return self::passwordResetFrontendUrl($notifiable, $token);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            return (new MailMessage)
                ->subject('بازیابی رمز عبور Ausbildung Match')
                ->line('درخواست بازیابی رمز عبور حساب شما دریافت شده است.')
                ->action('تعیین رمز جدید', self::passwordResetFrontendUrl($notifiable, $token))
                ->line('این لینک تا ۶۰ دقیقه معتبر و یک‌بارمصرف است. اگر شما درخواست نداده‌اید، این پیام را نادیده بگیرید.');
        });
    }

    private static function passwordResetFrontendUrl($notifiable, string $token): string
    {
        return rtrim(config('frontend.url'), '/').'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
