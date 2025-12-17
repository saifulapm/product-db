@php
    $location = $location ?? null;
    $uniqueId = 'location-preview-' . md5($location ?? 'empty');
    $encodedLocation = $location ? urlencode($location) : '';
@endphp

<div class="w-full" style="width: 100%;">
    @if(!$location)
        <p class="text-gray-500 text-sm py-4">Enter a location to see map preview</p>
    @else
        <div class="w-full border border-gray-300 rounded-lg overflow-hidden bg-white mt-4" style="width: 100%; max-width: 100%; box-sizing: border-box;">
            <iframe
                id="{{ $uniqueId }}"
                width="100%"
                height="400"
                style="border:0; display: block;"
                loading="lazy"
                allowfullscreen
                referrerpolicy="no-referrer-when-downgrade"
                src="https://www.google.com/maps?q={{ $encodedLocation }}&output=embed">
            </iframe>
        </div>
    @endif
</div>

