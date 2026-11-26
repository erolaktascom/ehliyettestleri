@props(['deliveries'])
@if($deliveries->isNotEmpty())
<section class="rounded-2xl border border-slate-200 bg-white p-5 space-y-3">
    <h2 class="font-bold text-slate-900">Gönderim Sonuçları</h2>
    <p class="text-xs text-slate-500">En son 50 kanal kaydı gösterilir. E-postada Gönderildi, SMTP sunucusunun kabul ettiği anlamına gelir; okunma veya gelen kutusuna ulaşma garantisi değildir.</p>
    <div class="overflow-x-auto">
        <table class="w-full text-sm text-left"><thead><tr class="border-b"><th class="p-2">Üye</th><th class="p-2">Kanal</th><th class="p-2">Sonuç</th><th class="p-2">Tarih</th></tr></thead>
        <tbody>@foreach($deliveries as $delivery)
            <tr class="border-b border-slate-100"><td class="p-2">{{ $delivery->user?->name ?? 'Silinen hesap' }}</td><td class="p-2">{{ \App\Models\NotificationDelivery::CHANNELS[$delivery->channel] ?? $delivery->channel }}</td>
            <td class="p-2">{{ \App\Models\NotificationDelivery::STATUS_LABELS[$delivery->status] ?? $delivery->status }}@if($delivery->error)<p class="text-xs text-red-600 mt-1">{{ $delivery->error }}</p>@endif</td><td class="p-2 whitespace-nowrap">{{ ($delivery->sent_at ?? $delivery->attempted_at ?? $delivery->available_at)?->format('d.m.Y H:i') }}</td></tr>
        @endforeach</tbody></table>
    </div>
</section>
@endif
