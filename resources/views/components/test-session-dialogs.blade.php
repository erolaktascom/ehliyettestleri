@props(['attempt', 'isExam' => false])
<dialog @keydown.tab="trapDialogFocus($event)" class="test-confirm-dialog site-card p-6 text-center" aria-labelledby="finish-confirm-title" aria-describedby="finish-confirm-description"
        x-init="$watch('confirmFinishModal', value => { if (value && !$el.open) $el.showModal(); else if (!value && $el.open) $el.close(); })"
        @cancel.prevent="if (!isFinishing) confirmFinishModal = false"
        @close="confirmFinishModal = false"
        @click="if ($event.target === $el && !isFinishing) confirmFinishModal = false">
    <h2 id="finish-confirm-title" class="text-xl font-extrabold text-ink">{{ $isExam ? 'Sınavı' : 'Testi' }} bitirmek istiyor musunuz?</h2>
    <p id="finish-confirm-description" class="mt-2 text-sm text-muted">
        Toplam <strong x-text="totalQuestions"></strong> sorudan <strong x-text="answeredCount"></strong> tanesini cevapladınız.
        <span x-show="totalQuestions - answeredCount > 0" class="block mt-1 text-red-600 font-semibold"><span x-text="totalQuestions - answeredCount"></span> soru boş bırakıldı.</span>
    </p>
    <form method="POST" @if($attempt) action="{{ route('exams.finish', $attempt) }}" @endif @submit.prevent="finishExam($event.target)" class="mt-6 flex gap-3">
        @csrf
        <button autofocus type="button" @click="confirmFinishModal = false" :disabled="isFinishing" class="site-button-secondary flex-1 py-2.5"><x-action-label icon="play">Devam Et</x-action-label></button>
        <button type="submit" :disabled="isFinishing" class="site-button-primary flex-1 py-2.5"><x-action-label icon="flag">Bitir</x-action-label></button>
    </form>
    <p x-cloak x-show="isFinishing" role="status" class="mt-3 text-sm text-muted">Sınav tamamlanıyor…</p>
</dialog>

<dialog @keydown.tab="trapDialogFocus($event)" class="test-confirm-dialog site-card p-6 text-center" aria-labelledby="leave-confirm-title" aria-describedby="leave-confirm-description"
        x-init="$watch('confirmLeaveModal', value => { if (value && !$el.open) $el.showModal(); else if (!value && $el.open) $el.close(); })"
        @cancel.prevent="confirmLeaveModal = false" @close="confirmLeaveModal = false"
        @click="if ($event.target === $el) confirmLeaveModal = false">
    <h2 id="leave-confirm-title" class="text-xl font-extrabold text-ink">Çıkmak istiyor musunuz?</h2>
    <p id="leave-confirm-description" class="mt-2 text-sm text-muted">
        @if(!$attempt?->user_id)
            Çıkarsanız bu çözümdeki cevaplarınız silinir. Tekrar açtığınızda baştan başlarsınız.
        @else
            Denemeden çıkarsanız bu oturum sona erer ve sonuç oluşturulmaz. Tekrar başladığınızda yeni bir deneme açılır.
        @endif
    </p>
    <div class="mt-6 flex gap-3">
        <button autofocus type="button" @click="confirmLeaveModal = false" class="site-button-secondary flex-1 py-2.5">Vazgeç</button>
        <button type="button" @click="confirmLeave()" class="site-button-primary flex-1 py-2.5">Çıkış Yap</button>
    </div>
</dialog>
@once
    @push('styles')
        <style>
            dialog.test-confirm-dialog { margin: auto; width: calc(100% - 32px); max-width: 448px; max-height: calc(100dvh - 32px); overflow-y: auto; color: #0f172a; }
            dialog.test-confirm-dialog:not([open]) { display: none; }
            dialog.test-confirm-dialog::backdrop { background: rgb(15 23 42 / .6); backdrop-filter: blur(4px); }
            dialog.test-confirm-dialog button:focus-visible { outline: 3px solid #92a6fb; outline-offset: 3px; }
        </style>
    @endpush
@endonce
