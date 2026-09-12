<?php

namespace App\Filament\Resources\Opportunities;

use App\Filament\Resources\Opportunities\Pages\CreateOpportunity;
use App\Filament\Resources\Opportunities\Pages\EditOpportunity;
use App\Filament\Resources\Opportunities\Pages\ListOpportunities;
use App\Models\Opportunity;
use BackedEnum;
use Filament\Actions\BulkActionGroup;
use Filament\Actions\DeleteBulkAction;
use Filament\Actions\EditAction;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\DateTimePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\Toggle;
use Filament\Resources\Resource;
use Filament\Schemas\Components\Section;
use Filament\Schemas\Schema;
use Filament\Support\Icons\Heroicon;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;
use UnitEnum;

class OpportunityResource extends Resource
{
    protected static ?string $model = Opportunity::class;

    protected static string|BackedEnum|null $navigationIcon = Heroicon::OutlinedBriefcase;

    protected static string|UnitEnum|null $navigationGroup = 'مدیریت فرصت‌ها';

    protected static ?string $navigationLabel = 'فرصت‌های آوسبیلدونگ';

    protected static ?string $modelLabel = 'فرصت';

    protected static ?string $pluralModelLabel = 'فرصت‌ها';

    protected static ?int $navigationSort = 1;

    public static function form(Schema $schema): Schema
    {
        return $schema->components([
            Section::make('اطلاعات اصلی')
                ->columns(2)
                ->schema([
                    TextInput::make('title_fa')->label('عنوان فارسی')->required()->maxLength(255),
                    TextInput::make('title_de')->label('عنوان آلمانی')->required()->maxLength(255),
                    TextInput::make('employer_name')->label('کارفرما')->required()->maxLength(255),
                    TextInput::make('slug')->label('نشانی یکتا')->helperText('در صورت خالی بودن خودکار ساخته می‌شود.')->maxLength(255),
                    Select::make('category_id')->label('دسته‌بندی')->relationship('category', 'name_fa')->searchable()->preload()->required(),
                    Select::make('source_id')->label('منبع')->relationship('source', 'name')->searchable()->preload(),
                    Textarea::make('description_fa')->label('توضیحات فارسی')->required()->rows(7)->columnSpanFull(),
                    Textarea::make('description_de')->label('توضیحات آلمانی')->rows(7)->columnSpanFull(),
                ]),
            Section::make('موقعیت و شرایط')
                ->columns(3)
                ->schema([
                    TextInput::make('city')->label('شهر')->required(),
                    TextInput::make('state')->label('ایالت'),
                    Select::make('training_type')->label('نوع دوره')->options([
                        'dual' => 'دوگانه (Dual)',
                        'school' => 'مدرسه‌ای (Schulisch)',
                    ])->required(),
                    DatePicker::make('start_date')->label('تاریخ شروع'),
                    DatePicker::make('application_deadline')->label('مهلت درخواست'),
                    Select::make('required_german_level')->label('حداقل زبان آلمانی')->options([
                        'a2' => 'A2',
                        'b1' => 'B1',
                        'b2' => 'B2',
                        'c1' => 'C1',
                    ])->required(),
                    TextInput::make('monthly_salary_from')->label('حقوق از (یورو)')->numeric()->minValue(0),
                    TextInput::make('monthly_salary_to')->label('حقوق تا (یورو)')->numeric()->minValue(0),
                    TextInput::make('education_requirement')->label('مدرک موردنیاز'),
                    TagsInput::make('skills')->label('مهارت‌ها')->columnSpanFull(),
                    Toggle::make('accepts_international')->label('پذیرش متقاضی بین‌المللی'),
                    Select::make('visa_support')->label('حمایت ویزا')->options([
                        'unknown' => 'نامشخص',
                        'no' => 'خیر',
                        'possible' => 'ممکن',
                        'yes' => 'بله',
                    ])->required(),
                ]),
            Section::make('انتشار و درخواست')
                ->columns(2)
                ->schema([
                    TextInput::make('application_url')->label('لینک درخواست')->url()->required()->columnSpanFull(),
                    TextInput::make('contact_email')->label('ایمیل تماس')->email(),
                    TextInput::make('external_id')->label('شناسه در منبع'),
                    Select::make('status')->label('وضعیت')->options([
                        'draft' => 'پیش‌نویس',
                        'published' => 'منتشرشده',
                        'expired' => 'منقضی',
                    ])->required(),
                    DateTimePicker::make('published_at')->label('زمان انتشار'),
                ]),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('title_de')->label('عنوان')->searchable()->sortable()->description(fn (Opportunity $record): string => $record->title_fa),
                TextColumn::make('employer_name')->label('کارفرما')->searchable()->sortable(),
                TextColumn::make('category.name_fa')->label('دسته')->badge()->sortable(),
                TextColumn::make('city')->label('شهر')->searchable()->sortable(),
                TextColumn::make('required_german_level')->label('زبان')->badge()->formatStateUsing(fn (string $state): string => strtoupper($state)),
                IconColumn::make('accepts_international')->label('بین‌المللی')->boolean(),
                TextColumn::make('application_deadline')->label('مهلت')->date()->sortable(),
                TextColumn::make('status')->label('وضعیت')->badge()->color(fn (string $state): string => match ($state) {
                    'published' => 'success',
                    'expired' => 'danger',
                    default => 'gray',
                }),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                SelectFilter::make('status')->label('وضعیت')->options([
                    'draft' => 'پیش‌نویس',
                    'published' => 'منتشرشده',
                    'expired' => 'منقضی',
                ]),
                SelectFilter::make('category')->label('دسته‌بندی')->relationship('category', 'name_fa'),
                SelectFilter::make('accepts_international')->label('متقاضی بین‌المللی')->options([
                    1 => 'بله',
                    0 => 'خیر',
                ]),
            ])
            ->recordActions([
                EditAction::make(),
            ])
            ->toolbarActions([
                BulkActionGroup::make([
                    DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => ListOpportunities::route('/'),
            'create' => CreateOpportunity::route('/create'),
            'edit' => EditOpportunity::route('/{record}/edit'),
        ];
    }
}
