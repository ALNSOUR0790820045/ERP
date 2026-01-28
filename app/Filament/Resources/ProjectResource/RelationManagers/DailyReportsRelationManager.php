<?php

namespace App\Filament\Resources\ProjectResource\RelationManagers;

use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class DailyReportsRelationManager extends RelationManager
{
    protected static string $relationship = 'dailyReports';
    
    protected static ?string $title = 'التقارير اليومية';

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('report_number')->label('رقم التقرير'),
                Tables\Columns\TextColumn::make('report_date')->label('التاريخ')->date(),
                Tables\Columns\TextColumn::make('weather')->label('الطقس')->badge(),
                Tables\Columns\TextColumn::make('total_labor_count')->label('عدد العمال'),
                Tables\Columns\TextColumn::make('status')->label('الحالة')->badge(),
            ])
            ->defaultSort('report_date', 'desc');
    }
}
