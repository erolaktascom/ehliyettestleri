<section class="mt-6 rounded-2xl border border-slate-200 bg-white p-6 shadow-sm space-y-5" x-data="{ dragging: null }">
    <div>
        <h2 class="text-lg font-bold text-slate-900">Testin Soruları <span class="text-sm text-blue-600">({{ count($questionIds) }} soru)</span></h2>
        <p class="mt-1 text-sm text-slate-500">Havuzdan soru ekleyin. Sıralamayı sürükleyerek veya oklarla değiştirin; değişiklikler testi kaydettiğinizde uygulanır.</p>
        <p class="mt-1 text-xs text-slate-500">Taslak sorular testte tutulabilir. Yeni sınav oturumlarında yalnızca yayındaki sorular gösterilir.</p>
    </div>
    @error('questionIds') <p role="alert" class="rounded-xl bg-red-50 p-3 text-sm text-red-700">{{ $message }}</p> @enderror
    @error('questionIds.*') <p role="alert" class="text-sm text-red-700">{{ $message }}</p> @enderror
    <ol class="space-y-2 max-h-[32rem] overflow-y-auto pr-1">
        @forelse($selectedQuestions as $question)
            <li wire:key="selected-question-{{ $question->id }}" draggable="true"
                @dragstart.stop="dragging = {{ $question->id }}; $event.dataTransfer.effectAllowed = 'move'; $event.dataTransfer.setData('text/plain', '{{ $question->id }}')"
                @dragend="dragging = null" @dragover.prevent
                @drop.prevent.stop="if (dragging !== null) $wire.moveQuestion(dragging, {{ $question->id }}); dragging = null"
                class="flex items-center gap-3 rounded-xl border border-slate-200 bg-slate-50 p-3 cursor-grab">
                <span class="font-bold text-blue-700 w-6 shrink-0">{{ $loop->iteration }}.</span>
                <div class="flex-1 min-w-0"><p class="text-sm font-semibold text-slate-800">{{ \Illuminate\Support\Str::limit(strip_tags($question->question_text), 180) }}</p>
                    <p class="mt-1 text-xs text-slate-500">#{{ $question->id }} · {{ $question->topicCategory?->name ?? 'Genel' }} · {{ $question->status }}</p></div>
                <div class="flex shrink-0 items-center gap-1">
                    <button type="button" wire:click="moveQuestionBy({{ $question->id }}, -1)" @disabled($loop->first) aria-label="Soru {{ $question->id }} yukarı" class="rounded-lg border bg-white px-2 py-1 disabled:opacity-30">↑</button>
                    <button type="button" wire:click="moveQuestionBy({{ $question->id }}, 1)" @disabled($loop->last) aria-label="Soru {{ $question->id }} aşağı" class="rounded-lg border bg-white px-2 py-1 disabled:opacity-30">↓</button>
                    <button type="button" wire:click="removeQuestion({{ $question->id }})" class="rounded-lg px-2 py-1 text-xs font-semibold text-red-600 hover:bg-red-50">Çıkar</button>
                </div>
            </li>
        @empty
            <li class="rounded-xl border border-dashed border-slate-300 p-5 text-sm text-slate-500">Henüz soru seçilmedi. Aşağıdaki havuzdan ekleyebilirsiniz.</li>
        @endforelse
    </ol>
    <div class="border-t pt-5 space-y-3">
        <h3 class="font-bold text-slate-800">Soru Havuzu</h3>
        <div class="flex flex-wrap gap-3">
            <input type="search" wire:model.live.debounce.300ms="questionSearch" placeholder="Soru metni veya numarası ara..." aria-label="Havuzda soru ara" class="flex-1 min-w-48 rounded-xl border border-slate-200 px-3 py-2 text-sm">
            @if(!$fixedQuestionCategory)
                <select wire:model.live="questionCategoryFilter" aria-label="Havuz kategorisi" class="rounded-xl border border-slate-200 px-3 py-2 text-sm">
                    <option value="">Tüm kategoriler</option>
                    @foreach($questionCategories as $category)<option value="{{ $category->id }}">{{ $category->name }}</option>@endforeach
                </select>
            @else
                <span class="self-center text-xs text-slate-500">Testin kategorisindeki sorular gösteriliyor.</span>
            @endif
        </div>
        <div class="max-h-96 overflow-y-auto space-y-2">
            @forelse($availableQuestions as $question)
                <div wire:key="available-question-{{ $question->id }}" class="flex items-center gap-3 rounded-xl border border-slate-200 p-3">
                    <div class="flex-1 min-w-0"><p class="text-sm text-slate-800">{{ \Illuminate\Support\Str::limit(strip_tags($question->question_text), 180) }}</p><p class="mt-1 text-xs text-slate-500">#{{ $question->id }} · {{ $question->topicCategory?->name ?? 'Genel' }} · {{ $question->status }}</p></div>
                    <button type="button" wire:click="addQuestion({{ $question->id }})" class="shrink-0 rounded-lg bg-blue-50 px-3 py-2 text-sm font-bold text-blue-700 hover:bg-blue-100">Ekle</button>
                </div>
            @empty
                <p class="py-3 text-sm text-slate-500">Bu filtreye uygun, eklenmemiş soru bulunamadı.</p>
            @endforelse
        </div>
        @if($availableCount > $availableQuestions->count() && $questionLimit < 200)
            <button type="button" wire:click="moreQuestions" class="text-sm font-semibold text-blue-600">Daha fazla göster</button>
        @endif
    </div>
</section>
