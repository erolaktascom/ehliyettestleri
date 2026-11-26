<span class="study-entry-icon" title="Çalışmaya Başla" aria-hidden="true">
    <x-student-icon name="arrow" />
</span>
@once
    @push('styles')
        <style>
            .study-entry-icon { display: inline-grid; place-items: center; flex-shrink: 0; width: 44px; height: 44px; border: 1px solid #dce3ff; border-radius: 14px; background: #eef2ff; color: #263fe5; transition: background .2s, color .2s; }
            .study-entry-icon svg { width: 21px; height: 21px; }
            a:hover .study-entry-icon, a:focus-visible .study-entry-icon { background: #263fe5; border-color: #263fe5; color: white; }
            a.stitch-row:focus-visible { outline: 3px solid #92a6fb; outline-offset: 3px; }
            @media (prefers-reduced-motion: reduce) { .study-entry-icon { transition: none; } }
        </style>
    @endpush
@endonce
