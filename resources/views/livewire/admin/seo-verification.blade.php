<div class="space-y-6 max-w-4xl">
    <x-admin.flash-messages />
    <div>
        <h1 class="text-2xl font-bold text-slate-900">SEO Ayarları</h1>
        <p class="text-sm text-slate-500 mt-1">Arama motorları ve diğer servisler için site sahipliği doğrulaması.</p>
    </div>
    <x-admin.seo-tabs active="seo-verification" />
    <div class="bg-white rounded-2xl border border-slate-200 shadow-sm p-6 sm:p-8">
        <form wire:submit="save" class="space-y-6">
            <section  aria-labelledby="verification-heading">
                <h2 id="verification-heading" class="text-lg font-bold text-slate-900">Doğrulama Ayarları</h2>
                <p class="text-sm text-slate-500 mt-1">Site sahipliğini doğrulamak için servisin verdiği kimliği veya meta etiketini girin. Boş bırakılan alanlar siteye eklenmez.</p>
                <div class="mt-5 divide-y divide-slate-100">
                    @foreach($verificationProviders as $key => $provider)
                        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 py-5 first:pt-0" wire:key="verification-{{ $key }}">
                            <label for="verification-{{ $key }}" class="text-sm font-semibold text-slate-700 sm:pt-3">{{ $provider['label'] }}</label>
                            <div class="sm:col-span-2 min-w-0">
                                <input id="verification-{{ $key }}" type="text" wire:model="verification.{{ $key }}" autocomplete="off" spellcheck="false" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                                <p class="text-xs text-slate-500 mt-2">Doğrulama kimliği veya HTML meta etiketi.</p>
                                <code class="block text-xs text-slate-400 mt-1 break-all">{{ '<meta name="'.$provider['name'].'" content="doğrulama-kimliği">' }}</code>
                                @error('verification.'.$key) <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                            </div>
                        </div>
                    @endforeach
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-6 pt-5">
                        <label for="custom-verification" class="text-sm font-semibold text-slate-700 sm:pt-3">Diğer Servisler</label>
                        <div class="sm:col-span-2 min-w-0">
                            <textarea id="custom-verification" wire:model="custom_verification" rows="4" spellcheck="false" class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-xs font-mono focus:outline-none focus:ring-2 focus:ring-blue-500"></textarea>
                            <p class="text-xs text-slate-500 mt-2">Her satıra bir doğrulama meta etiketi ekleyin. En fazla 20 etiket ekleyebilirsiniz. Yalnız name ve content alanları kabul edilir.</p>
                            @error('custom_verification') <p class="text-xs text-red-600 mt-2">{{ $message }}</p> @enderror
                        </div>
                    </div>
                </div>
            </section>
            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition disabled:opacity-50">
                    Doğrulama Ayarlarını Kaydet
                </button>
            </div>
        </form>
    </div>
</div>