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
            return self::authMail(
                subject: 'تأیید ایمیل حساب Ausbildung Match',
                title: 'ایمیلت را تأیید کن',
                eyebrow: 'یک قدم تا شروع مسیر حرفه‌ای',
                intro: 'برای فعال‌شدن کامل حساب و دسترسی به امکانات Ausbildung Match، آدرس ایمیل خود را تأیید کن.',
                actionLabel: 'تأیید ایمیل',
                actionUrl: $url,
                notice: 'این لینک تا ۶۰ دقیقه معتبر است. اگر این حساب را نساخته‌ای، این پیام را نادیده بگیر.',
                email: $notifiable->getEmailForVerification(),
            );
        });

        ResetPassword::createUrlUsing(function ($notifiable, string $token): string {
            return self::passwordResetFrontendUrl($notifiable, $token);
        });

        ResetPassword::toMailUsing(function ($notifiable, string $token): MailMessage {
            return self::authMail(
                subject: 'بازیابی رمز عبور Ausbildung Match',
                title: 'رمز جدیدت را تعیین کن',
                eyebrow: 'بازیابی امن حساب کاربری',
                intro: 'درخواست بازیابی رمز عبور حساب تو دریافت شد. برای انتخاب رمز جدید، روی دکمه زیر بزن.',
                actionLabel: 'تعیین رمز جدید',
                actionUrl: self::passwordResetFrontendUrl($notifiable, $token),
                notice: 'این لینک تا ۶۰ دقیقه معتبر و یک‌بارمصرف است. اگر این درخواست را ثبت نکرده‌ای، نیازی به انجام کاری نیست.',
                email: $notifiable->getEmailForPasswordReset(),
            );
        });
    }

    private static function authMail(
        string $subject,
        string $title,
        string $eyebrow,
        string $intro,
        string $actionLabel,
        string $actionUrl,
        string $notice,
        string $email,
    ): MailMessage {
        return (new MailMessage)
            ->subject($subject)
            // Keep the semantic action metadata available to Laravel and tests,
            // while the custom view controls the branded visual presentation.
            ->action($actionLabel, $actionUrl)
            ->view([
                'html' => 'emails.auth-action',
                'text' => 'emails.auth-action-text',
            ], compact(
                'title',
                'eyebrow',
                'intro',
                'actionLabel',
                'actionUrl',
                'notice',
                'email',
            ));
    }

    private static function passwordResetFrontendUrl($notifiable, string $token): string
    {
        return rtrim(config('frontend.url'), '/').'/reset-password?'.http_build_query([
            'token' => $token,
            'email' => $notifiable->getEmailForPasswordReset(),
        ]);
    }
}
