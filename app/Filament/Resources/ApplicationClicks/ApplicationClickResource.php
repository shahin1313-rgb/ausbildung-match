<?php

namespace App\Filament\Resources\ApplicationClicks;

use App\Filament\Resources\ApplicationClicks\Pages\ListApplicationClicks;
use App\Models\ApplicationClick;
use BackedEnum;
use Filament\Resources\Resource;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class ApplicationClickResource extends Resource
{
    protected static ?string $model = ApplicationClick::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedCursorArrowRays;

    protected static string|UnitEnum|null $navigationGroup = 'گزارش‌ها';

    protected static ?string $navigationLabel = 'کلیک‌های درخواست';

    protected static ?string $modelLabel = 'کلیک درخواست';

    protected static ?string $pluralModelLabel = 'کلیک‌های درخواست';

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opportunity.title_de')->label('فرصت')->searchable(),
                TextColumn::make('user.email')->label('کاربر')->placeholder('مهمان')->searchable(),
                TextColumn::make('channel')->label('کانال')->badge(),
                TextColumn::make('clicked_at')->label('زمان')->dateTime()->sortable(),
            ])
            ->defaultSort('clicked_at', 'desc');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function canEdit($record): bool
    {
        return false;
    }

    public static function canDelete($record): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListApplicationClicks::route('/'),
        ];
    }
}
