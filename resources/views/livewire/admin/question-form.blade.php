<div class="space-y-6 max-w-6xl mx-auto">
    <x-admin.flash-messages />
    <!-- Top Bar / Navigation -->
    <div class="flex items-center justify-between">
        <div class="flex items-center gap-3">
            <a href="{{ route('admin.questions') }}" class="p-2 rounded-xl border border-slate-200 bg-white hover:bg-slate-50 text-slate-600 transition shadow-sm" title="Listeye Dön">
                <svg class="size-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/></svg>
            </a>
            <div>
                <h1 class="text-2xl font-bold text-slate-900">
                    {{ $questionId ? 'Soruyu Düzenle (ID #' . $questionId . ')' : 'Yeni Soru Ekle' }}
                </h1>
                <p class="text-sm text-slate-500 mt-0.5">
                    {{ $questionId ? 'Soru kökünü, dinamik seçenekleri, doğru cevabı ve açıklamasını güncelleyin.' : 'MEB müfredatına uygun yeni bir sınav/test sorusu tanımlayın.' }}
                </p>
            </div>
        </div>

        <div class="flex items-center gap-2">
            <button wire:click="save" type="button" class="inline-flex items-center gap-2 px-5 py-2.5 rounded-xl bg-blue-600 hover:bg-blue-700 text-white font-semibold text-sm shadow-sm transition">
                <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                <span>{{ $questionId ? 'Değişiklikleri Kaydet' : 'Soruyu Kaydet' }}</span>
            </button>
        </div>
    </div>


    <!-- Form Content -->


    <form wire:submit="save">
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
            
            <!-- Left Main Column (2 cols) -->
            <div class="lg:col-span-2 space-y-6">
                <!-- Soru Kökü (Metin Editörü Destekli) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="flex items-center justify-between border-b border-slate-100 pb-3">
                        <div>
                            <h2 class="text-base font-bold text-slate-900">Soru Kökü Metni (Zengin Metin Editörü) *</h2>
                            <p class="text-xs text-slate-400 mt-0.5">Soru metnini kalın yazı, madde işaretleri veya vurgularla zenginleştirebilirsiniz.</p>
                        </div>
                    </div>

                    <div>
                        <x-rich-editor wire:model="question_text" min-height="160px" placeholder="Soru metnini eksiksiz ve anlaşılır biçimde yazın..." />
                        @error('question_text') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>


                </div>

                <!-- Dinamik Şıklar / Çoktan Seçmeli Seçenekler (Artırılabilir & Azaltılabilir) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-3 border-b border-slate-100 pb-3">
                        <div>
                            <div class="flex items-center gap-2">
                                <h2 class="text-base font-bold text-slate-900">Seçenekler ve Doğru Cevap</h2>
                                <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-blue-100 text-blue-800">
                                    {{ count($options) }} Şık
                                </span>
                            </div>
                            <p class="text-xs text-slate-400 mt-0.5">Doğru cevabın solundaki yeşil radyo butonunu seçin. İstediğiniz kadar şık ekleyip kaldırabilirsiniz.</p>
                        </div>

                        <!-- Şık Ekle Butonu -->
                        <button type="button" wire:click="addOption" 
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 rounded-xl bg-blue-50 hover:bg-blue-100 text-blue-700 text-xs font-bold transition shrink-0 border border-blue-200">
                            <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span>Yeni Şık Ekle (+{{ chr(65 + count($options)) }})</span>
                        </button>
                    </div>

                    @error('options') <div class="text-xs text-red-500 font-semibold p-2 bg-red-50 rounded-lg">{{ $message }}</div> @enderror
                    @error('correct_option') <div class="text-xs text-red-500 font-semibold p-2 bg-red-50 rounded-lg">{{ $message }}</div> @enderror

                    <!-- Options Dynamic List -->
                    <div class="space-y-3">
                        @foreach($options as $index => $opt)
                            <div class="flex items-center gap-3 p-3 rounded-xl border transition {{ $correct_option === $opt['key'] ? 'bg-emerald-50/70 border-emerald-300 ring-1 ring-emerald-400' : 'bg-slate-50/60 border-slate-200 hover:border-slate-300' }}">
                                <!-- Correct Option Radio -->
                                <label class="inline-flex items-center gap-2.5 cursor-pointer shrink-0" title="Doğru cevap olarak işaretle">
                                    <input type="radio" wire:model.live="correct_option" value="{{ $opt['key'] }}" class="size-5 text-emerald-600 focus:ring-emerald-500">
                                    <span class="size-8 rounded-lg flex items-center justify-center font-black text-sm shadow-xs {{ $correct_option === $opt['key'] ? 'bg-emerald-600 text-white' : 'bg-white text-slate-700 border border-slate-300' }}">
                                        {{ $opt['key'] }}
                                    </span>
                                </label>

                                <!-- Option Text Field -->
                                <div class="w-full">
                                    <input type="text" 
                                           wire:model="options.{{ $index }}.text" 
                                           placeholder="{{ $opt['key'] }} şıkkı metnini girin..." 
                                           class="w-full px-3.5 py-2 bg-white rounded-lg border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-medium">
                                    @error('options.' . $index . '.text') 
                                        <span class="text-xs text-red-500 block mt-1">Bu şık metni boş bırakılamaz.</span> 
                                    @enderror
                                </div>

                                <!-- Remove Option Button -->
                                @if(count($options) > 2)
                                    <button type="button" 
                                            wire:click="removeOption({{ $index }})" 
                                            class="p-2 text-slate-400 hover:text-red-600 hover:bg-red-50 rounded-lg transition shrink-0" 
                                            title="{{ $opt['key'] }} Şıkkını Sil">
                                        <svg class="size-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    </div>

                    <div class="pt-2 flex items-center justify-between text-xs text-slate-500">
                        <span>En az 2 şık (Doğru/Yanlış soruları için) veya 4-5 şık ekleyebilirsiniz.</span>
                        <span class="font-bold text-emerald-700">Seçilen Doğru Cevap: {{ $correct_option }}</span>
                    </div>
                </div>

                <!-- Detaylı Çözüm Açıklaması (Metin Editörü Destekli) -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <div class="border-b border-slate-100 pb-3">
                        <h2 class="text-base font-bold text-slate-900">Detaylı Çözüm ve Açıklama (Zengin Metin Editörü)</h2>
                        <p class="text-xs text-slate-400 mt-0.5">Soru çözüldükten sonra öğrencinin göreceği kural, gerekçe ve eğitici bilgi.</p>
                    </div>

                    <div>
                        <x-rich-editor wire:model="explanation" min-height="130px" placeholder="Doğru cevabın gerekçesini ve ilgili MEB mevzuat kuralını açıklayın..." />
                        @error('explanation') <span class="text-xs text-red-500 mt-1 block">{{ $message }}</span> @enderror
                    </div>
                </div>
            </div>

            <!-- Right Sidebar Column (1 col) -->
            <div class="space-y-6">
                <!-- Bağlantılar ve Sınav Seçimi -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Soru Kategorisi</h2>

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Soru Kategorisi</label>
                        <select wire:model.live="topic_category_id" class="w-full px-3.5 py-2.5 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500">
                            <option value="">-- Kategori Yok --</option>
                            @foreach($categories as $tc)
                                <option value="{{ $tc->id }}">{{ $tc->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    @error('topic_category_id') <p class="text-xs text-red-600">{{ $message }}</p> @enderror
                </div>

                <!-- Yayın bilgileri -->
                <div class="bg-white rounded-2xl border border-slate-200 p-6 shadow-sm space-y-4">
                    <h2 class="text-base font-bold text-slate-900 border-b border-slate-100 pb-3">Soru Parametreleri</h2>

                    <x-admin.creator-info :record="$creatorRecord" />

                    <div>
                        <label class="block text-xs font-bold text-slate-700 uppercase mb-1.5">Yayın Durumu</label>
                        <select wire:model="status" class="w-full px-3 py-2 rounded-xl border border-slate-200 text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 font-semibold">
                            <option value="Yayında">Yayında</option>
                            <option value="Taslak">Taslak</option>
                        </select>
                        @error('status') <p class="text-xs text-red-600 mt-1">{{ $message }}</p> @enderror
                        <div class="mt-4"><x-admin.publication-date-input /></div>
                        <x-admin.content-dates :record="$creatorRecord" />
                    </div>

                </div>
            </div>

        </div>
    </form>
</div>
