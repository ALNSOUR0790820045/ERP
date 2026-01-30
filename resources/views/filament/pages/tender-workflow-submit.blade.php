<div class="flex gap-3 items-center">
    <x-filament::button
        type="submit"
        size="lg"
        wire:click="save"
    >
        حفظ العطاء
    </x-filament::button>
    
    @if($this->record?->exists && $this->record?->status === \App\Enums\TenderStatus::NEW)
        <x-filament::button
            size="lg"
            color="success"
            wire:click="sendForStudy"
            wire:confirm="هل أنت متأكد من إرسال هذا العطاء للمرحلة التالية (الدراسة والقرار)؟"
        >
            <x-heroicon-o-paper-airplane class="w-5 h-5 ml-2" />
            إرسال للدراسة
        </x-filament::button>
    @endif
</div>
