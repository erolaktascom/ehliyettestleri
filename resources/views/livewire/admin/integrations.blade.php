<div class="space-y-6">
    <x-admin.flash-messages />
    <div>
        <h1 class="text-2xl font-bold text-slate-900">Entegrasyonlar</h1>
        <p class="mt-1 text-sm text-slate-500">Hazır entegrasyonların kendilerine özel bağlantı bilgilerini buradan düzenleyin.</p>
    </div>
    <div class="grid grid-cols-1 xl:grid-cols-2 gap-5">
        @foreach($catalog as $key => $definition)
            @php($record = $integrations->get($key))
            <article class="rounded-2xl border border-slate-200 bg-white p-6 shadow-sm">
                <div class="flex items-start justify-between gap-4">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-blue-600">{{ $definition['provider'] }}</p>
                        <h2 class="mt-2 text-lg font-bold text-slate-900">{{ $definition['name'] }}</h2>
                    </div>
                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold text-slate-600">{{ $definition['ready'] && $record?->is_active ? 'Aktif' : 'Pasif' }}</span>
                </div>
                <p class="mt-3 text-sm leading-6 text-slate-500">{{ $definition['description'] }}</p>
                @if($definition['ready'] && !$record?->is_active)
                    <p class="mt-4 text-sm text-slate-500">{{ $key === 'google_login' ? 'Bağlantı altyapısı hazır. Bilgilerinizi girip etkinleştirdiğinizde giriş ve kayıt sayfalarında Google seçeneği görünür.' : 'Bağlantı altyapısı hazır. Sunucu bilgilerini girip etkinleştirerek e-posta kanalını kullanabilirsiniz.' }}</p>
                @endif
                @if(!$definition['ready'])
                    <p class="mt-4 rounded-xl bg-amber-50 px-4 py-3 text-sm text-amber-800">Ayarlar hazırlanabilir. Şu anda bağlantı kurulmaz ve kullanıcı girişinde kullanılmaz.</p>
                @endif
                <div class="mt-5 flex justify-end">
                    @if($record)
                        <a href="{{ route('admin.integrations.edit', $record->id) }}" class="rounded-xl bg-blue-600 px-5 py-2.5 text-sm font-semibold text-white hover:bg-blue-700">Ayarları Düzenle</a>
                    @else
                        <span class="text-sm text-slate-500">Kurulum bekleniyor.</span>
                    @endif
                </div>
            </article>
        @endforeach
    </div>
</div>
