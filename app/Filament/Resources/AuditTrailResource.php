<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditTrailResource\Pages;
use App\Models\AuditTrail;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class AuditTrailResource extends Resource
{
    protected static ?string $model = AuditTrail::class;

    protected static ?string $navigationIcon = 'heroicon-o-eye';
    protected static ?string $navigationGroup = 'إدارة المستندات';
    protected static ?string $modelLabel = 'سجل تدقيق';
    protected static ?string $pluralModelLabel = 'سجل التدقيق';
    protected static ?int $navigationSort = 35;

    public static function canCreate(): bool
    {
        return false;
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الحدث')
                    ->schema([
                        Forms\Components\TextInput::make('auditable_type')
                            ->label('نوع الكيان')
                            ->disabled(),
                        Forms\Components\TextInput::make('auditable_id')
                            ->label('معرف الكيان')
                            ->disabled(),
                        Forms\Components\TextInput::make('action')
                            ->label('الإجراء')
                            ->disabled(),
                        Forms\Components\TextInput::make('module')
                            ->label('الوحدة')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('معلومات المستخدم')
                    ->schema([
                        Forms\Components\TextInput::make('user.name')
                            ->label('المستخدم')
                            ->disabled(),
                        Forms\Components\TextInput::make('ip_address')
                            ->label('عنوان IP')
                            ->disabled(),
                        Forms\Components\TextInput::make('url')
                            ->label('الرابط')
                            ->disabled(),
                    ])->columns(2),

                Forms\Components\Section::make('البيانات')
                    ->schema([
                        Forms\Components\KeyValue::make('old_values')
                            ->label('القيم القديمة')
                            ->disabled(),
                        Forms\Components\KeyValue::make('new_values')
                            ->label('القيم الجديدة')
                            ->disabled(),
                    ]),

                Forms\Components\Textarea::make('notes')
                    ->label('ملاحظات')
                    ->disabled(),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('التاريخ')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('المستخدم')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\BadgeColumn::make('action')
                    ->label('الإجراء')
                    ->colors([
                        'success' => 'create',
                        'info' => 'update',
                        'danger' => 'delete',
                        'warning' => 'approve',
                        'gray' => 'view',
                    ])
                    ->formatStateUsing(fn ($state) => AuditTrail::ACTIONS[$state] ?? $state),
                Tables\Columns\TextColumn::make('auditable_type')
                    ->label('الكيان')
                    ->formatStateUsing(fn ($state) => class_basename($state)),
                Tables\Columns\TextColumn::make('auditable_id')
                    ->label('المعرف'),
                Tables\Columns\TextColumn::make('module')
                    ->label('الوحدة'),
                Tables\Columns\TextColumn::make('ip_address')
                    ->label('IP')
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->defaultSort('created_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('action')
                    ->label('الإجراء')
                    ->options(AuditTrail::ACTIONS),
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('المستخدم')
                    ->relationship('user', 'name'),
                Tables\Filters\Filter::make('created_at')
                    ->form([
                        Forms\Components\DatePicker::make('from')
                            ->label('من تاريخ'),
                        Forms\Components\DatePicker::make('until')
                            ->label('إلى تاريخ'),
                    ])
                    ->query(function ($query, array $data) {
                        return $query
                            ->when($data['from'], fn ($q) => $q->whereDate('created_at', '>=', $data['from']))
                            ->when($data['until'], fn ($q) => $q->whereDate('created_at', '<=', $data['until']));
                    }),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
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
            'index' => Pages\ListAuditTrails::route('/'),
        ];
    }
}
