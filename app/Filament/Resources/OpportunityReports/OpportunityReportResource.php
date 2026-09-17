<?php

namespace App\Filament\Resources\OpportunityReports;

use App\Filament\Resources\OpportunityReports\Pages\EditOpportunityReport;
use App\Filament\Resources\OpportunityReports\Pages\ListOpportunityReports;
use App\Models\OpportunityReport;
use BackedEnum;
use Filament\Actions\EditAction;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OpportunityReportResource extends Resource
{
    protected static ?string $model = OpportunityReport::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedFlag;

    protected static string|UnitEnum|null $navigationGroup = 'گزارش‌ها';

    protected static ?string $navigationLabel = 'گزارش آگهی‌ها';

    protected static ?string $modelLabel = 'گزارش آگهی';

    protected static ?string $pluralModelLabel = 'گزارش آگهی‌ها';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات گزارش')
                ->columns(2)
                ->schema([
                    TextInput::make('opportunity_title')
                        ->label('فرصت')
                        ->default(fn (?OpportunityReport $record): string => $record
                            ? $record->opportunity->title_de.' — '.$record->opportunity->title_fa
                            : '')
                        ->disabled()
                        ->dehydrated(false),
                    TextInput::make('reporter_email')
                        ->label('ایمیل گزارش‌دهنده')
                        ->disabled()
                        ->dehydrated(false),
                    Select::make('reason')
                        ->label('نوع مشکل')
                        ->options(self::reasonLabels())
                        ->disabled()
                        ->dehydrated(false),
                    Textarea::make('details')
                        ->label('توضیحات گزارش‌دهنده')
                        ->rows(5)
                        ->disabled()
                        ->dehydrated(false)
                        ->columnSpanFull(),
                ]),
            Section::make('رسیدگی مدیر')
                ->schema([
                    Select::make('status')
                        ->label('وضعیت رسیدگی')
                        ->options(self::statusLabels())
                        ->required(),
                    Textarea::make('admin_notes')
                        ->label('یادداشت داخلی مدیر')
                        ->helperText('این یادداشت برای کاربر نمایش داده نمی‌شود.')
                        ->rows(5)
                        ->maxLength(5000),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('opportunity.title_de')
                    ->label('فرصت')
                    ->description(fn (OpportunityReport $record): string => $record->opportunity->title_fa)
                    ->searchable()
                    ->sortable(),
                TextColumn::make('reason')
                    ->label('نوع مشکل')
                    ->formatStateUsing(fn (string $state): string => self::reasonLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'scam' => 'danger',
                        'broken_link', 'expired' => 'warning',
                        'incorrect_info' => 'info',
                        default => 'gray',
                    }),
                TextColumn::make('reporter_email')
                    ->label('گزارش‌دهنده')
                    ->searchable()
                    ->description(fn (OpportunityReport $record): string => $record->user_id ? 'کاربر ثبت‌شده' : 'مهمان'),
                TextColumn::make('status')
                    ->label('وضعیت')
                    ->formatStateUsing(fn (string $state): string => self::statusLabels()[$state] ?? $state)
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'pending' => 'warning',
                        'reviewing' => 'info',
                        'resolved' => 'success',
                        'dismissed' => 'gray',
                    }),
                TextColumn::make('created_at')
                    ->label('زمان ثبت')
                    ->dateTime()
                    ->sortable(),
                TextColumn::make('resolved_at')
                    ->label('زمان پایان')
                    ->dateTime()
                    ->placeholder('—')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('reason')->label('نوع مشکل')->options(self::reasonLabels()),
                SelectFilter::make('status')->label('وضعیت رسیدگی')->options(self::statusLabels()),
            ])
            ->recordActions([
                EditAction::make()->label('بررسی'),
            ]);
    }

    public static function canCreate(): bool
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
            'index' => ListOpportunityReports::route('/'),
            'edit' => EditOpportunityReport::route('/{record}/edit'),
        ];
    }

    private static function reasonLabels(): array
    {
        return [
            'scam' => 'گزارش کلاهبرداری',
            'broken_link' => 'لینک خراب',
            'incorrect_info' => 'اطلاعات اشتباه',
            'expired' => 'فرصت منقضی',
            'other' => 'سایر',
        ];
    }

    private static function statusLabels(): array
    {
        return [
            'pending' => 'در انتظار بررسی',
            'reviewing' => 'در حال بررسی',
            'resolved' => 'رسیدگی‌شده',
            'dismissed' => 'ردشده',
        ];
    }
}
