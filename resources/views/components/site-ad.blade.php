@props(['ad'])
@php
    $side = in_array($ad->location, ['LeftBanner', 'RightBanner']);
    $height = $side ? 600 : ($ad->device === 'mobile' ? 100 : 90);
    $query = $ad->device === 'mobile' ? '(max-width: 1023px)' : '(min-width: 1024px)';
    $document = '<!doctype html><html><head><meta name="viewport" content="width=device-width, initial-scale=1"><style>html,body{margin:0;padding:0}img,iframe{max-width:100%}body{overflow:hidden}</style></head><body>'.$ad->code.'</body></html>';
@endphp
<div class="site-ad site-ad-{{ $ad->device }} {{ $side ? 'site-ad-side' : 'site-ad-header' }}" data-ad-slot="{{ $ad->device }}.{{ $ad->location }}"
     x-data="{ visible: window.matchMedia(@js($query)).matches }" @resize.window.debounce.100ms="visible = window.matchMedia(@js($query)).matches">
    <template x-if="visible">
        <div>
            @if(trim($ad->code ?? '') !== '')
                <iframe title="{{ $ad->title }}" srcdoc="{{ $document }}" sandbox="allow-scripts allow-popups allow-popups-to-escape-sandbox" referrerpolicy="no-referrer" loading="lazy" style="width:100%;height:{{ $height }}px;border:0;display:block"></iframe>
            @endif
        </div>
    </template>
</div>
