<?php

namespace App\Filament\Resources;

use App\Filament\Resources\TeamResource\Pages;
use App\Filament\Resources\TeamResource\RelationManagers;
use App\Models\Team;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class TeamResource extends Resource
{
    protected static ?string $model = Team::class;

    protected static ?string $navigationIcon = 'heroicon-o-user-group';
    protected static ?string $navigationGroup = 'إدارة الصلاحيات والوصول';
    protected static ?string $navigationLabel = 'فرق العمل';
    protected static ?string $modelLabel = 'فريق';
    protected static ?string $pluralModelLabel = 'فرق العمل';
    protected static ?int $navigationSort = 3;
    
    // إخفاء من القائمة - الوصول عبر صفحة إدارة الوصول
    protected static bool $shouldRegisterNavigation = false;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('معلومات الفريق')
                    ->icon('heroicon-o-user-group')
                    ->columns(3)
                    ->schema([
                        Forms\Components\TextInput::make('name_ar')
                            ->label('اسم الفريق بالعربي')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('name_en')
                            ->label('اسم الفريق بالإنجليزي')
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->label('الرمز')
                            ->required()
                            ->unique(ignoreRecord: true)
                            ->maxLength(50)
                            ->helperText('رمز فريد للفريق'),
                        Forms\Components\Select::make('type')
                            ->label('نوع الفريق')
                            ->options([
                                'general' => 'عام',
                                'tender' => 'فريق عطاءات',
                                'project' => 'فريق مشروع',
                                'department' => 'قسم',
                                'pricing' => 'فريق تسعير',
                                'technical' => 'فريق فني',
                            ])
                            ->default('general')
                            ->required(),
                        Forms\Components\Select::make('leader_id')
                            ->label('قائد الفريق')
                            ->relationship('leader', 'name')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Select::make('branch_id')
                            ->label('الفرع')
                            ->relationship('branch', 'name_ar')
                            ->searchable()
                            ->preload(),
                        Forms\Components\Textarea::make('description')
                            ->label('الوصف')
                            ->columnSpanFull()
                            ->rows(2),
                        Forms\Components\Toggle::make('is_active')
                            ->label('نشط')
                            ->default(true),
                    ]),

                Forms\Components\Section::make('أعضاء الفريق')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Repeater::make('teamMembers')
                            ->label('')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('user_id')
                                    ->label('العضو')
                                    ->relationship('user', 'name')
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->distinct(),
                                Forms\Components\Select::make('role_in_team')
                                    ->label('الدور في الفريق')
                                    ->options([
                                        'leader' => 'قائد',
                                        'member' => 'عضو',
                                        'viewer' => 'مشاهد',
                                    ])
                                    ->default('member')
                                    ->required(),
                                Forms\Components\DatePicker::make('joined_at')
                                    ->label('تاريخ الانضمام')
                                    ->default(now()),
                                Forms\Components\Toggle::make('is_active')
                                    ->label('نشط')
                                    ->default(true),
                            ])
                            ->columns(4)
                            ->defaultItems(0)
                            ->addActionLabel('إضافة عضو')
                            ->reorderable(false)
                            ->collapsible()
                            ->itemLabel(fn (array $state): ?string => 
                                $state['user_id'] ? \App\Models\User::find($state['user_id'])?->name : null
                            ),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name_ar')
                    ->label('اسم الفريق')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('code')
                    ->label('الرمز')
                    ->badge()
                    ->color('gray'),
                Tables\Columns\TextColumn::make('type')
                    ->label('النوع')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'general' => 'عام',
                        'tender' => 'عطاءات',
                        'project' => 'مشروع',
                        'department' => 'قسم',
                        'pricing' => 'تسعير',
                        'technical' => 'فني',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'tender' => 'success',
                        'project' => 'info',
                        'pricing' => 'warning',
                        'technical' => 'primary',
                        default => 'gray',
                    }),
                Tables\Columns\TextColumn::make('leader.name')
                    ->label('القائد'),
                Tables\Columns\TextColumn::make('active_members_count')
                    ->label('الأعضاء')
                    ->counts([
                        'teamMembers as active_members_count' => fn ($query) => $query->where('is_active', true),
                    ])
                    ->badge()
                    ->color('info'),
                Tables\Columns\TextColumn::make('branch.name_ar')
                    ->label('الفرع')
                    ->toggleable(),
                Tables\Columns\IconColumn::make('is_active')
                    ->label('نشط')
                    ->boolean(),
            ])
            ->defaultSort('name_ar')
            ->filters([
                Tables\Filters\SelectFilter::make('type')
                    ->label('النوع')
                    ->options([
                        'general' => 'عام',
                        'tender' => 'عطاءات',
                        'project' => 'مشروع',
                        'department' => 'قسم',
                        'pricing' => 'تسعير',
                        'technical' => 'فني',
                    ]),
                Tables\Filters\TernaryFilter::make('is_active')
                    ->label('الحالة'),
            ])
            ->actions([
                Tables\Actions\ViewAction::make(),
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
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListTeams::route('/'),
            'create' => Pages\CreateTeam::route('/create'),
            'view' => Pages\ViewTeam::route('/{record}'),
            'edit' => Pages\EditTeam::route('/{record}/edit'),
        ];
    }
}
