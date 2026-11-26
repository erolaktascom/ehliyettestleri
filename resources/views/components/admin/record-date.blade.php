@props(['record'])
@php
    $firstPublished = $record->first_published_at;
    $isBlog = $record instanceof \App\Models\BlogPost;
    $wasUpdated = $record->content_updated_at !== null;
    $published = $record->isContentPublished();
    $scheduled = $isBlog && $record->isScheduled();
    $date = $scheduled ? $record->published_at : ($wasUpdated ? $record->content_updated_at : $firstPublished);
    $formatDate = fn ($value) => $value?->copy()->timezone('Europe/Istanbul')->format('d.m.Y H:i') ?? 'Kayıt yok';
@endphp
<div title="İlk yayın: {{ $formatDate($firstPublished) }}@if($wasUpdated) | Son güncelleme: {{ $formatDate($record->content_updated_at) }}@endif">
    <span class="block text-xs text-slate-500 whitespace-nowrap mb-1">{{ $scheduled ? 'Planlanan Yayın' : ($wasUpdated ? 'Son Güncelleme' : ($published || $firstPublished ? 'Yayınlanmış' : 'Henüz yayımlanmadı')) }}</span>
    <x-admin.timestamp :date="$date" />
</div>
