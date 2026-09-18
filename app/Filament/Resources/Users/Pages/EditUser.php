<?php

namespace App\Filament\Resources\Users\Pages;

use App\Filament\Resources\Users\UserResource;
use App\Models\User;
use App\Services\AdminUserUpdater;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Database\Eloquent\Model;

class EditUser extends EditRecord
{
    protected static string $resource = UserResource::class;

    protected function handleRecordUpdate(Model $record, array $data): Model
    {
        /** @var User $actor */
        $actor = auth()->user();

        app(AdminUserUpdater::class)->update(
            actor: $actor,
            target: $record,
            data: $data,
            currentPassword: $data['current_password'] ?? null,
        );

        return $record->refresh();
    }
}
