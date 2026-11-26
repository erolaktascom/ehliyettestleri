@props(['href', 'title', 'number' => null, 'icon' => 'book', 'status' => null, 'label' => 'KONU'])
<a href="{{ $href }}" class="catalog-tile">
    <div class="catalog-tile-main">
        <div class="catalog-number" aria-hidden="true">
            @if($number !== null)<small>{{ $label }}</small><strong>{{ str_pad($number, 2, '0', STR_PAD_LEFT) }}</strong>
            @else<x-student-icon :name="$icon" />@endif
        </div>
        <div class="catalog-copy"><h3>{{ $title }}</h3>@if($status)<span class="catalog-status">{{ $status }}</span>@endif</div>
        <span class="catalog-entry-arrow" aria-hidden="true"><x-student-icon name="arrow" /></span>
    </div>
    @if($slot->isNotEmpty())<div class="catalog-progress-footer">{{ $slot }}</div>@endif
</a>