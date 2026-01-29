<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderSiteVisitResource\Pages;
use App\Models\Tenders\TenderSiteVisit;
use App\Models\Tender;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderSiteVisitResource extends Resource
{
    protected static ?string $model = TenderSiteVisit::class;

    protected static ?string $navigationIcon = 'heroicon-o-map-pin';
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    protected static ?string $navigationLabel = 'زيارات الموقع';
    protected static ?string $modelLabel = 'زيارة موقع';
    protected static ?string $pluralModelLabel = 'زيارات الموقع';
    protected static ?int $navigationSort = 5;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make('بيانات الزيارة')->schema([
                Forms\Components\Select::make('tender_id')
                    ->label('العطاء')
                    ->relationship('tender', 'name_ar')
                    ->searchable()
                    ->preload()
                    ->required(),
                Forms\Components\DateTimePicker::make('visit_date')
                    ->label('تاريخ ووقت الزيارة')
                    ->required(),
                Forms\Components\DateTimePicker::make('visit_end_time')
                    ->label('وقت الانتهاء'),
                Forms\Components\Toggle::make('is_mandatory')
                    ->label('زيارة إلزامية'),
            ])->columns(2),

            Forms\Components\Section::make('فريق الزيارة')->schema([
                Forms\Components\TagsInput::make('visitors')
                    ->label('الزائرين')
                    ->placeholder('أضف اسم')
                    ->columnSpanFull(),
                Forms\Components\Toggle::make('owner_representative_present')
                    ->label('حضور ممثل المالك'),
                Forms\Components\TextInput::make('owner_representative_name')
                    ->label('اسم ممثل المالك')
                    ->maxLength(255),
            ])->columns(2),

            Forms\Components\Section::make('تفاصيل الموقع')->schema([
                Forms\Components\TextInput::make('site_location')
                    ->label('موقع المشروع')
                    ->maxLength(255),
                Forms\Components\TextInput::make('site_area')
                    ->label('مساحة الموقع (م²)')
                    ->numeric(),
                Forms\Components\Textarea::make('access_conditions')
                    ->label('ظروف الوصول')
                    ->rows(2),
                Forms\Components\Textarea::make('terrain_description')
                    ->label('وصف التضاريس')
                    ->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('الملاحظات الفنية')->schema([
                Forms\Components\Textarea::make('existing_structures')
                    ->label('المنشآت القائمة')
                    ->rows(2),
                Forms\Components\Textarea::make('utilities_available')
                    ->label('المرافق المتوفرة')
                    ->rows(2),
                Forms\Components\Textarea::make('nearby_facilities')
                    ->label('المرافق القريبة')
                    ->rows(2),
                Forms\Components\Textarea::make('potential_issues')
                    ->label('المشاكل المحتملة')
                    ->rows(2),
            ])->columns(2),

            Forms\Components\Section::make('التقييم')->schema([
                Forms\Components\Select::make('site_rating')
                    ->label('تقييم الموقع')
                    ->options(TenderSiteVisit::SITE_RATINGS),
                Forms\Components\Textarea::make('recommendations')
                    ->label('التوصيات')
                    ->rows(3)
                    ->columnSpan(2),
                Forms\Components\Textarea::make('weather_conditions')
                    ->label('أحوال الطقس')
                    ->rows(2),
            ])->columns(3),

            Forms\Components\Section::make('المرفقات')->schema([
                Forms\Components\FileUpload::make('photos')
                    ->label('صور الموقع')
                    ->multiple()
                    ->image()
                    ->directory('tender-site-visits'),
                Forms\Components\FileUpload::make('visit_report_path')
                    ->label('تقرير الزيارة')
                    ->directory('tender-site-visits/reports'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.name_ar')
                    ->label('العطاء')
                    ->searchable()
                    ->limit(30),
                Tables\Columns\TextColumn::make('visit_date')
                    ->label('تاريخ الزيارة')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('site_location')
                    ->label('الموقع')
                    ->limit(30),
                Tables\Columns\TextColumn::make('site_rating')
                    ->label('التقييم')
                    ->badge()
                    ->formatStateUsing(fn($state) => TenderSiteVisit::SITE_RATINGS[$state] ?? $state)
                    ->color(fn($state) => match($state) {
                        'excellent' => 'success',
                        'good' => 'info',
                        'fair' => 'warning',
                        'poor' => 'danger',
                        default => 'gray',
                    }),
                Tables\Columns\IconColumn::make('is_mandatory')
                    ->label('إلزامية')
                    ->boolean(),
                Tables\Columns\IconColumn::make('owner_representative_present')
                    ->label('حضور المالك')
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('site_rating')
                    ->label('التقييم')
                    ->options(TenderSiteVisit::SITE_RATINGS),
                Tables\Filters\TernaryFilter::make('is_mandatory')
                    ->label('إلزامية'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
            'index' => Pages\ListTenderSiteVisits::route('/'),
            'create' => Pages\CreateTenderSiteVisit::route('/create'),
            'edit' => Pages\EditTenderSiteVisit::route('/{record}/edit'),
        ];
    }
}
