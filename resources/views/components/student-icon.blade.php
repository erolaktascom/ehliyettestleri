@props(['name'])
@php
    $paths = [
        'arrow-left' => 'M19 12H5m6-6-6 6 6 6',
        'home' => 'm3 10 9-7 9 7M5 9v12h14V9M9 21v-8h6v8',
        'flag' => 'M5 21V3m0 1c5-4 9 4 14 0v10c-5 4-9-4-14 0',
        'clock' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 7v5l3 2',
        'lock' => 'M5 10h14v11H5V10Zm3 0V7a4 4 0 0 1 8 0v3',
        'login' => 'M14 3h6v18h-6M3 12h12m-5-5 5 5-5 5',
        'send' => 'm3 3 19 9-19 9 4-9-4-9Zm4 9h15',
        'save' => 'M4 3h13l4 4v14H3V3h1Zm3 0v6h10V3M7 21v-8h10v8',
        'arrow' => 'M5 12h14m-6-6 6 6-6 6',
        'first-aid' => 'M9 3h6v6h6v6h-6v6H9v-6H3V9h6V3Z',
        'gear' => 'm10 3-.5 2-2 1-2-.5-2 3L5 10v3l-1.5 1.5 2 3 2-.5 2 1 .5 3h4l.5-3 2-1 2 .5 2-3L19 13v-3l1.5-1.5-2-3-2 .5-2-1-.5-2h-4Zm5 9a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z',
        'courtesy' => 'm12 21-8-8C-3 6 6 0 12 7c6-7 15-1 8 6l-8 8Zm-5-9 3 3 7-7',
        'parking' => 'M4 3h16v18H4V3Zm5 14V7h4a3 3 0 0 1 0 6H9',
        'restriction' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM6 6l12 12',
        'info' => 'M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM12 11v6m0-10v.1',
        'book' => 'M12 5v15m0-15C9 3 5 3 3 4v15c3-1 6-1 9 1m0-15c3-2 7-2 9-1v15c-3-1-6-1-9 1',
        'exam' => 'M9 4H6a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V6a2 2 0 0 0-2-2h-3M9 2h6v4H9zM8 12h8m-8 5h5',
        'bookmark' => 'M6 3h12v18l-6-4-6 4V3Z',
        'repeat' => 'M3 10a9 9 0 0 1 15-6l3 3m0-5v5h-5M21 14a9 9 0 0 1-15 6l-3-3m0 5v-5h5',
        'chart' => 'M4 4v16h16M8 16v-4m5 4V7m5 9v-6',
        'sign' => 'm12 3 10 18H2L12 3Zm0 7v4m0 3v.1',
        'calendar' => 'M4 5h16v16H4V5Zm3-3v6m10-6v6M4 11h16m-12 4h2m4 0h2',
        'check' => 'm5 12 4 4L19 6',
        'play' => 'm8 4 13 8-13 8V4Z',
        'profile' => 'M20 21v-2a7 7 0 0 0-14 0v2M17 7a4 4 0 1 1-8 0 4 4 0 0 1 8 0Z',
    ];
@endphp
<svg {{ $attributes->merge(['class'=>'portal-icon']) }} viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false"><path d="{{ $paths[$name] ?? $paths['book'] }}" /></svg>
