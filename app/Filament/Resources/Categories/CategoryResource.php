<?php

namespace App\Filament\Resources\Categories;

use App\Filament\Resources\Categories\Pages\CreateCategory;
use App\Filament\Resources\Categories\Pages\EditCategory;
use App\Filament\Resources\Categories\Pages\ListCategories;
use App\Models\Category;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use UnitEnum;

class CategoryResource extends Resource
{
    protected static ?string $model = Category::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedTag;

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت فرصت‌ها';

    protected static ?string $navigationLabel = 'دسته‌بندی‌ها';

    protected static ?string $modelLabel = 'دسته‌بندی';

    protected static ?string $pluralModelLabel = 'دسته‌بندی‌ها';

    protected static ?int $navigationSort = 2;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            TextInput::make('name_fa')->label('نام فارسی')->required()->maxLength(255),
            TextInput::make('name_de')->label('نام آلمانی')->required()->maxLength(255),
            TextInput::make('slug')->label('نامک')->required()->unique(ignoreRecord: true)->maxLength(255),
            TextInput::make('sort_order')->label('ترتیب')->numeric()->default(0)->required(),
            Toggle::make('is_active')->label('فعال')->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name_fa')->label('نام فارسی')->searchable()->sortable(),
                TextColumn::make('name_de')->label('نام آلمانی')->searchable(),
                TextColumn::make('opportunities_count')->label('فرصت‌ها')->counts('opportunities')->sortable(),
                TextColumn::make('sort_order')->label('ترتیب')->sortable(),
                IconColumn::make('is_active')->label('فعال')->boolean(),
            ])
            ->defaultSort('sort_order')
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
            'index' => ListCategories::route('/'),
            'create' => CreateCategory::route('/create'),
            'edit' => EditCategory::route('/{record}/edit'),
        ];
    }
}
