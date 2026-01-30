<div class="space-y-4 text-sm" dir="rtl">
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">رقم المستند</span>
            <span class="font-semibold">{{ $document->document_number }}</span>
        </div>
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">الحالة</span>
            <span class="font-semibold">{{ $statusLabel }}</span>
        </div>
    </div>
    
    <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
        <span class="text-gray-500 dark:text-gray-400 block mb-1">عنوان المستند</span>
        <span class="font-semibold text-lg">{{ $document->title }}</span>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">نوع المستند</span>
            <span class="font-semibold">{{ $document->document_type ?? '-' }}</span>
        </div>
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">تاريخ الإصدار</span>
            <span class="font-semibold">{{ $document->issue_date?->format('Y-m-d') ?? '-' }}</span>
        </div>
    </div>
    
    <div class="grid grid-cols-2 gap-4">
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">تاريخ الانتهاء</span>
            <span class="font-semibold {{ $document->expiry_date?->isPast() ? 'text-red-600' : 'text-green-600' }}">
                {{ $document->expiry_date?->format('Y-m-d') ?? '-' }}
                @if($document->expiry_date?->isPast())
                    <span class="text-red-600">⚠️ منتهي</span>
                @endif
            </span>
        </div>
        <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
            <span class="text-gray-500 dark:text-gray-400 block mb-1">الإصدار</span>
            <span class="font-semibold">{{ $document->revision ?? '-' }}</span>
        </div>
    </div>
    
    @if($document->description)
    <div class="bg-gray-100 dark:bg-gray-800 p-3 rounded-lg">
        <span class="text-gray-500 dark:text-gray-400 block mb-1">الوصف</span>
        <span>{{ $document->description }}</span>
    </div>
    @endif
    
    @if($document->file_path)
    <div class="bg-blue-50 dark:bg-blue-900/20 p-3 rounded-lg border border-blue-200 dark:border-blue-800">
        <span class="text-blue-600 dark:text-blue-400 block mb-2">📎 ملف مرفق</span>
        <a href="{{ asset('storage/' . $document->file_path) }}" 
           target="_blank" 
           class="text-blue-600 hover:underline">
            فتح الملف
        </a>
    </div>
    @endif
    
    <div class="bg-green-50 dark:bg-green-900/20 p-4 rounded-lg border border-green-200 dark:border-green-800 text-center">
        <span class="text-green-600 dark:text-green-400 text-lg">✅ تم عرض المستند بنجاح</span>
        <p class="text-gray-500 dark:text-gray-400 text-xs mt-1">يمكنك الآن تفعيل خيار "تم التدقيق" بعد إغلاق هذه النافذة</p>
    </div>
</div>
