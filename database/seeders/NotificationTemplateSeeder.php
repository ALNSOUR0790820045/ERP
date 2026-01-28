<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\NotificationTemplate;

class NotificationTemplateSeeder extends Seeder
{
    /**
     * قوالب الإشعارات
     */
    public function run(): void
    {
        $templates = [
            // إشعارات سير العمل
            [
                'code' => 'WORKFLOW_PENDING',
                'name' => 'طلب موافقة جديد',
                'event_type' => 'workflow.created',
                'subject_ar' => 'طلب موافقة جديد - {document_type}',
                'subject_en' => 'New Approval Request - {document_type}',
                'body_ar' => 'مرحباً {user_name}،\n\nيوجد طلب موافقة جديد بانتظارك:\n\nنوع المستند: {document_type}\nالرقم: {document_number}\nالمبلغ: {amount}\n\nيرجى مراجعة الطلب واتخاذ الإجراء المناسب.',
                'body_en' => 'Hello {user_name},\n\nThere is a new approval request waiting for you:\n\nDocument Type: {document_type}\nNumber: {document_number}\nAmount: {amount}\n\nPlease review and take appropriate action.',
                'channels' => json_encode(['database', 'email']),
                'variables' => json_encode(['user_name', 'document_type', 'document_number', 'amount']),
            ],
            [
                'code' => 'WORKFLOW_APPROVED',
                'name' => 'تم اعتماد الطلب',
                'event_type' => 'workflow.approved',
                'subject_ar' => 'تم اعتماد طلبك - {document_type}',
                'subject_en' => 'Your Request Approved - {document_type}',
                'body_ar' => 'مرحباً {user_name}،\n\nتم اعتماد طلبك:\n\nنوع المستند: {document_type}\nالرقم: {document_number}\n\nتم الاعتماد بواسطة: {approver_name}',
                'body_en' => 'Hello {user_name},\n\nYour request has been approved:\n\nDocument Type: {document_type}\nNumber: {document_number}\n\nApproved by: {approver_name}',
                'channels' => json_encode(['database', 'email']),
                'variables' => json_encode(['user_name', 'document_type', 'document_number', 'approver_name']),
            ],
            [
                'code' => 'WORKFLOW_REJECTED',
                'name' => 'تم رفض الطلب',
                'event_type' => 'workflow.rejected',
                'subject_ar' => 'تم رفض طلبك - {document_type}',
                'subject_en' => 'Your Request Rejected - {document_type}',
                'body_ar' => 'مرحباً {user_name}،\n\nتم رفض طلبك:\n\nنوع المستند: {document_type}\nالرقم: {document_number}\n\nالسبب: {rejection_reason}',
                'body_en' => 'Hello {user_name},\n\nYour request has been rejected:\n\nDocument Type: {document_type}\nNumber: {document_number}\n\nReason: {rejection_reason}',
                'channels' => json_encode(['database', 'email']),
                'variables' => json_encode(['user_name', 'document_type', 'document_number', 'rejection_reason']),
            ],
            // إشعارات المخزون
            [
                'code' => 'STOCK_REORDER',
                'name' => 'تنبيه إعادة الطلب',
                'event_type' => 'stock.alert',
                'subject_ar' => 'تنبيه: المخزون أقل من الحد الأدنى',
                'subject_en' => 'Alert: Stock Below Minimum Level',
                'body_ar' => 'تنبيه!\n\nالمادة: {material_name}\nالمستودع: {warehouse_name}\nالكمية الحالية: {current_quantity}\nالحد الأدنى: {minimum_quantity}\n\nيرجى إصدار طلب شراء.',
                'body_en' => 'Alert!\n\nMaterial: {material_name}\nWarehouse: {warehouse_name}\nCurrent Quantity: {current_quantity}\nMinimum Level: {minimum_quantity}\n\nPlease create a purchase request.',
                'channels' => json_encode(['database']),
                'variables' => json_encode(['material_name', 'warehouse_name', 'current_quantity', 'minimum_quantity']),
            ],
            // إشعارات العقود
            [
                'code' => 'CONTRACT_EXPIRY',
                'name' => 'تنبيه انتهاء العقد',
                'event_type' => 'contract.reminder',
                'subject_ar' => 'تذكير: العقد يقترب من تاريخ الانتهاء',
                'subject_en' => 'Reminder: Contract Approaching Expiry Date',
                'body_ar' => 'تذكير!\n\nالعقد: {contract_number}\nالعميل: {customer_name}\nتاريخ الانتهاء: {expiry_date}\nالأيام المتبقية: {days_remaining}',
                'body_en' => 'Reminder!\n\nContract: {contract_number}\nCustomer: {customer_name}\nExpiry Date: {expiry_date}\nDays Remaining: {days_remaining}',
                'channels' => json_encode(['database', 'email']),
                'variables' => json_encode(['contract_number', 'customer_name', 'expiry_date', 'days_remaining']),
            ],
            // إشعارات المستخلصات
            [
                'code' => 'IPC_SUBMITTED',
                'name' => 'تقديم مستخلص جديد',
                'event_type' => 'ipc.created',
                'subject_ar' => 'مستخلص جديد - {certificate_number}',
                'subject_en' => 'New Progress Certificate - {certificate_number}',
                'body_ar' => 'تم تقديم مستخلص جديد:\n\nرقم المستخلص: {certificate_number}\nالعقد: {contract_number}\nالمبلغ: {amount} دينار',
                'body_en' => 'New progress certificate submitted:\n\nCertificate: {certificate_number}\nContract: {contract_number}\nAmount: {amount} JOD',
                'channels' => json_encode(['database']),
                'variables' => json_encode(['certificate_number', 'contract_number', 'amount']),
            ],
        ];

        foreach ($templates as $template) {
            NotificationTemplate::updateOrCreate(
                ['code' => $template['code']],
                array_merge($template, ['is_active' => true])
            );
        }
    }
}
