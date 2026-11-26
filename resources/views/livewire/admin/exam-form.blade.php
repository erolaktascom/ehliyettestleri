<div class="space-y-6 max-w-6xl mx-auto">
    <x-admin.flash-messages />
    <!-- Top Bar / Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.exams') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm" title="Listeye Dön">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $examId ? 'Sınavı Düzenle' : 'Yeni Deneme Sınavı Ekle' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $examId ? 'Sınav parametrelerini, süresini, baraj puanını ve açıklamasını güncelleyin.' : 'Sürücü adayları için yeni bir online deneme sınavı tanımlayın.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="save" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ $examId ? 'Değişiklikleri Kaydet' : 'Sınavı Oluştur' }}</span>
            </button>
        </div>
    </div>

    <!-- Form Content -->
    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Sınav Bilgileri</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Sınav Başlığı *</label>
                        <input type="text" wire:model.live="title" placeholder="Örn: 2026 MEB Ehliyet Deneme Sınavı - Seviye 1" 
                               class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold text-slate-900">
                        @error('title') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Kalıcı Bağlantı (Slug) *</label>
                        <div class="flex rounded-xl border border-slate-200 overflow-hidden focus-within:ring-2 focus-within:ring-blue-500 bg-slate-50">
                            <span class="inline-flex items-center px-3.5 text-xs text-slate-400 font-mono select-none border-r border-slate-200">/deneme/</span>
                            <input type="text" wire:model="slug" class="w-full px-3 py-2 bg-white text-xs font-mono text-slate-800 focus:outline-none">
                        </div>
                        @error('slug') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Sınav Açıklaması</label>
                        <textarea wire:model="description" rows="4" placeholder="Sınav kapsamı ve hedef kitle hakkında bilgilendirici açıklama..." 
                                  class="w-full px-4 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 text-slate-700 leading-relaxed"></textarea>
                    </div>
                </div>

                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Sınav Parametreleri</h2>

                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Soru Sayısı *</label>
                            <div class="rounded-xl bg-slate-50 px-3.5 py-2.5 font-bold"><x-admin.question-counts :published="$selectedQuestions->where('status', 'Yayında')->count()" :draft="$selectedQuestions->where('status', 'Taslak')->count()" /></div><p class="mt-1 text-xs text-slate-500">Seçtiğiniz sorulardan hesaplanır.</p>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Süre (Dakika) *</label>
                            <input type="number" wire:model="duration_minutes" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                            @error('duration_minutes') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Geçme Notu (Puan) *</label>
                            <input type="number" wire:model="passing_score" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                            @error('passing_score') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-5">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Yayın Durumu</h2>

                    <x-admin.creator-info :record="$creatorRecord" />

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Sıralama Önceliği *</label>
                        <input type="number" wire:model="order" class="w-full px-3.5 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-bold">
                        @error('order') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>

                    <div class="pt-2">
                        <label class="block text-xs font-bold uppercase text-slate-600 mb-1.5" for="publication-status">Yayın Durumu</label>
                        <select id="publication-status" wire:model.boolean="is_published" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="true" @selected($is_published)>Yayında</option>
                            <option value="false" @selected(!$is_published)>Taslak</option>
                        </select>
                        <p class="text-xs text-slate-500 mt-1.5">Taslak kayıtlar sitede listelenmez.</p>
                        <div class="mt-4"><x-admin.publication-date-input /></div>
                        <x-admin.content-dates :record="$creatorRecord" />
                    </div>

                </div>

                @if($creatorRecord?->is_published)
                    @php($liveUrl = route('exams.play', ['exam' => $creatorRecord->slug]))
                    <div class="bg-blue-50/60 rounded-2xl border border-blue-100 p-5 text-xs text-blue-900 space-y-2">
                        <div class="font-bold flex items-center gap-1.5 text-blue-700">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            <span>Canlı Deneme Sınavı Linki</span>
                        </div>
                        <p class="text-blue-800">Öğrenciler bu sınavı sitede çözebilir:</p>
                        <a href="{{ $liveUrl }}" target="_blank" rel="noopener" class="inline-flex items-center gap-1 break-all text-blue-600 font-semibold hover:underline">
                            {{ parse_url($liveUrl, PHP_URL_PATH) }} ↗
                        </a>
                    </div>
                @endif
            </div>

        </div>
        @include('livewire.admin.partials.test-questions')
        <x-admin.seo-fields :custom="filled($seo_title) || filled($seo_description)" />
    </form>
</div>
