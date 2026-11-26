@once
    @push('scripts')
        <script>
            function questionBookmarks(config) {
                return { @include('partials.question-bookmark-actions') };
            }
        </script>
    @endpush
    @push('styles')
        <style>
            .question-bookmark { display: inline-flex; align-items: center; justify-content: center; gap: 6px; min-width: 44px; min-height: 44px; padding: 8px 10px; border: 1px solid #e2e8f0; border-radius: 12px; background: #f8fafc; color: #475569; font-size: 12px; font-weight: 700; flex-shrink: 0; }
            .question-bookmark svg { width: 18px; height: 18px; }
            .question-bookmark.is-saved { color: #b45309; background: #fffbeb; border-color: #fcd34d; }
            .question-bookmark.is-saved svg { fill: #fef3c7; }
            .question-bookmark:focus-visible { outline: 3px solid #92a6fb; outline-offset: 2px; }
            .question-bookmark:disabled { opacity: .5; cursor: wait; }
        </style>
    @endpush
@endonce
