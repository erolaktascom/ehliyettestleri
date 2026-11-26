<div class="max-w-4xl space-y-6">
    <x-admin.flash-messages />
    <div class="flex items-center gap-3">
        <a href="{{ route('admin.integrations') }}" aria-label="Entegrasyonlara dön" class="rounded-xl border border-slate-200 bg-white p-2 text-slate-600 hover:bg-slate-50">
            <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-2xl font-bold text-slate-900">{{ $definition['name'] }}</h1>
            <p class="mt-1 text-sm text-slate-500">{{ $definition['description'] }}</p>
        </div>
    </div>
    <form wire:submit="save" class="space-y-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
        @if(!$definition['ready'])
            <div class="rounded-xl border border-amber-200 bg-amber-50 p-4 text-sm leading-6 text-amber-800">
                <strong>Pasif · Bağlantı kurulmadı</strong>
                <p>Bilgileri kaydedebilirsiniz. Bu aşamada Google ile giriş veya kayıt işlemi çalışmaz.</p>
            </div>
        @endif
        @if(isset($definition['instructions']))
            <p class="rounded-xl bg-blue-50 p-4 text-sm text-blue-900">{{ $definition['instructions'] }}</p>
        @elseif($definition['ready'])
            <div class="rounded-xl border border-blue-100 bg-blue-50 p-4 text-sm leading-6 text-blue-900">
                Google Cloud’da bir Web uygulaması OAuth istemcisi oluşturun. Client ID ve Client Secret bilgilerini aşağıya girin; geri dönüş adresini Google’daki yetkili yönlendirme adreslerine aynen ekleyin. Ardından etkinleştirip kaydedin.
                <p class="mt-2">Yayındaki sitenizde HTTPS kullanın. Google uygulaması test modundaysa giriş yapacak hesapları test kullanıcılarına ekleyin.</p>
            </div>
        @endif
        @foreach($definition['fields'] as $key => $field)
            <div>
                <label for="integration-{{ $key }}" class="mb-2 block text-sm font-semibold text-slate-700">{{ $field['label'] }}</label>
                <input id="integration-{{ $key }}" type="{{ $field['type'] }}" wire:model="fields.{{ $key }}" autocomplete="{{ $field['type'] === 'password' ? 'new-password' : 'off' }}" class="w-full rounded-xl border border-slate-200 px-3.5 py-2.5 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                @if($field['type'] === 'password')
                    <p class="mt-2 text-xs text-slate-500">{{ ($savedSecrets[$key] ?? false) ? 'Gizli anahtar kayıtlı. Boş bırakırsanız mevcut anahtar korunur.' : 'Gizli anahtar henüz girilmedi. Kaydedildiğinde şifreli saklanır.' }}</p>
                @endif
                @error('fields.'.$key)<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
            </div>
        @endforeach
        @error('fields')<p class="text-sm text-red-600">{{ $message }}</p>@enderror
        @if(isset($definition['callback_path']))
            <div>
                <label for="callback-address" class="mb-2 block text-sm font-semibold text-slate-700">Geri Dönüş Adresi (Redirect URI)</label>
                <input id="callback-address" type="text" readonly value="{{ \App\Support\GoogleLogin::callbackUrl() }}" class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3.5 py-2.5 text-sm text-slate-600">
                <p class="mt-2 text-xs text-slate-500">{{ $definition['ready'] ? 'Bu adresi sağlayıcının geri dönüş adresi alanına girin.' : 'Bağlantı tamamlandığında kullanılacak adres. Şu anda etkin değildir.' }}</p>
            </div>
        @endif
        <div class="rounded-xl border border-slate-200 bg-slate-50 p-4">
            <label class="flex items-center gap-3 text-sm font-semibold text-slate-700">
                <input type="checkbox" wire:model="is_active" @disabled(!$definition['ready']) class="size-4 rounded border-slate-300 text-blue-600 disabled:opacity-50">
                Entegrasyonu Etkinleştir
            </label>
            @if(!$definition['ready'])<p class="mt-2 text-xs text-slate-500">Bağlantı hazır olduğunda etkinleştirme açılacak.</p>@endif
            @error('is_active')<p class="mt-2 text-xs text-red-600">{{ $message }}</p>@enderror
        </div>
        <div class="flex justify-end border-t border-slate-100 pt-4">
            <button type="submit" wire:loading.attr="disabled" wire:target="save" class="rounded-xl bg-blue-600 px-6 py-2.5 text-sm font-semibold text-white hover:bg-blue-700 disabled:opacity-50">Ayarları Kaydet</button>
        </div>
    </form>
</div>
