<div class="site-card contact-page-form">
    @if ($submitted)
        <div class="py-12 text-center">
            <div class="size-16 rounded-full bg-emerald-50 text-emerald-600 flex items-center justify-center mx-auto mb-4 border border-emerald-200">
                <svg class="size-8" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2.5" d="M5 13l4 4L19 7"/>
                </svg>
            </div>
            <h3 class="text-xl font-extrabold text-slate-900 mb-2">Mesajınız Alındı!</h3>
            <p class="text-sm text-slate-600 max-w-md mx-auto mb-6">
                Talebiniz başarıyla yönetici panelimize iletildi. En kısa sürede e-posta adresiniz üzerinden geri dönüş sağlanacaktır.
            </p>
            <button wire:click="$set('submitted', false)" class="site-button-secondary text-xs px-5 py-2.5">
                Yeni Mesaj Gönder
            </button>
        </div>
    @else
        <div class="contact-page-form-heading"><h2>Mesajınızı gönderin</h2><p>Formu doldurun, mesajınız bize ulaşsın.</p></div>
        <form wire:submit="submit" class="grid gap-5 sm:grid-cols-2">
            <!-- Honeypot for bot spam prevention -->
            <div class="hidden" aria-hidden="true">
                <input type="text" wire:model="website" tabindex="-1" autocomplete="off">
            </div>

            <div>
                <label for="c_name" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Ad Soyad</label>
                <input type="text" id="c_name" wire:model="name" required
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 placeholder:text-slate-400"
                       placeholder="Adınız Soyadınız">
                @error('name') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="c_email" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">E-posta</label>
                <input type="email" id="c_email" wire:model="email" required
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 placeholder:text-slate-400"
                       placeholder="ornek@email.com">
                @error('email') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="c_phone" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Telefon <span class="text-muted font-normal">(isteğe bağlı)</span></label>
                <input type="tel" id="c_phone" wire:model="phone"
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 placeholder:text-slate-400"
                       placeholder="05xx xxx xx xx">
                @error('phone') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div>
                <label for="c_subject" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Konu</label>
                <input type="text" id="c_subject" wire:model="subject" required
                       class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 placeholder:text-slate-400"
                       placeholder="Mesajınızın konusu">
                @error('subject') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-2">
                <label for="c_message" class="block text-xs font-bold text-slate-700 uppercase tracking-wider mb-1.5">Mesajınız</label>
                <textarea id="c_message" wire:model="message" rows="5" required minlength="10"
                          class="w-full rounded-xl border border-slate-300 bg-white px-4 py-3 text-sm outline-none transition focus:border-brand-600 focus:ring-2 focus:ring-brand-100 placeholder:text-slate-400"
                          placeholder="Sorunuzu veya geri bildiriminizi buraya yazabilirsiniz..."></textarea>
                @error('message') <span class="text-xs text-red-500 font-bold mt-1 block">{{ $message }}</span> @enderror
            </div>

            <div class="sm:col-span-2 flex flex-col sm:flex-row sm:items-center justify-between gap-4 pt-2">
                <button type="submit" wire:loading.attr="disabled"
                        class="site-button-primary w-full sm:w-auto py-3 px-8 text-sm disabled:opacity-50">
                    <span wire:loading.remove><x-action-label icon="send">Mesajı Gönder</x-action-label></span>
                    <span wire:loading>Gönderiliyor...</span>
                </button>
                <p class="text-xs text-muted">Bilgileriniz güvenle saklanır ve gizli tutulur.</p>
            </div>
        </form>
    @endif
</div>
