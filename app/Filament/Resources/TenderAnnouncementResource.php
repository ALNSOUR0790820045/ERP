<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TenderAnnouncementResource\Pages;
use App\Models\TenderAnnouncement;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class TenderAnnouncementResource extends Resource
{
    protected static ?string $model = TenderAnnouncement::class;

    protected static ?string $navigationIcon = 'heroicon-o-megaphone';
    
    protected static ?string $navigationGroup = 'العطاءات والمناقصات';
    
    protected static ?string $modelLabel = 'إعلان عطاء';
    
    protected static ?string $pluralModelLabel = 'إعلانات العطاءات';

    protected static ?int $navigationSort = 2;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الإعلان')
                    ->schema([
                        Forms\Components\Select::make('tender_id')
                            ->label('العطاء')
                            ->relationship('tender', 'title')
                            ->required()
                            ->searchable()
                            ->preload(),
                        
                        Forms\Components\Select::make('announcement_type')
                            ->label('نوع الإعلان')
                            ->options([
                                'invitation' => 'دعوة للمناقصة',
                                'prequalification' => 'إعلان تأهيل مسبق',
                                'result' => 'إعلان نتيجة',
                                'cancellation' => 'إعلان إلغاء',
                            ])
                            ->required(),
                        
                        Forms\Components\DatePicker::make('publication_date')
                            ->label('تاريخ النشر')
                            ->required(),
                        
                        Forms\Components\TextInput::make('newspaper_name')
                            ->label('اسم الجريدة')
                            ->maxLength(255),
                        
                        Forms\Components\TextInput::make('official_gazette_number')
                            ->label('رقم الجريدة الرسمية')
                            ->maxLength(100),
                        
                        Forms\Components\TextInput::make('website_url')
                            ->label('رابط الموقع الإلكتروني')
                            ->url()
                            ->maxLength(500),
                    ])->columns(2),

                Forms\Components\Section::make('قنوات النشر')
                    ->schema([
                        Forms\Components\CheckboxList::make('publication_channels')
                            ->label('قنوات النشر')
                            ->options([
                                'official_gazette' => 'الجريدة الرسمية',
                                'local_newspaper' => 'صحيفة محلية',
                                'government_portal' => 'بوابة المشتريات الحكومية',
                                'ministry_website' => 'موقع الوزارة',
                                'social_media' => 'وسائل التواصل الاجتماعي',
                            ])
                            ->columns(3),
                        
                        Forms\Components\Textarea::make('announcement_text')
                            ->label('نص الإعلان')
                            ->rows(4)
                            ->columnSpanFull(),
                        
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات')
                            ->rows(2)
                            ->columnSpanFull(),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('tender.title')
                    ->label('العطاء')
                    ->searchable()
                    ->sortable()
                    ->limit(30),
                
                Tables\Columns\TextColumn::make('announcement_type')
                    ->label('نوع الإعلان')
                    ->badge()
                    ->formatStateUsing(fn (string $state): string => match ($state) {
                        'invitation' => 'دعوة للمناقصة',
                        'prequalification' => 'تأهيل مسبق',
                        'result' => 'إعلان نتيجة',
                        'cancellation' => 'إلغاء',
                        default => $state,
                    })
                    ->color(fn (string $state): string => match ($state) {
                        'invitation' => 'info',
                        'prequalification' => 'warning',
                        'result' => 'success',
                        'cancellation' => 'danger',
                        default => 'gray',
                    }),
                
                Tables\Columns\TextColumn::make('publication_date')
                    ->label('تاريخ النشر')
                    ->date()
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('newspaper_name')
                    ->label('الجريدة')
                    ->searchable(),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime()
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('announcement_type')
                    ->label('نوع الإعلان')
                    ->options([
                        'invitation' => 'دعوة للمناقصة',
                        'prequalification' => 'تأهيل مسبق',
                        'result' => 'إعلان نتيجة',
                        'cancellation' => 'إلغاء',
                    ]),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTenderAnnouncements::route('/'),
            'create' => Pages\CreateTenderAnnouncement::route('/create'),
            'edit' => Pages\EditTenderAnnouncement::route('/{record}/edit'),
        ];
    }
}
