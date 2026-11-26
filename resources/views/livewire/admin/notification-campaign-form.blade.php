<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.notification-campaigns') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">{{ $campaignId ? 'Bildirim Kampanyasını Düzenle' : 'Yeni Bildirim Gönder' }}</h1>
                <p class="text-sm text-slate-500 mt-0.5">Duyurunuzun içeriğini ve hedef kitlesini belirleyin.</p>
            </div>
        </div>
    </div>

    <form wire:submit="save" class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-6">
        @if($campaign && !$campaign->execution_enabled && !$campaign->prepared_at && in_array($campaign->status, ['Gönderildi', 'Planlandı']))
            <p class="text-sm text-amber-700">Bu eski kaydın “{{ $campaign->status }}” durumu gerçek gönderim kaydı içermiyor. Yeniden planlanana kadar gönderilmez.</p>
        @endif
        @if($campaign?->prepared_at)
            <p class="text-sm font-semibold text-blue-800">Gönderim durumu: {{ $campaign->status }}. Gönderimi başlamış kampanyanın içeriği değiştirilemez.</p>
        @endif
        <fieldset @disabled($campaign?->prepared_at) class="space-y-6">
        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bildirim Başlığı *</label>
            <input type="text" wire:model="title" placeholder="Örn: Yeni Seviye Deneme Sınavları Yayınlandı!" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bildirim Türü *</label>
                <input type="text" wire:model="type" placeholder="Genel Duyuru, Sınav Hatırlatma, vb." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @error('type') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
        </div>

        <fieldset class="space-y-3">
            <legend class="text-xs font-bold text-slate-700 uppercase mb-3">Hedef Kitle *</legend>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                @foreach($audiences as $key => $label)
                    <label wire:key="notification-audience-{{ $key }}-{{ in_array($key, $target_groups, true) ? 'selected' : 'unselected' }}" class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer">
                        <input type="checkbox" wire:click="toggleAudience('{{ $key }}')" @checked(in_array($key, $target_groups, true)) class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                    </label>
                @endforeach
                <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 text-slate-400">
                    <input type="checkbox" disabled class="size-4 rounded border-slate-300">
                    <span class="text-sm">Ziyaretçiler <span class="text-xs">(abonelik gerekli)</span></span>
                </label>
            </div>
            <p class="text-xs text-slate-500">Üyeler, Premium olmayan kayıtlı hesaplardır. Herkes, tüm kayıtlı hesapları kapsar. Ziyaretçi bildirim aboneliği henüz bulunmuyor.</p>
            @if($legacyTarget)
                <p class="text-sm text-amber-700">Önceki hedef kitle: {{ $legacyTarget }}. Kaydetmeden önce yukarıdan bir hedef kitle seçin.</p>
            @endif
            @error('target_groups') <span class="text-xs text-red-500 block">En az bir hedef kitle seçin.</span> @enderror
            @error('target_groups.*') <span class="text-xs text-red-500 block">Geçerli bir hedef kitle seçin.</span> @enderror
            <div class="rounded-xl border border-blue-100 bg-blue-50 px-4 py-3" role="status" aria-live="polite" aria-atomic="true">
                <span class="text-sm text-blue-800">Hedef kitle:</span>
                <strong class="text-lg text-blue-900" data-recipient-count="{{ $recipientCount }}">{{ number_format($recipientCount, 0, ',', '.') }} kişi</strong>
                <span wire:loading wire:target="toggleAudience" class="text-xs text-blue-700 ml-2">Hesaplanıyor…</span>
                <p class="text-xs text-blue-700 mt-1">Seçilen gruplardaki hesaplar bir kez sayılır. Bu sayı teslim edilen bildirim sayısı değildir.</p>
            </div>
        </fieldset>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Durum *</label>
                <select wire:model.live="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @if($campaign?->prepared_at)<option value="{{ $status }}">{{ $campaign->status }}</option>@endif
                    <option value="Sırada">Şimdi gönder</option>
                    <option value="Planlandı">Planlandı</option>
                    <option value="Taslak">Taslak</option>
                </select>
                @error('status') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
            </div>
            @if($status === 'Planlandı')
                <div>
                    <label for="notification-schedule" class="block text-xs font-bold text-slate-700 uppercase mb-1">Gönderim Tarihi ve Saati *</label>
                    <input id="notification-schedule" type="datetime-local" wire:model="scheduled_at" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm">
                    @error('scheduled_at') <span class="text-xs text-red-500 mt-1 block">Gelecekte bir tarih ve saat seçin.</span> @enderror
                </div>
            @endif
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 pt-1">
            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model="is_in_app" class="size-4 rounded border-slate-300 text-blue-600 focus:ring-blue-500">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">Site İçi Bildirim</span>
                    <span class="text-xs text-slate-500">Üyenin sitedeki bildirim kutusuna eklenir.</span>
                </div>
            </label>

            <label class="flex items-center gap-3 p-3.5 rounded-xl border border-slate-200 bg-slate-50/50 cursor-pointer hover:bg-slate-50">
                <input type="checkbox" wire:model="is_email" class="size-4 rounded border-slate-300 text-amber-600 focus:ring-blue-500">
                <div>
                    <span class="text-sm font-semibold text-slate-800 block">E-posta Bülteni</span>
                    <span class="text-xs text-slate-500">Kayıtlı e-posta adreslerine duyuru e-postası ilet.</span>
                </div>
            </label>
        </div>
        @error('is_in_app') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('is_email') <p class="text-sm text-red-600">{{ $message }}</p> @enderror
        @error('is_push') <p class="text-sm text-red-600">{{ $message }}</p> @enderror

        <div>
            <label class="block text-xs font-bold text-slate-700 uppercase mb-1">Bildirim İçeriği</label>
            <textarea wire:model="content" rows="4" placeholder="Bildirim detay metni veya mesaj..." class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            @error('content') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
        </div>

        <p class="text-xs text-slate-500">Taslak gönderilmez. Şimdi gönder veya Planlandı seçimini kaydettiğinizde gönderim işleme alınır. E-posta için Entegrasyonlar bölümünde SMTP bağlantısı etkin olmalıdır.</p>
        @unless($campaign?->prepared_at)
        <div class="flex items-center justify-end gap-3 pt-4 border-t border-slate-100">
            
            <button type="submit" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                {{ $campaignId ? 'Değişiklikleri Kaydet' : 'Kampanyayı Kaydet' }}
            </button>
        </div>
        @endunless
        </fieldset>
    </form>
    @if($campaign?->execution_enabled && in_array($campaign->status, ['Sırada', 'Planlandı', 'Gönderiliyor', 'Hata']))
        <div class="flex flex-wrap gap-3">
            <button type="button" wire:click="cancelPending" class="rounded-xl border border-red-200 px-4 py-2 text-sm font-semibold text-red-700">Bekleyen Gönderimleri İptal Et</button>
            @if($campaign->status === 'Hata' && $campaign->deliveries()->where('status', 'failed')->exists())
                <button type="button" wire:click="retryFailed" class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Gönderilemeyenleri Yeniden Dene</button>
            @endif
        </div>
    @endif
    <x-admin.notification-deliveries :deliveries="$deliveries" />
</div>
