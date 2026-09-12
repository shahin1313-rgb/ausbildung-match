<?php

namespace App\Filament\Resources\Sources;

use App\Filament\Resources\Sources\Pages\CreateSource;
use App\Filament\Resources\Sources\Pages\EditSource;
use App\Filament\Resources\Sources\Pages\ListSources;
use App\Models\Source;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class SourceResource extends Resource
{
    protected static ?string $model = Source::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedLink;

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت فرصت‌ها';

    protected static ?string $navigationLabel = 'منابع';

    protected static ?string $modelLabel = 'منبع';

    protected static ?string $pluralModelLabel = 'منابع';

    protected static ?int $navigationSort = 3;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name')->label('نام')->required()->unique(ignoreRecord: true),
            TextInput::make('base_url')->label('وب‌سایت')->url()->required(),
            TextInput::make('feed_url')->label('آدرس فید یا API')->url(),
            Select::make('sync_method')->label('روش همگام‌سازی')->options([
                'manual' => 'دستی',
                'api' => 'API',
                'feed' => 'Feed',
            ])->required(),
            Toggle::make('is_enabled')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('نام')->searchable()->sortable(),
                TextColumn::make('base_url')->label('وب‌سایت')->url(fn (Source $record): string => $record->base_url)->openUrlInNewTab()->limit(45),
                TextColumn::make('sync_method')->label('روش')->badge(),
                TextColumn::make('opportunities_count')->label('فرصت‌ها')->counts('opportunities'),
                TextColumn::make('last_synced_at')->label('آخرین بروزرسانی')->since()->placeholder('—'),
                IconColumn::make('is_enabled')->label('فعال')->boolean(),
            ])
            ->recordActions([EditAction::make()])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => ListSources::route('/'),
            'create' => CreateSource::route('/create'),
            'edit' => EditSource::route('/{record}/edit'),
        ];
    }
}
