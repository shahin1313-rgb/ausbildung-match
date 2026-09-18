<?php

namespace App\Filament\Resources\Companies;

use App\Filament\Resources\Companies\Pages\EditCompany;
use App\Filament\Resources\Companies\Pages\ListCompanies;
use App\Models\Company;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class CompanyResource extends Resource
{
    protected static ?string $model = Company::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBuildingOffice;

    protected static string|UnitEnum|null $navigationGroup = 'کارفرمایان';

    protected static ?string $navigationLabel = 'تأیید شرکت‌ها';

    protected static ?string $modelLabel = 'شرکت';

    protected static ?string $pluralModelLabel = 'شرکت‌ها';

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات شرکت')
                ->columns(2)
                ->schema([
                    TextInput::make('name')->label('نام شرکت')->disabled()->dehydrated(false),
                    TextInput::make('legal_name')->label('نام حقوقی')->disabled()->dehydrated(false),
                    TextInput::make('owner.email')->label('ایمیل مالک')->disabled()->dehydrated(false),
                    TextInput::make('contact_email')->label('ایمیل تماس')->disabled()->dehydrated(false),
                    TextInput::make('website')->label('وب‌سایت')->disabled()->dehydrated(false),
                    TextInput::make('city')->label('شهر')->disabled()->dehydrated(false),
                ]),
            Section::make('بررسی و تأیید')
                ->schema([
                    Select::make('status')
                        ->label('وضعیت')
                        ->options(self::statusLabels())
                        ->required(),
                    Select::make('verification_method')
                        ->label('روش تأیید')
                        ->options(self::verificationMethodLabels())
                        ->nullable(),
                    Textarea::make('verification_notes')
                        ->label('یادداشت داخلی بررسی')
                        ->helperText('این یادداشت برای کارفرما نمایش داده نمی‌شود.')
                        ->maxLength(5000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')->label('شرکت')->searchable()->sortable(),
                TextColumn::make('owner.email')->label('مالک')->searchable(),
                TextColumn::make('city')->label('شهر')->searchable(),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (string $state): string => self::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'verified' => 'success',
                        'under_review' => 'info',
                        'suspended' => 'danger',
                        default => 'warning',
                    }),
                TextColumn::make('verification_method')
                    ->label('روش تأیید')
                    ->formatStateUsing(fn (?string $state): string => self::verificationMethodLabels()[$state] ?? '—'),
                TextColumn::make('created_at')->label('ثبت')->dateTime()->sortable(),
                TextColumn::make('verified_at')->label('تأیید')->dateTime()->placeholder('—')->sortable(),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options(self::statusLabels()),
            ])
            ->recordActions([EditAction::make()->label('بررسی')]);
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getPages(): array
    {
        return [
            'index' => ListCompanies::route('/'),
            'edit' => EditCompany::route('/{record}/edit'),
        ];
    }

    private static function statusLabels(): array
    {
        return [
            'pending' => 'در انتظار بررسی',
            'under_review' => 'در حال بررسی',
            'verified' => 'تأییدشده',
            'suspended' => 'تعلیق‌شده',
        ];
    }

    private static function verificationMethodLabels(): array
    {
        return [
            'admin_review' => 'بررسی مدیر',
            'email_domain' => 'تأیید دامنه ایمیل',
            'documents' => 'بررسی مدارک شرکت',
        ];
    }
}
