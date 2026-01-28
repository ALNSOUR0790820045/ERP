<?php

namespace App\Filament\Pages;

use App\Models\GanttTask;
use App\Models\GanttDependency;
use App\Models\Project;
use Filament\Pages\Page;
use Filament\Forms\Components\Select;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Livewire\Attributes\Url;

class GanttChartPage extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static string $view = 'filament.pages.gantt-chart-page';

    protected static ?string $navigationGroup = 'إدارة المشاريع';

    protected static ?string $navigationLabel = 'مخطط Gantt';

    protected static ?string $title = 'مخطط Gantt التفاعلي';

    protected static ?int $navigationSort = 26;

    #[Url]
    public ?int $projectId = null;

    public function mount(): void
    {
        $this->projectId = $this->projectId ?? Project::first()?->id;
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Select::make('projectId')
                    ->label('المشروع')
                    ->options(Project::pluck('name', 'id'))
                    ->searchable()
                    ->live()
                    ->afterStateUpdated(fn ($state) => $this->projectId = $state),
            ]);
    }

    public function getGanttData(): array
    {
        if (!$this->projectId) {
            return ['tasks' => [], 'links' => []];
        }

        $tasks = GanttTask::where('project_id', $this->projectId)
            ->orderBy('sort_order')
            ->get()
            ->map(fn ($task) => $task->toGanttData())
            ->toArray();

        $taskIds = GanttTask::where('project_id', $this->projectId)->pluck('id');
        
        $links = GanttDependency::whereIn('predecessor_id', $taskIds)
            ->whereIn('successor_id', $taskIds)
            ->get()
            ->map(fn ($dep) => $dep->toGanttData())
            ->toArray();

        return [
            'tasks' => $tasks,
            'links' => $links,
        ];
    }

    public function getProjectStats(): array
    {
        if (!$this->projectId) {
            return [
                'total_tasks' => 0,
                'completed' => 0,
                'in_progress' => 0,
                'not_started' => 0,
                'delayed' => 0,
                'critical' => 0,
                'overall_progress' => 0,
            ];
        }

        $tasks = GanttTask::where('project_id', $this->projectId)->get();

        $totalWeight = $tasks->sum('weight');
        $weightedProgress = $tasks->sum(fn ($t) => $t->progress * $t->weight);
        
        return [
            'total_tasks' => $tasks->count(),
            'completed' => $tasks->where('status', 'completed')->count(),
            'in_progress' => $tasks->where('status', 'in_progress')->count(),
            'not_started' => $tasks->where('status', 'not_started')->count(),
            'delayed' => $tasks->filter(fn ($t) => $t->is_delayed)->count(),
            'critical' => $tasks->where('is_critical', true)->count(),
            'overall_progress' => $totalWeight > 0 
                ? round($weightedProgress / $totalWeight, 2) 
                : round($tasks->avg('progress') ?? 0, 2),
        ];
    }
}
