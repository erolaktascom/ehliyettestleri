@props(['item', 'icon' => null, 'number' => null, 'status' => 'not_started', 'attempt' => null, 'locked' => false, 'kind' => 'test'])
@php
    $completed = $status === 'completed' && $attempt;
    $passed = $completed && ($kind === 'exam' ? $attempt->is_passed : $attempt->score >= 70);
    $stateClass = $locked ? 'catalog-locked' : ($completed ? ($passed ? 'catalog-passed' : 'catalog-failed') : ($status === 'in_progress' ? 'catalog-active' : ''));
@endphp
<article class="study-card catalog-tile {{ $stateClass }}">
    <div class="catalog-tile-main">
        <div class="catalog-number" aria-hidden="true">
            @if($icon)
                <x-student-icon :name="$icon" />
            @else
                <small>{{ $kind === 'exam' ? 'DENEME' : 'TEST' }}</small>
                <strong>{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</strong>
            @endif
        </div>
        <div class="catalog-copy">
            <h3>{{ $item->title }}</h3>
            <span class="catalog-status">
                @if($locked)
                    <x-student-icon name="lock" /> Kilitli · Üyelere Özel
                @elseif($completed)
                    <x-student-icon name="check" /> {{ $passed ? 'Geçti' : 'Kaldı' }} · Tamamlandı
                @elseif($status === 'in_progress')
                    <x-student-icon name="repeat" /> Yarım Kalan
                @else
                    <span class="catalog-status-dot" aria-hidden="true"></span> {{ $status === 'guest' ? 'Hemen Başla' : 'Başlanmadı' }}
                @endif
            </span>
        </div>
        <x-study-card-action :item="$item" :status="$status" :attempt="$attempt" :locked="$locked" :kind="$kind" />
    </div>
    @if($completed)
        <div class="catalog-result">
            <div class="catalog-result-counts"><span><b>{{ $attempt->correct_count ?? 0 }}</b> Doğru</span><span><b>{{ $attempt->incorrect_count ?? 0 }}</b> Yanlış</span><span><b>{{ $attempt->empty_count ?? 0 }}</b> Boş</span></div>
            <span class="catalog-score"><strong>{{ round($attempt->score) }}</strong> Puan</span>
        </div>
    @endif
</article>
