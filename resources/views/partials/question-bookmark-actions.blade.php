savedQuestionIds: (config.savedQuestionIds || []).map(Number),
bookmarkPending: {},
bookmarkError: '',

async toggleBookmark(questionId) {
    questionId = Number(questionId);
    if (!questionId || this.bookmarkPending[questionId]) return;
    this.bookmarkPending[questionId] = true;
    this.bookmarkError = '';
    const saved = !this.savedQuestionIds.includes(questionId);
    const controller = new AbortController();
    let timeout;
    try {
        const response = await Promise.race([
            fetch(`${config.toggleSaveUrl}/${questionId}/kaydet`, {
                method: 'POST',
                signal: controller.signal,
                headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrfToken },
                body: JSON.stringify({ saved })
            }),
            new Promise((resolve, reject) => {
                timeout = setTimeout(() => { controller.abort(); reject(new Error('Timed out')); }, config.requestTimeoutMs ?? 15000);
            })
        ]);
        if (!response.ok) throw new Error('Bookmark request failed');
        const data = await response.json();
        if (!data.success || typeof data.saved !== 'boolean') throw new Error('Invalid bookmark response');
        this.savedQuestionIds = data.saved
            ? [...new Set([...this.savedQuestionIds, questionId])]
            : this.savedQuestionIds.filter(id => id !== questionId);
    } catch (error) {
        this.bookmarkError = 'Soru kaydı güncellenemedi. Bağlantınızı kontrol edip yeniden deneyin.';
    } finally {
        clearTimeout(timeout);
        delete this.bookmarkPending[questionId];
    }
},
