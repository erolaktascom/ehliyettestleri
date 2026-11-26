<div class="space-y-6 max-w-6xl mx-auto">
    <x-admin.flash-messages />
    <!-- Top Bar / Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.pages') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm" title="Listeye Dön">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $pageId ? 'Sayfayı Düzenle' : 'Yeni Sayfa Oluştur' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $pageId ? 'Kurumsal veya yasal sayfa içeriğini güncelleyin.' : 'Sitede yayınlanacak yeni bir statik/dinamik sayfa oluşturun.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="save" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ $pageId ? 'Değişiklikleri Kaydet' : 'Sayfayı Oluştur' }}</span>
            </button>
        </div>
    </div>

    <!-- Form Content -->


    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Sayfa Başlığı ve Bağlantı</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Sayfa Başlığı *</label>
                        <input type="text" wire:model.live="title" placeholder="Örn: Gizlilik Politikası ve KVKK Aydınlatma Metni" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900">
                        @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kalıcı Bağlantı (Slug) *</label>
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 bg-slate-50">
                            <span class="inline-flex items-center px-3.5 text-xs text-slate-400 font-mono select-none border-r border-slate-200">/</span>
                            <input type="text" wire:model="slug" class="w-full px-3 py-2 bg-white text-xs font-mono text-slate-800 focus:outline-none">
                        </div>
                        @error('slug') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kısa Açıklama / Özet</label>
                        <textarea wire:model="short_description" rows="2" placeholder="Arama motorları ve sayfa başlığı altında görünecek kısa açıklama..." 
                                  class="w-full px-4 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700"></textarea>
                    </div>
                </div>

                <!-- Rich Content Area -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Sayfa Metni (HTML / Zengin Metin)</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Paragraflar, başlıklar ve maddeler halinde kurumsal sayfa içeriğinizi girin. %site_adi% güncel site adını gösterir.</p>
                        </div>
                    </div>

                    <div>
                        <x-rich-editor wire:model="content" min-height="320px" placeholder="Kurumsal sayfa metnini buraya yazın..." />
                        @error('content') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Yayın Durumu</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Durum *</label>
                        <select wire:model="status" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="published">Yayında (Aktif)</option>
                            <option value="draft">Taslak (Gizli)</option>
                        </select>
                        @error('status') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Yayın Tarihi</label>
                        <input type="datetime-local" wire:model="published_at" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700">
                        @error('published_at') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <x-admin.content-dates :record="$creatorRecord" />

                </div>

                @if($creatorRecord?->isContentPublished())
                    <div class="bg-blue-50/60 rounded-2xl border border-blue-100 p-5 text-xs text-blue-900 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-blue-700">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Canlı Sayfa Bağlantısı</span>
                        </div>
                        <p class="text-blue-800">Bu sayfa sitede şu adreste yayınlanmaktadır:</p>
                        <a href="{{ route('page.show', ['slug' => $creatorRecord->slug]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 break-all text-blue-600 font-semibold hover:underline font-mono">
                            /{{ $creatorRecord->slug }} ↗
                        </a>
                    </div>
                @endif
            </div>

        </div>
        <x-admin.seo-fields :general="false" />
    </form>
</div>
