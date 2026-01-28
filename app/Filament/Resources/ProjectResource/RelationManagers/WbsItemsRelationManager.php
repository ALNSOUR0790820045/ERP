<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class WbsItemsRelationManager extends RelationManager
{
    protected static string $relationship = 'wbsItems';
    
    protected static ?string $title = 'هيكل تقسيم العمل (WBS)';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('wbs_code')->label('الكود'),
                Tables\Columns\TextColumn::make('name_ar')->label('الاسم'),
                Tables\Columns\TextColumn::make('type')->label('النوع')->badge(),
                Tables\Columns\TextColumn::make('planned_start')->label('البدء')->date(),
                Tables\Columns\TextColumn::make('planned_finish')->label('الانتهاء')->date(),
                Tables\Columns\TextColumn::make('actual_progress')->label('الإنجاز %')->suffix('%'),
            ]);
    }
}
