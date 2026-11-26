@props(['item'])
@php
    $store = strtolower(trim($item->name));
    $apple = $store === 'app store';
    $google = in_array($store, ['play store', 'google play'], true);
@endphp
@if($item->url !== '')
    <a href="{{ $item->url }}" class="store-badge">
@else
    <span class="store-badge">
@endif
    @if($apple || $google)
        <span class="sr-only">{{ $item->name }}</span>
        @if($apple)
            <svg aria-hidden="true" class="store-badge-icon" viewBox="0 0 24 24" fill="white">
                <path d="M18.71 19.5c-.83 1.24-1.71 2.45-3.05 2.47-1.34.03-1.77-.79-3.29-.79-1.53 0-2 .77-3.27.82-1.31.05-2.3-1.32-3.14-2.53C4.25 17 2.94 12.45 4.7 9.39c.87-1.52 2.43-2.48 4.12-2.51 1.28-.02 2.5.87 3.29.87.78 0 2.26-1.07 3.81-.91.65.03 2.47.26 3.64 1.98-.09.06-2.17 1.28-2.15 3.81.03 3.02 2.65 4.03 2.68 4.04-.03.07-.42 1.44-1.38 2.83M15.97 6.37c.62-.75 1.04-1.8 1.01-2.87-1 .04-2.16.67-2.75 1.37-.54.63-.97 1.66-.91 2.69 1.12.09 2.11-.53 2.65-1.19z"/>
            </svg>
            <span aria-hidden="true" class="store-badge-copy"><span class="store-badge-title">App Store’dan</span><span class="store-badge-caption">İndirin</span></span>
        @else
            <svg aria-hidden="true" class="store-badge-icon" viewBox="0 0 24 26" fill="none">
                <path d="M2 1.3 14 13 2 24.7Z" fill="#00D5EF"/>
                <path d="m2 1.3 15.2 8.8L14 13Z" fill="#00E676"/>
                <path d="m14 13 3.2 2.9L2 24.7Z" fill="#FF3D57"/>
                <path d="m17.2 10.1 4 2.3c.5.3.5.9 0 1.2l-4 2.3L14 13Z" fill="#FFD600"/>
            </svg>
            <span aria-hidden="true" class="store-badge-copy"><span class="store-badge-title">Google Play</span><span class="store-badge-caption store-badge-caption-google">’DEN ALIN</span></span>
        @endif
    @else
        {{ $item->name }}
    @endif
@if($item->url !== '')
    </a>
@else
    </span>
@endif
