<?php

namespace App\Filament\Widgets;

use Filament\Widgets\Widget;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsWidget extends Widget
{
    protected static bool $isLazy = true;
    
    protected static ?int $sort = 8;
    
    protected int|string|array $columnSpan = 1;
    
    protected static string $view = 'filament.widgets.pending-approvals-widget';
    
    public function getHeading(): ?string
    {
        return 'الموافقات المعلقة';
    }
    
    public function getApprovals(): array
    {
        // التحقق من وجود جدول سير العمل
        if (!Schema::hasTable('workflow_step_executions')) {
            return [];
        }
        
        return DB::table('workflow_step_executions')
            ->where('status', 'pending')
            ->where('assigned_to', Auth::id())
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get()
            ->toArray();
    }
}
