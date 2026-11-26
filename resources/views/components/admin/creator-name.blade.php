@props(['record'])
@if($record?->creator_name)
    <span class="font-medium text-slate-600">{{ $record->creator_name }}</span>
@else
    <span class="text-slate-400" title="Bu kaydın ekleyen kişi bilgisi tutulmamış.">Kayıt yok</span>
@endif
