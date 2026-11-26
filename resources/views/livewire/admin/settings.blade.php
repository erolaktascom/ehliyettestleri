<div>
    <x-admin.flash-messages />
    <div class="max-w-4xl bg-white rounded-2xl p-6 sm:p-8 border border-slate-200/80 shadow-sm">
        <h3 class="font-extrabold text-base text-slate-900 mb-1">Site Ayarları</h3>
        <p class="text-xs text-slate-500 mb-6">Site kimliği, alt bilgi, sosyal medya ve iletişim bilgilerini yönetin.</p>

        <form wire:submit="save" class="space-y-4">
            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Site Adı</label>
                <input type="text" wire:model="site_name" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                @error('site_name') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="site-url" class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Site Adresi</label>
                <input id="site-url" type="url" wire:model="site_url" required maxlength="2048" placeholder="https://alanadiniz.com" spellcheck="false" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                <p class="text-xs text-slate-500 mt-1">Kurulumda belirlediğiniz ana site adresi. Alan adınız değiştiğinde buradan güncelleyebilirsiniz.</p>
                @error('site_url') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">İletişim E-posta Adresi</label>
                <input type="email" wire:model="contact_email" required class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none">
                @error('contact_email') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">İletişim Telefon Numarası</label>
                <input type="text" wire:model="phone" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none" placeholder="+90 (850) 000 00 00">
                @error('phone') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>

            <div>
                <label class="block text-xs font-bold uppercase text-slate-700 mb-1.5">Açık Adres</label>
                <textarea wire:model="address" rows="3" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm focus:ring-2 focus:ring-blue-600 focus:outline-none placeholder:text-slate-400"></textarea>
                @error('address') <span class="text-xs text-red-500 font-bold">{{ $message }}</span> @enderror
            </div>

            <div class="pt-5 border-t border-slate-100 space-y-4">
                <h4 class="font-bold text-slate-900">Logo ve Favicon</h4>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                    @foreach(['logo' => 'Logo', 'favicon' => 'Favicon'] as $field => $label)
                        @php($savedUrl = $branding?->mediaUrl($field))
                        @php($displayUrl = $savedUrl ?? ($field === 'favicon' ? asset('favicon.svg') : null))
                        <div wire:key="branding-{{ $field }}-{{ md5($savedUrl ?? '') }}"
                             x-data="{
                                 preview: null,
                                 selectFile(file) {
                                     if (this.preview) URL.revokeObjectURL(this.preview);
                                     this.preview = file ? URL.createObjectURL(file) : null;
                                 },
                                 destroy() { this.selectFile(null) }
                             }">
                            <label for="{{ $field }}-upload" class="block text-sm font-semibold text-slate-700 mb-2">{{ $label }}</label>
                            @if($displayUrl)
                                <img x-show="!preview" src="{{ $displayUrl }}" alt="Mevcut {{ $label }}" class="h-12 max-w-full object-contain mb-3 rounded bg-slate-100 p-1">
                            @endif
                            <template x-if="preview">
                                <img :src="preview" alt="Seçilen {{ $label }}" class="h-12 max-w-full object-contain mb-3 rounded bg-slate-100 p-1">
                            </template>
                            @if($savedUrl)
                                <label class="flex items-center gap-2 text-sm text-slate-600 mb-3"><input type="checkbox" wire:model="remove_{{ $field }}">Mevcut {{ $label }} görselini kaldır</label>
                            @endif
                            <input id="{{ $field }}-upload" type="file" wire:model="{{ $field }}Upload"
                                   x-on:change="selectFile($event.target.files[0])"
                                   x-on:livewire-upload-error="selectFile(null)" x-on:livewire-upload-cancel="selectFile(null)"
                                   accept="{{ $field === 'logo' ? '.png,.jpg,.jpeg,.webp' : '.png,.ico' }}" class="block w-full text-sm text-slate-600">
                            <p class="text-xs text-slate-500 mt-2">{{ $field === 'logo' ? 'PNG, JPG veya WEBP; en fazla 2 MB.' : 'PNG veya ICO; en fazla 512 KB. Kare görsel kullanın (ör. 32 × 32).' }}</p>
                            <span wire:loading wire:target="{{ $field }}Upload" class="text-xs text-blue-600">Görsel yükleniyor…</span>
                            @error($field.'Upload') <span class="block text-xs text-red-500 mt-1">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="pt-5 border-t border-slate-100 space-y-4">
                <h4 class="font-bold text-slate-900">Alt Bilgi</h4>
                <div>
                    <label for="site-description" class="block text-sm font-semibold text-slate-700 mb-2">Site Açıklaması</label>
                    <textarea id="site-description" wire:model="site_description" rows="3" maxlength="2000" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm"></textarea>
                    <p class="text-xs text-slate-500 mt-1">Alt bilgideki logo altında gösterilir. SEO açıklaması boşsa sayfanın açıklaması olarak da kullanılır.</p>
                    @error('site_description') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
                <div>
                    <label for="copyright-text" class="block text-sm font-semibold text-slate-700 mb-2">Telif Hakkı (Copyright)</label>
                    <input id="copyright-text" type="text" wire:model="copyright_text" maxlength="500" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm">
                    <p class="text-xs text-slate-500 mt-1">{year} güncel yılı, {site_name} site adını otomatik gösterir. Boş bırakırsanız metin görünmez.</p>
                    @error('copyright_text') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>
            <div class="pt-5 border-t border-slate-100 space-y-4">
                <h4 class="font-bold text-slate-900">Sosyal Medya</h4>
                <p class="text-xs text-slate-500">Yalnızca kullanıcı adını girin (ör. kullaniciadi veya @kullaniciadi). Profil bağlantısı otomatik oluşturulur. Boş bırakılan hesapların simgesi gösterilmez. YouTube için kanalın @kullanıcı adını kullanın.</p>
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                    @foreach(\App\Models\SiteSetting::SOCIAL_PLATFORMS as $key => $label)
                        <div>
                            <label for="social-{{ $key }}" class="block text-sm font-semibold text-slate-700 mb-1">{{ $label }} kullanıcı adı</label>
                            <input id="social-{{ $key }}" type="text" wire:model="social_links.{{ $key }}" maxlength="101" spellcheck="false" autocapitalize="none" class="w-full px-4 py-2.5 rounded-xl border border-slate-300 text-sm">
                            @error('social_links.'.$key) <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                        </div>
                    @endforeach
                    @error('social_links') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                </div>
            </div>

            <div class="pt-4 border-t border-slate-100 flex justify-end">
                <button type="submit" wire:loading.attr="disabled" class="px-6 py-2.5 rounded-xl bg-navy-900 hover:bg-navy-800 text-white font-bold text-sm shadow-md transition active:scale-95">
                    Ayarları Güncelle
                </button>
            </div>
        </form>
    </div>
</div>
