<x-dynamic-component :component="$getFieldWrapperView()" :field="$field">
    @php
        $googleMapsLink = $getState();
        $embedUrl = '';
        
        if ($googleMapsLink) {
            // تحويل رابط خرائط جوجل العادي إلى رابط التضمين
            if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $googleMapsLink, $matches)) {
                $lat = $matches[1];
                $lng = $matches[2];
                $embedUrl = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d{$lng}!3d{$lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1sar!2sjo!4v1";
            } elseif (str_contains($googleMapsLink, '/place/')) {
                // استخراج الإحداثيات من رابط المكان
                if (preg_match('/@(-?\d+\.\d+),(-?\d+\.\d+)/', $googleMapsLink, $matches)) {
                    $lat = $matches[1];
                    $lng = $matches[2];
                    $embedUrl = "https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3000!2d{$lng}!3d{$lat}!2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x0%3A0x0!2zM!5e0!3m2!1sar!2sjo!4v1";
                }
            }
        }
    @endphp

    @if($embedUrl)
        <div class="rounded-lg overflow-hidden border border-gray-300 dark:border-gray-600">
            <iframe 
                src="{{ $embedUrl }}"
                width="100%" 
                height="300" 
                style="border:0;" 
                allowfullscreen="" 
                loading="lazy" 
                referrerpolicy="no-referrer-when-downgrade">
            </iframe>
        </div>
    @elseif($googleMapsLink)
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-center">
            <a href="{{ $googleMapsLink }}" target="_blank" class="text-primary-600 hover:underline">
                🗺️ فتح الموقع في خرائط جوجل
            </a>
        </div>
    @else
        <div class="p-4 bg-gray-100 dark:bg-gray-800 rounded-lg text-center text-gray-500">
            أدخل رابط خرائط جوجل لعرض الموقع
        </div>
    @endif
</x-dynamic-component>
