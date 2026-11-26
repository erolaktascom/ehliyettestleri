<div class="w-full min-w-0 max-w-4xl space-y-6">
    <x-admin.flash-messages />

    <div class="flex items-start gap-3">
        <a href="{{ route('admin.automation-rules') }}" aria-label="Otomasyon kurallarına dön" class="shrink-0 rounded-xl border border-slate-200 bg-white p-2 text-slate-600 transition hover:bg-slate-50">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div class="min-w-0">
            <h1 class="text-2xl font-bold text-slate-900">{{ $ruleId ? 'Otomasyon Kuralını Düzenle' : 'Yeni Otomasyon Kuralı' }}</h1>
            <p class="mt-1 text-sm leading-6 text-slate-500">Bir olay gerçekleştiğinde belirlediğiniz bekleme süresinin ardından bildirim gönderilir.</p>
        </div>
    </div>

    <form wire:submit="save" class="min-w-0 space-y-6 rounded-2xl border border-slate-200 bg-white p-4 shadow-sm sm:p-6">
        @if($rule && !$rule->activated_at)
            <p class="rounded-xl border border-amber-200 bg-amber-50 p-3 text-sm leading-6 text-amber-800">Bu kural henüz çalıştırılmadı. Tetikleyici, kanal ve mesajı belirleyip etkinleştirerek kaydedin.</p>
        @endif

        <div>
            <label for="rule-name" class="mb-2 block text-xs font-bold uppercase text-slate-700">Kural Adı *</label>
            <input id="rule-name" type="text" wire:model="name" class="block w-full min-w-0 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('name')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-3">
            <div class="grid min-w-0 grid-cols-1 gap-4 md:grid-cols-2">
                <div class="min-w-0">
                    <label for="rule-trigger" class="mb-2 block text-xs font-bold uppercase text-slate-700">Ne Zaman Çalışsın? *</label>
                    <select id="rule-trigger" wire:model.live="trigger" class="block w-full min-w-0 max-w-full rounded-xl border border-slate-200 bg-white px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                        @foreach(\App\Models\AutomationRule::TRIGGERS as $key => $label)
                            <option value="{{ $key }}">{{ $label }}</option>
                        @endforeach
                    </select>
                    @error('trigger')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
                <div class="min-w-0">
                    <label for="rule-wait" class="mb-2 block text-xs font-bold uppercase text-slate-700">Bekleme Süresi (Saat) *</label>
                    <input id="rule-wait" type="number" min="{{ $trigger === 'inactive' ? 1 : 0 }}" wire:model="wait_hours" class="block w-full min-w-0 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                    @error('wait_hours')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
                </div>
            </div>
            <p class="text-xs leading-5 text-slate-500">{{ $trigger === 'inactive' ? 'Üyenin son ziyaretinden itibaren sayılır. Aynı hareketsizlik dönemi için bir kez gönderilir.' : 'Üyelik veya başarılı sınav olayı sonrasında sayılır. Her olay için bir kez gönderilir.' }} Kural yalnız etkinleştirildikten sonraki olayları kapsar. Yönetim hesapları otomasyon alıcısı değildir.</p>
        </div>

        <fieldset class="min-w-0">
            <legend class="mb-3 text-xs font-bold uppercase text-slate-700">Hedef Kitle *</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-3">
                @foreach($audiences as $key => $label)
                    <label wire:key="rule-audience-{{ $key }}-{{ in_array($key, $target_groups, true) ? 'on' : 'off' }}" class="flex min-w-0 cursor-pointer items-center gap-3 rounded-xl border p-3 transition {{ in_array($key, $target_groups, true) ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                        <input type="checkbox" wire:click="toggleAudience('{{ $key }}')" @checked(in_array($key, $target_groups, true)) class="size-4 shrink-0 rounded border-slate-300 accent-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('target_groups')<p class="mt-2 text-xs text-red-600">En az bir hedef kitle seçin.</p>@enderror
            @error('target_groups.*')<p class="mt-2 text-xs text-red-600">Geçerli bir hedef kitle seçin.</p>@enderror
        </fieldset>

        <fieldset class="min-w-0">
            <legend class="mb-3 text-xs font-bold uppercase text-slate-700">Gönderim Kanalları *</legend>
            <div class="grid grid-cols-1 gap-3 sm:grid-cols-2">
                @foreach(\App\Models\NotificationDelivery::CHANNELS as $key => $label)
                    <label class="flex min-w-0 cursor-pointer items-center gap-3 rounded-xl border p-3 transition {{ in_array($key, $delivery_channels, true) ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-white hover:bg-slate-50' }}">
                        <input type="checkbox" wire:model.live="delivery_channels" value="{{ $key }}" class="size-4 shrink-0 rounded border-slate-300 accent-blue-600 focus:ring-blue-500">
                        <span class="text-sm font-semibold text-slate-800">{{ $label }}</span>
                    </label>
                @endforeach
            </div>
            @error('delivery_channels')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            @error('delivery_channels.*')<p class="mt-2 text-xs text-red-600">Geçerli bir kanal seçin.</p>@enderror
        </fieldset>

        <div>
            <label for="rule-subject" class="mb-2 block text-xs font-bold uppercase text-slate-700">Bildirim Başlığı @if($is_active)*@endif</label>
            <input id="rule-subject" type="text" wire:model="subject" class="block w-full min-w-0 rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
            @error('subject')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div>
            <label for="rule-content" class="mb-2 block text-xs font-bold uppercase text-slate-700">Mesaj @if($is_active)*@endif</label>
            <textarea id="rule-content" rows="5" wire:model="content" class="block w-full min-w-0 resize-y rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
            <p class="mt-2 text-xs leading-5 text-slate-500">Başlık veya mesajdaki {ad}, üyenin adıyla değiştirilir.</p>
            @error('content')<p class="mt-1 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>

        <div class="space-y-2">
            <label class="flex cursor-pointer items-center gap-3 rounded-xl border p-3.5 transition {{ $is_active ? 'border-blue-300 bg-blue-50' : 'border-slate-200 bg-slate-50' }}">
                <input type="checkbox" wire:model.live="is_active" class="size-4 shrink-0 rounded border-slate-300 accent-blue-600 focus:ring-blue-500">
                <span class="text-sm font-semibold text-slate-800">Kuralı Etkinleştir</span>
            </label>
            <p class="text-xs leading-5 text-slate-500">Pasife almak bekleyen gönderimleri iptal eder. Gönderimi başlamış bir ileti geri alınamaz. Düzenlemeler yeni oluşacak iletilere uygulanır.</p>
        </div>

        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" wire:loading.attr="disabled" class="w-full rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white shadow-sm transition hover:bg-blue-700 disabled:opacity-50 sm:w-auto">{{ $ruleId ? 'Değişiklikleri Kaydet' : 'Kuralı Kaydet' }}</button>
        </div>
    </form>

    @if($rule?->is_active && $rule->activated_at && $rule->deliveries()->where('status', 'failed')->exists())
        <button type="button" wire:click="retryFailed" class="rounded-xl border border-blue-200 px-4 py-2 text-sm font-semibold text-blue-700">Gönderilemeyenleri Yeniden Dene</button>
    @endif
    <x-admin.notification-deliveries :deliveries="$deliveries" />
</div>
