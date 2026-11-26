@props(['questionExpression'])
@auth
    <button type="button" @click="toggleBookmark({{ $questionExpression }})"
            :disabled="bookmarkPending[{{ $questionExpression }}]"
            :aria-pressed="savedQuestionIds.includes(Number({{ $questionExpression }}))"
            :aria-label="(savedQuestionIds.includes(Number({{ $questionExpression }})) ? 'Kaydedilenlerden Çıkar' : 'Soruyu Kaydet') + ' — Soru ' + ({{ $questionExpression }})"
            :title="savedQuestionIds.includes(Number({{ $questionExpression }})) ? 'Kaydedilenlerden Çıkar' : 'Soruyu Kaydet'"
            class="question-bookmark"
            :class="{ 'is-saved': savedQuestionIds.includes(Number({{ $questionExpression }})) }">
        <x-student-icon name="bookmark" />
        <span class="hidden sm:inline" x-text="savedQuestionIds.includes(Number({{ $questionExpression }})) ? 'Kaldır' : 'Kaydet'"></span>
    </button>
@endauth
<x-question-bookmark-scripts />
