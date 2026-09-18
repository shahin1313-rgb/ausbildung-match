<?php

namespace App\Services;

use App\Models\AdminAuditLog;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AdminUserUpdater
{
    public function update(User $actor, User $target, array $data, ?string $currentPassword): void
    {
        DB::transaction(function () use ($actor, $target, $data, $currentPassword): void {
            $target = User::query()->lockForUpdate()->findOrFail($target->getKey());
            $changes = Arr::only($data, ['name', 'email', 'is_admin']);
            $roleChanges = array_key_exists('is_admin', $changes)
                && (bool) $changes['is_admin'] !== (bool) $target->is_admin;
            $emailChanges = array_key_exists('email', $changes)
                && $changes['email'] !== $target->email;

            if ($roleChanges || $emailChanges) {
                $this->authorizeSensitiveChange($actor, $target, $roleChanges, $currentPassword);
            }

            if ($roleChanges && ! $changes['is_admin']) {
                $otherAdminsExist = User::query()
                    ->where('is_admin', true)
                    ->whereKeyNot($target->getKey())
                    ->lockForUpdate()
                    ->exists();

                if (! $otherAdminsExist) {
                    throw ValidationException::withMessages([
                        'data.is_admin' => 'حذف نقش آخرین مدیر سیستم امکان‌پذیر نیست.',
                    ]);
                }
            }

            $before = $target->only(['name', 'email', 'is_admin']);
            $target->fill($changes);

            if (! $target->isDirty()) {
                return;
            }

            $dirtyFields = array_keys($target->getDirty());
            $target->save();

            AdminAuditLog::query()->create([
                'actor_id' => $actor->getKey(),
                'subject_id' => $target->getKey(),
                'event' => $roleChanges ? 'admin_role_changed' : 'admin_user_updated',
                'old_values' => Arr::only($before, $dirtyFields),
                'new_values' => Arr::only($target->only(['name', 'email', 'is_admin']), $dirtyFields),
                'ip_address' => request()->ip(),
                'user_agent' => mb_substr((string) request()->userAgent(), 0, 1024),
                'created_at' => now(),
            ]);
        });
    }

    private function authorizeSensitiveChange(
        User $actor,
        User $target,
        bool $roleChanges,
        ?string $currentPassword,
    ): void {
        if (! $actor->is_super_admin) {
            throw new AuthorizationException('فقط مدیر ارشد می‌تواند ایمیل یا نقش مدیریتی را تغییر دهد.');
        }

        if ($roleChanges && $actor->is($target)) {
            throw ValidationException::withMessages([
                'data.is_admin' => 'نمی‌توانید نقش مدیریتی خودتان را تغییر دهید.',
            ]);
        }

        if (! is_string($currentPassword) || ! Hash::check($currentPassword, $actor->password)) {
            throw ValidationException::withMessages([
                'data.current_password' => 'رمز عبور فعلی صحیح نیست.',
            ]);
        }
    }
}
