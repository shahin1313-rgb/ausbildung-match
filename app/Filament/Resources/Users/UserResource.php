<?php

namespace App\Filament\Resources\Users;

use App\Filament\Resources\Users\Pages\EditUser;
use App\Filament\Resources\Users\Pages\ListUsers;
use App\Models\User;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\TernaryFilter;
use Filament\Tables\Table;
use UnitEnum;

class UserResource extends Resource
{
    protected static ?string $model = User::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedUsers;

    protected static string|UnitEnum|null $navigationGroup = 'کاربران';

    protected static ?string $navigationLabel = 'کاربران';

    protected static ?string $modelLabel = 'کاربر';

    protected static ?string $pluralModelLabel = 'کاربران';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->maxLength(255),
            TextInput::make('email')
                ->label('ایمیل')
                ->email()
                ->required()
                ->unique(ignoreRecord: true)
                ->disabled(fn (): bool => ! auth()->user()?->is_super_admin),
            Toggle::make('is_admin')
                ->label('مدیر سیستم')
                ->disabled(fn (?User $record): bool =>
                    ! auth()->user()?->is_super_admin || auth()->id() === $record?->getKey()
                ),
            TextInput::make('current_password')
                ->label('رمز عبور فعلی شما')
                ->password()
                ->revealable()
                ->autocomplete('current-password')
                ->helperText('برای تغییر ایمیل یا نقش مدیریتی، تأیید دوباره هویت الزامی است.')
                ->required(fn (?User $record, callable $get): bool => $record !== null && (
                    $get('email') !== $record->email ||
                    (bool) $get('is_admin') !== (bool) $record->is_admin
                ))
                ->dehydrated(),
            Hidden::make('email_verified_at')->dehydrated(false),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('email')->label('ایمیل')->searchable(),
                IconColumn::make('is_admin')->label('مدیر')->boolean(),
                TextColumn::make('resumes_count')->label('رزومه‌ها')->counts('resumes'),
                TextColumn::make('favorite_opportunities_count')->label('نشان‌شده‌ها')->counts('favoriteOpportunities'),
                TextColumn::make('created_at')->label('عضویت')->dateTime()->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                TernaryFilter::make('is_admin')->label('مدیر سیستم'),
            ])
            ->recordActions([EditAction::make()]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        $actor = auth()->user();

        return $actor instanceof User
            && ($actor->is_super_admin || ! $record->is_admin);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListUsers::route('/'),
            'edit' => EditUser::route('/{record}/edit'),
        ];
    }
}
