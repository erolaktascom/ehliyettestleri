@props(['record'])
@if($record)
    @php
        $dates = ['İlk Yayınlanma' => $record->first_published_at];
        if ($record instanceof \App\Models\BlogPost && $record->isScheduled()) $dates['Planlanan Yayın'] = $record->published_at;
        if ($record->content_updated_at) $dates['Son Güncelleme'] = $record->content_updated_at;
    @endphp
    <dl class="border-t border-slate-100 pt-4 space-y-3 text-xs">
        @if(filled($record->last_editor_name))
            <div class="flex items-start justify-between gap-3">
                <dt class="text-slate-500">Son Düzenleyen</dt>
                <dd class="text-right font-semibold text-slate-700">{{ $record->last_editor_name }}</dd>
            </div>
        @endif
        @foreach($dates as $label => $date)
            <div class="flex items-start justify-between gap-3">
                <dt class="text-slate-500">{{ $label }}</dt>
                <dd class="text-right"><x-admin.timestamp :date="$date" /></dd>
            </div>
        @endforeach
    </dl>
@endif
