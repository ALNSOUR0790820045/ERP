<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PurchaseRequestResource\Pages;
use App\Models\PurchaseRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PurchaseRequestResource extends Resource
{
    protected static ?string $model = PurchaseRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'المشتريات';

    protected static ?string $modelLabel = 'طلب شراء';

    protected static ?string $pluralModelLabel = 'طلبات الشراء';

    protected static ?int $navigationSort = 1;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\TextInput::make('request_number')
                            ->label('رقم الطلب')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->default(fn () => 'PR-' . date('Y') . '-' . str_pad(PurchaseRequest::count() + 1, 5, '0', STR_PAD_LEFT)),
                        Forms\Components\DatePicker::make('request_date')
                            ->label('تاريخ الطلب')
                            ->required()
                            ->default(now()),
                        Forms\Components\Select::make('project_id')
                            ->label('المشروع')
                            ->relationship('project', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('required_date')
                            ->label('التاريخ المطلوب'),
                        Forms\Components\Select::make('priority')
                            ->label('الأولوية')
                            ->options([
                                'low' => 'منخفضة',
                                'normal' => 'عادية',
                                'high' => 'عالية',
                                'urgent' => 'عاجلة',
                            ])
                            ->default('normal'),
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'draft' => 'مسودة',
                                'pending' => 'قيد الاعتماد',
                                'approved' => 'معتمد',
                                'rejected' => 'مرفوض',
                                'ordered' => 'تم الطلب',
                                'completed' => 'مكتمل',
                            ])
                            ->default('draft'),
                    ])->columns(3),

                Forms\Components\Section::make('التفاصيل')
                    ->schema([
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف'),
                        Forms\Components\Textarea::make('notes')
                            ->label('ملاحظات'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('request_number')
                    ->label('رقم الطلب')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('request_date')
                    ->label('التاريخ')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->searchable(),
                Tables\Columns\TextColumn::make('required_date')
                    ->label('التاريخ المطلوب')
                    ->date(),
                Tables\Columns\BadgeColumn::make('priority')
                    ->label('الأولوية')
                    ->colors([
                        'gray' => 'low',
                        'info' => 'normal',
                        'warning' => 'high',
                        'danger' => 'urgent',
                    ]),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'gray' => 'draft',
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'info' => 'ordered',
                        'primary' => 'completed',
                    ]),
                Tables\Columns\TextColumn::make('createdBy.name')
                    ->label('المنشئ'),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'draft' => 'مسودة',
                        'pending' => 'قيد الاعتماد',
                        'approved' => 'معتمد',
                        'rejected' => 'مرفوض',
                        'ordered' => 'تم الطلب',
                        'completed' => 'مكتمل',
                    ]),
                Tables\Filters\SelectFilter::make('priority')
                    ->label('الأولوية')
                    ->options([
                        'low' => 'منخفضة',
                        'normal' => 'عادية',
                        'high' => 'عالية',
                        'urgent' => 'عاجلة',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
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
            'index' => Pages\ListPurchaseRequests::route('/'),
            'create' => Pages\CreatePurchaseRequest::route('/create'),
            'edit' => Pages\EditPurchaseRequest::route('/{record}/edit'),
        ];
    }
}
