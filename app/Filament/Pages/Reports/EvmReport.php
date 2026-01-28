<?php

namespace App\Filament\Pages\Reports;

use App\Models\EvmMeasurement;
use App\Models\Project;
use App\Models\ProjectBaseline;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Pages\Page;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Tables\Concerns\InteractsWithTable;
use Filament\Tables\Contracts\HasTable;
use Illuminate\Database\Eloquent\Builder;

class EvmReport extends Page implements HasTable
{
    use InteractsWithTable;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar-square';

    protected static string $view = 'filament.pages.reports.evm-report';

    protected static ?string $navigationGroup = 'التقارير';

    protected static ?string $navigationLabel = 'تقرير EVM';

    protected static ?string $title = 'تقرير القيمة المكتسبة (EVM)';

    protected static ?int $navigationSort = 35;

    public ?int $project_id = null;
    public ?string $date_from = null;
    public ?string $date_to = null;

    public function mount(): void
    {
        $this->date_from = now()->subMonths(6)->format('Y-m-d');
        $this->date_to = now()->format('Y-m-d');
    }

    protected function getFormSchema(): array
    {
        return [
            Forms\Components\Grid::make(4)
                ->schema([
                    Forms\Components\Select::make('project_id')
                        ->label('المشروع')
                        ->options(Project::pluck('name', 'id'))
                        ->searchable()
                        ->placeholder('جميع المشاريع')
                        ->live(),

                    Forms\Components\DatePicker::make('date_from')
                        ->label('من تاريخ')
                        ->live(),

                    Forms\Components\DatePicker::make('date_to')
                        ->label('إلى تاريخ')
                        ->live(),

                    Forms\Components\Actions::make([
                        Forms\Components\Actions\Action::make('filter')
                            ->label('تطبيق الفلتر')
                            ->action(fn () => $this->resetTable()),
                    ]),
                ]),
        ];
    }

    public function table(Table $table): Table
    {
        return $table
            ->query($this->getTableQuery())
            ->columns([
                Tables\Columns\TextColumn::make('measurement_number')
                    ->label('رقم القياس')
                    ->searchable(),

                Tables\Columns\TextColumn::make('project.name')
                    ->label('المشروع')
                    ->limit(25),

                Tables\Columns\TextColumn::make('measurement_date')
                    ->label('التاريخ')
                    ->date(),

                Tables\Columns\TextColumn::make('planned_value')
                    ->label('PV')
                    ->numeric(0)
                    ->suffix(' JOD'),

                Tables\Columns\TextColumn::make('earned_value')
                    ->label('EV')
                    ->numeric(0)
                    ->suffix(' JOD'),

                Tables\Columns\TextColumn::make('actual_cost')
                    ->label('AC')
                    ->numeric(0)
                    ->suffix(' JOD'),

                Tables\Columns\TextColumn::make('schedule_variance')
                    ->label('SV')
                    ->numeric(0)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('cost_variance')
                    ->label('CV')
                    ->numeric(0)
                    ->color(fn ($state) => $state >= 0 ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('schedule_performance_index')
                    ->label('SPI')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($state) => $state >= 0.95 ? 'success' : ($state >= 0.80 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('cost_performance_index')
                    ->label('CPI')
                    ->numeric(2)
                    ->badge()
                    ->color(fn ($state) => $state >= 0.95 ? 'success' : ($state >= 0.80 ? 'warning' : 'danger')),

                Tables\Columns\TextColumn::make('physical_progress')
                    ->label('الإنجاز %')
                    ->numeric(1)
                    ->suffix('%'),

                Tables\Columns\TextColumn::make('overall_status')
                    ->label('الحالة')
                    ->badge()
                    ->formatStateUsing(fn ($state) => match($state) {
                        'green' => '✓ جيد',
                        'yellow' => '⚠ تحذير',
                        'red' => '✗ حرج',
                        default => $state,
                    })
                    ->color(fn ($state) => match($state) {
                        'green' => 'success',
                        'yellow' => 'warning',
                        'red' => 'danger',
                        default => 'gray',
                    }),
            ])
            ->defaultSort('measurement_date', 'desc')
            ->striped();
    }

    protected function getTableQuery(): Builder
    {
        $query = EvmMeasurement::query()
            ->with('project')
            ->where('status', 'approved');

        if ($this->project_id) {
            $query->where('project_id', $this->project_id);
        }

        if ($this->date_from) {
            $query->where('measurement_date', '>=', $this->date_from);
        }

        if ($this->date_to) {
            $query->where('measurement_date', '<=', $this->date_to);
        }

        return $query;
    }

    public function getSummary(): array
    {
        $query = $this->getTableQuery();
        
        return [
            'total_measurements' => $query->count(),
            'avg_spi' => $query->avg('schedule_performance_index') ?? 0,
            'avg_cpi' => $query->avg('cost_performance_index') ?? 0,
            'total_pv' => $query->sum('planned_value'),
            'total_ev' => $query->sum('earned_value'),
            'total_ac' => $query->sum('actual_cost'),
            'behind_schedule' => $query->where('schedule_performance_index', '<', 0.90)->count(),
            'over_budget' => $query->where('cost_performance_index', '<', 0.90)->count(),
            'critical' => $query->where('overall_status', 'red')->count(),
        ];
    }
}
