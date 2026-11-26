<div class="space-y-6 max-w-6xl mx-auto">
    <x-admin.flash-messages />
    <!-- Top Bar / Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.blog-posts') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm" title="Listeye Dön">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $postId ? 'Blog Yazısını Düzenle' : 'Yeni Blog Yazısı Yaz' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $postId ? 'Yazı içeriğini, başlığını ve kategorisini güncelleyin.' : 'Sürücü adaylarına rehberlik edecek yeni bir makale yayınlayın.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save,featuredImageUpload" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ $postId ? 'Değişiklikleri Kaydet' : 'Yazıyı Kaydet' }}</span>
            </button>
        </div>
    </div>

    <!-- Form Content -->
    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Makale Detayları</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Makale Başlığı *</label>
                        <input type="text" wire:model.live="title" placeholder="Örn: 2026 Ehliyet Sınavına Hazırlanırken Dikkat Edilmesi Gereken 5 Kural" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900">
                        @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kalıcı Bağlantı (Slug) *</label>
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 bg-slate-50">
                            <span class="inline-flex items-center px-3.5 text-xs text-slate-400 font-mono select-none border-r border-slate-200">/blog/</span>
                            <input type="text" wire:model="slug" class="w-full px-3 py-2 bg-white text-xs font-mono text-slate-800 focus:outline-none">
                        </div>
                        @error('slug') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Özet / Spot Metin</label>
                        <textarea wire:model="summary" rows="3" placeholder="Makalenin ana fikrini özetleyen ve ana sayfada görünecek spot metin..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700"></textarea>
                    </div>
                </div>

                <!-- Rich Content Area -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Makale İçeriği (HTML / Zengin Metin) *</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Alt başlıklar (&lt;h2&gt;, &lt;h3&gt;), listeler ve paragraflarla zenginleştirin.</p>
                        </div>
                    </div>

                    <div>
                        <x-rich-editor wire:model="content" min-height="320px" placeholder="Makale metnini başlıklar, listeler ve biçimlendirmelerle buraya yazın..." />
                        @error('content') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Yayın Seçenekleri</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Blog Kategorisi *</label>
                        <select wire:model="blog_category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($categories as $c)
                                <option value="{{ $c->id }}">{{ $c->name }}</option>
                            @endforeach
                        </select>
                        @error('blog_category_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <span class="block text-xs font-bold uppercase text-slate-600 mb-1.5">Yazar</span>
                        <p class="w-full px-3 py-2 rounded-xl border border-slate-200 bg-white text-sm text-slate-700">{{ $author_name ?: 'Kayıt yok' }}</p>
                        <p class="text-xs text-slate-500 mt-1">Yazıyı ilk ekleyen kişi otomatik kaydedilir.</p>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Yayın Tarihi</label>
                        <input type="datetime-local" wire:model="published_at" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700">
                        @error('published_at') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-3 border-t border-slate-100">
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5" for="publication-status">Yayın Durumu</label>
                        <select id="publication-status" wire:model.boolean="is_published" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="true" @selected($is_published)>Yayında</option>
                            <option value="false" @selected(!$is_published)>Taslak</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1.5">Taslak kayıtlar sitede listelenmez. Yayında seçilip ileri tarih girilen yazılar o tarihte otomatik görünür.</p>
                        <x-admin.content-dates :record="$creatorRecord" />
                    </div>

                </div>

                <section class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4" aria-labelledby="featured-image-heading">
                    <h2 id="featured-image-heading" class="text-base font-bold text-slate-900">Öne Çıkan Görsel</h2>
                    <label for="blog-featured-image" class="block text-sm text-slate-600">Blog kapağı</label>
                    <input id="blog-featured-image" type="file" wire:model="featuredImageUpload" accept="image/jpeg,image/png,image/webp" class="block w-full text-sm text-slate-600">
                    <p class="text-xs text-slate-500">JPG, PNG veya WebP · En fazla 2 MB, 4096 × 4096 piksel. Yatay 16:9 görsel önerilir.</p>
                    <p wire:loading wire:target="featuredImageUpload" class="text-xs text-blue-600" role="status">Görsel yükleniyor…</p>
                    @error('featuredImageUpload') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                    @if($featuredImageUpload && !$errors->has('featuredImageUpload'))
                        <img src="{{ $featuredImageUpload->temporaryUrl() }}" alt="Yeni blog kapağı önizlemesi" class="w-full aspect-video object-cover rounded-xl border border-slate-200">
                        <button type="button" wire:click="$set('featuredImageUpload', null)" class="text-sm text-slate-600 underline">Seçimi iptal et</button>
                    @elseif($creatorRecord?->featured_image_url && !$removeFeaturedImage)
                        <img src="{{ $creatorRecord->featured_image_url }}" alt="Mevcut blog kapağı" class="w-full aspect-video object-cover rounded-xl border border-slate-200">
                    @endif
                    @if($creatorRecord?->featured_image_url && !$featuredImageUpload)
                        <label class="flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="removeFeaturedImage"> Mevcut görseli kaldır</label>
                    @endif
                </section>

                @if($postId && $creatorRecord?->isContentPublished())
                    <div class="bg-blue-50/60 rounded-2xl border border-blue-100 p-5 text-xs text-blue-900 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-blue-700">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Canlı Blog Bağlantısı</span>
                        </div>
                        <p class="text-blue-800">Bu makale sitede şu adreste yayınlanmaktadır:</p>
                        <a href="{{ route('blog.show', ['post' => $creatorRecord->slug]) }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 break-all text-blue-600 font-semibold hover:underline font-mono">
                            /blog/{{ $creatorRecord->slug }} ↗
                        </a>
                    </div>
                @endif
            </div>

        </div>
        <x-admin.seo-fields :custom="filled($seo_title) || filled($seo_description)" />
    </form>
</div>
