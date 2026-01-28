<?php

namespace App\Filament\Widgets;

use Filament\Widgets\TableWidget;
use Filament\Tables;
use Filament\Tables\Table;
use App\Models\WorkflowApproval;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\Auth;

class PendingApprovalsWidget extends TableWidget
{
    protected static ?int $sort = 8;
    
    protected int|string|array $columnSpan = 'full';
    
    public function getHeading(): ?string
    {
        return 'الموافقات المعلقة';
    }

    public function table(Table $table): Table
    {
        return $table
            ->query(
                WorkflowApproval::query()
                    ->where('status', 'pending')
                    ->where('approver_id', Auth::id())
                    ->with(['approvable', 'workflow'])
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('workflow.name')
                    ->label('سير العمل')
                    ->sortable(),
                
                Tables\Columns\TextColumn::make('approvable_type')
                    ->label('نوع المستند')
                    ->formatStateUsing(function ($state) {
                        $types = [
                            'App\\Models\\PurchaseRequest' => 'طلب شراء',
                            'App\\Models\\PurchaseOrder' => 'أمر شراء',
                            'App\\Models\\ProgressCertificate' => 'مستخلص',
                            'App\\Models\\JournalEntry' => 'قيد يومية',
                            'App\\Models\\PaymentVoucher' => 'سند صرف',
                            'App\\Models\\ReceiptVoucher' => 'سند قبض',
                        ];
                        return $types[$state] ?? class_basename($state);
                    }),
                
                Tables\Columns\TextColumn::make('approvable.document_number')
                    ->label('رقم المستند')
                    ->getStateUsing(function ($record) {
                        return $record->approvable?->document_number 
                            ?? $record->approvable?->number 
                            ?? $record->approvable?->id;
                    }),
                
                Tables\Columns\TextColumn::make('step_name')
                    ->label('المرحلة'),
                
                Tables\Columns\TextColumn::make('created_at')
                    ->label('تاريخ الإنشاء')
                    ->dateTime('Y-m-d H:i')
                    ->sortable(),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->label('عرض')
                    ->icon('heroicon-o-eye')
                    ->color('info')
                    ->url(fn ($record) => $this->getApprovableUrl($record)),
                
                Tables\Actions\Action::make('approve')
                    ->label('موافقة')
                    ->icon('heroicon-o-check')
                    ->color('success')
                    ->requiresConfirmation()
                    ->action(function ($record) {
                        $record->update([
                            'status' => 'approved',
                            'approved_at' => now(),
                        ]);
                    }),
                
                Tables\Actions\Action::make('reject')
                    ->label('رفض')
                    ->icon('heroicon-o-x-mark')
                    ->color('danger')
                    ->requiresConfirmation(),
            ])
            ->emptyStateHeading('لا توجد موافقات معلقة')
            ->emptyStateDescription('جميع الطلبات تمت معالجتها')
            ->emptyStateIcon('heroicon-o-check-circle');
    }
    
    protected function getApprovableUrl($record): ?string
    {
        $routeMap = [
            'App\\Models\\PurchaseRequest' => 'filament.admin.resources.purchase-requests.view',
            'App\\Models\\PurchaseOrder' => 'filament.admin.resources.purchase-orders.view',
            'App\\Models\\ProgressCertificate' => 'filament.admin.resources.progress-certificates.view',
        ];
        
        $route = $routeMap[$record->approvable_type] ?? null;
        
        if ($route && $record->approvable_id) {
            try {
                return route($route, $record->approvable_id);
            } catch (\Exception $e) {
                return null;
            }
        }
        
        return null;
    }
}
