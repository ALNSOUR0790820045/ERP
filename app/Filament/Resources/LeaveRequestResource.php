<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Models\LeaveRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class LeaveRequestResource extends Resource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'الموارد البشرية';

    protected static ?string $modelLabel = 'طلب إجازة';

    protected static ?string $pluralModelLabel = 'طلبات الإجازات';

    protected static ?int $navigationSort = 3;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الطلب')
                    ->schema([
                        Forms\Components\Select::make('employee_id')
                            ->label('الموظف')
                            ->relationship('employee', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('leave_type_id')
                            ->label('نوع الإجازة')
                            ->relationship('leaveType', 'name')
                            ->required()
                            ->searchable()
                            ->preload(),
                        Forms\Components\DatePicker::make('start_date')
                            ->label('تاريخ البداية')
                            ->required(),
                        Forms\Components\DatePicker::make('end_date')
                            ->label('تاريخ النهاية')
                            ->required(),
                        Forms\Components\TextInput::make('days_count')
                            ->label('عدد الأيام')
                            ->numeric()
                            ->required(),
                        Forms\Components\Textarea::make('reason')
                            ->label('السبب')
                            ->columnSpanFull(),
                    ])->columns(2),

                Forms\Components\Section::make('الموافقة')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('الحالة')
                            ->options([
                                'pending' => 'معلق',
                                'approved' => 'موافق عليه',
                                'rejected' => 'مرفوض',
                                'cancelled' => 'ملغي',
                            ])
                            ->default('pending'),
                        Forms\Components\Textarea::make('rejection_reason')
                            ->label('سبب الرفض')
                            ->visible(fn ($get) => $get('status') === 'rejected'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('employee.name')
                    ->label('الموظف')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('leaveType.name')
                    ->label('نوع الإجازة'),
                Tables\Columns\TextColumn::make('start_date')
                    ->label('من')
                    ->date()
                    ->sortable(),
                Tables\Columns\TextColumn::make('end_date')
                    ->label('إلى')
                    ->date(),
                Tables\Columns\TextColumn::make('days_count')
                    ->label('الأيام'),
                Tables\Columns\BadgeColumn::make('status')
                    ->label('الحالة')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'approved',
                        'danger' => 'rejected',
                        'gray' => 'cancelled',
                    ])
                    ->formatStateUsing(fn ($state) => match($state) {
                        'pending' => 'معلق',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        'cancelled' => 'ملغي',
                        default => $state,
                    }),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الطلب')
                    ->dateTime()
                    ->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('الحالة')
                    ->options([
                        'pending' => 'معلق',
                        'approved' => 'موافق عليه',
                        'rejected' => 'مرفوض',
                        'cancelled' => 'ملغي',
                    ]),
                Tables\Filters\SelectFilter::make('leave_type_id')
                    ->label('نوع الإجازة')
                    ->relationship('leaveType', 'name'),
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
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
