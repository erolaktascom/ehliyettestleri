<div class="space-y-6 max-w-6xl mx-auto">
    <x-admin.flash-messages />
    <!-- Top Bar / Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.traffic-signs') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm" title="Listeye Dön">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $signId ? 'Trafik İşaretini Düzenle' : 'Yeni Trafik İşareti Ekle' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $signId ? 'Levha adını, kodunu, görselini ve sürücü anlamını güncelleyin.' : 'Trafik ve Çevre müfredatına uygun yeni bir işaret tanımlayın.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="save" wire:loading.attr="disabled" wire:target="save,imageUpload" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ $signId ? 'Değişiklikleri Kaydet' : 'İşareti Ekle' }}</span>
            </button>
        </div>
    </div>

    <!-- Form Content -->


    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Levha Bilgileri</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div class="sm:col-span-2">
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Levha Adı *</label>
                            <input type="text" wire:model.live="name" placeholder="Örn: Sağa Tehlikeli Viraj" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900">
                            @error('name') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Levha Kodu</label>
                            <input type="text" wire:model="code" placeholder="Örn: T-1a" 
                                   class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm font-mono text-xs focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @error('code') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Anlamı ve Sürücüye Uyarı *</label>
                        <textarea wire:model="meaning" rows="4" placeholder="Sürücünün bu levhayı gördüğünde ne yapması gerektiğini açıklayın..." 
                                  class="w-full px-4 py-3 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 leading-relaxed"></textarea>
                        @error('meaning') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label for="sign-image-upload" class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Levha Görseli</label>
                        <input id="sign-image-upload" type="file" wire:model="imageUpload" accept="image/png,image/jpeg,image/webp" class="block w-full text-sm text-slate-600">
                        <p class="mt-2 text-xs text-slate-500">PNG, JPG veya WebP · En fazla 2 MB. Levhanın gerçek görselini yükleyin.</p>
                        <p wire:loading wire:target="imageUpload" class="mt-2 text-xs text-blue-600">Görsel yükleniyor…</p>
                        @error('imageUpload') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                    @if($imageUpload && !$errors->has('imageUpload'))
                        <img src="{{ $imageUpload->temporaryUrl() }}" alt="Yeni levha görseli önizlemesi" class="h-40 w-40 object-contain rounded-xl border border-slate-200 bg-white p-3">
                    @elseif($creatorRecord?->image_url && !$removeImage)
                        <img src="{{ $creatorRecord->image_url }}" alt="{{ $name }}" class="h-40 w-40 object-contain rounded-xl border border-slate-200 bg-white p-3">
                    @else
                        <p class="rounded-xl bg-slate-50 p-4 text-sm text-slate-500">Henüz gerçek bir levha görseli eklenmemiş.</p>
                    @endif
                    @if($image_path)
                        <label class="inline-flex items-center gap-2 text-sm text-slate-600"><input type="checkbox" wire:model.live="removeImage"> Mevcut görseli kaldır</label>
                    @endif
                </div>
            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Kategori ve Yayın</h2>

                    <x-admin.creator-info :record="$creatorRecord" />
                    <fieldset>
                        <legend class="block text-xs font-bold text-slate-700 uppercase mb-2">Yayın Durumu</legend>
                        <div class="flex gap-4 text-sm"><label class="inline-flex items-center gap-2"><input type="radio" wire:model="is_published" value="1"> Yayında</label><label class="inline-flex items-center gap-2"><input type="radio" wire:model="is_published" value="0"> Taslak</label></div>
                        @error('is_published') <span class="text-xs text-red-500">{{ $message }}</span> @enderror
                    </fieldset>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">İşaret Kategorisi *</label>
                        <select wire:model="traffic_sign_category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm font-semibold text-slate-800 focus:outline-none focus:ring-2 focus:ring-blue-500">
                            @foreach($categories as $cat)
                                <option value="{{ $cat->id }}">{{ $cat->name }}</option>
                            @endforeach
                        </select>
                        @error('traffic_sign_category_id') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Görüntülenme Sırası *</label>
                        <input type="number" wire:model="order" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                        @error('order') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                </div>

                @if($signId)
                    <div class="bg-blue-50/60 rounded-2xl border border-blue-100 p-5 text-xs text-blue-900 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-blue-700">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Canlı Levhalar Bölümü</span>
                        </div>
                        <p class="text-blue-800">Trafik işaretleri modülü sitede yayındadır:</p>
                        <a href="{{ url('/trafik-isaretleri') }}" target="_blank" class="inline-flex items-center gap-1 text-blue-600 font-semibold hover:underline">
                            /trafik-isaretleri ↗
                        </a>
                    </div>
                @endif
            </div>

        </div>
    </form>
</div>
