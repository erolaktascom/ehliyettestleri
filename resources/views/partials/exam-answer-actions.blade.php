answers: Object.fromEntries(Object.entries(config.initialAnswers || {}).filter(([, value]) => Boolean(value))),
resultHtml: '',
saveQueue: Promise.resolve(),
failedAnswers: {},
failedPosition: null,
pendingSaves: 0,
isFinishing: false,
isLeaving: false,
saveStatus: '',
confirmLeaveModal: false,
pendingExitUrl: null,

async requestWithTimeout(url, options) {
    const controller = new AbortController();
    let timeout;
    try {
        return await Promise.race([
            fetch(url, { ...options, signal: controller.signal }),
            new Promise((resolve, reject) => {
                timeout = setTimeout(() => {
                    controller.abort();
                    reject(new Error('Request timed out'));
                }, config.requestTimeoutMs ?? 15000);
            })
        ]);
    } finally {
        clearTimeout(timeout);
    }
},

refreshSaveStatus() {
    this.saveStatus = Object.keys(this.failedAnswers).length > 0
        ? 'Bazı cevaplar kaydedilemedi. Yeniden kaydetmeyi deneyin.'
        : this.failedPosition !== null
            ? 'Kaldığınız soru kaydedilemedi. Yeniden kaydetmeyi deneyin.'
            : '';
},

trapDialogFocus(event) {
    const controls = [...event.currentTarget.querySelectorAll('button:not([disabled]), a[href], input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex="0"]')]
        .filter(element => element.getClientRects().length > 0);
    if (!controls.length) { event.preventDefault(); return; }
    const first = controls[0];
    const last = controls[controls.length - 1];
    if (event.shiftKey && (event.target === first || !controls.includes(event.target))) {
        event.preventDefault(); last.focus();
    } else if (!event.shiftKey && (event.target === last || !controls.includes(event.target))) {
        event.preventDefault(); first.focus();
    }
},
requestLeave(url) {
    if (this.isLeaving || this.isFinishing) return;
    if (config.resumeAllowed) return this.leaveTest(url);
    this.pendingExitUrl = url;
    this.confirmLeaveModal = true;
},

confirmLeave() {
    if (!this.pendingExitUrl) return;
    this.confirmLeaveModal = false;
    return this.leaveTest(this.pendingExitUrl);
},

selectAnswer(questionId, option) {
    if (this.isFinishing || this.isLeaving) return this.saveQueue;
    if (config.gradeUrl && this.resultHtml) return;
    if (config.gradeUrl && config.remainingSeconds > 0) {
        this.updateRemainingTime();
        if (this.remainingSeconds === 0) return;
    }
    this.answers[questionId] = option;
    if (config.gradeUrl) return;
    this.pendingSaves++;
    this.refreshSaveStatus();

    // Keep requests in order so an older response cannot overwrite the latest choice.
    this.saveQueue = this.saveQueue.then(async () => {
        try {
            const response = await this.requestWithTimeout(config.saveAnswerUrl, {
                method: 'POST',
                keepalive: true,
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': config.csrfToken
                },
                body: JSON.stringify({ question_id: questionId, selected_option: option })
            });
            if (!response.ok) throw new Error('Answer was not saved');
            delete this.failedAnswers[questionId];
        } catch (error) {
            this.failedAnswers[questionId] = option;
        } finally {
            this.pendingSaves--;
            this.refreshSaveStatus();
        }
    });
    return this.saveQueue;
},

retryAnswers() {
    for (const [questionId, option] of Object.entries(this.failedAnswers)) {
        this.selectAnswer(questionId, option);
    }
    if (this.failedPosition !== null) this.savePosition(this.failedPosition);
    return this.saveQueue;
},

async leaveTest(url) {
    if (this.isLeaving || this.isFinishing) return;
    this.isLeaving = true;
    await this.saveQueue;
    if (this.isFinishing) return;
    if (Object.keys(this.failedAnswers).length > 0 || this.failedPosition !== null) {
        this.isLeaving = false;
        this.saveStatus = 'Kaydedilemeyen cevaplar var. Çıkmadan önce Yeniden Kaydet düğmesini kullanın.';
        return;
    }
    if (config.leaveUrl) {
        try {
            const response = await this.requestWithTimeout(config.leaveUrl, {
                method: 'POST', headers: { 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrfToken }
            });
            if (!response.ok) throw new Error('Could not leave');
        } catch (error) {
            this.isLeaving = false;
            this.saveStatus = 'Çıkış yapılamadı. Bağlantınızı kontrol edip Çıkış düğmesini yeniden deneyin.';
            return;
        }
    }
    window.location.assign(url);
},

async finishExam(form, timedOut = false) {
    if (this.resultHtml || this.isFinishing || (this.isLeaving && !timedOut)) return;
    if (config.gradeUrl) {
        this.isFinishing = true;
        this.confirmFinishModal = false;
        this.confirmLeaveModal = false;
        this.saveStatus = '';
        try {
            const body = JSON.stringify({question_ids: this.questionsList, answers: this.answers});
            const submitAnswers = () => this.requestWithTimeout(config.gradeUrl, {
                method: 'POST',
                headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrfToken},
                body,
            });
            let response = await submitAnswers();
            if (response.status === 419) {
                const refresh = await this.requestWithTimeout(config.sessionTokenUrl, {
                    headers: {Accept: 'application/json', 'X-Requested-With': 'XMLHttpRequest'},
                    cache: 'no-store',
                });
                if (!refresh.ok) throw new Error('Session refresh failed');
                const {token} = await refresh.json();
                if (typeof token !== 'string' || !token) throw new Error('Missing session token');
                config.csrfToken = token;
                response = await submitAnswers();
            }
            if (response.status === 409) {
                this.saveStatus = 'Testin soruları değişti. Yeniden başlamak için sayfayı yenile.';
                return;
            }
            if (!response.ok) throw new Error('Grading failed');
            const {html} = await response.json();
            if (!html) throw new Error('Missing result');
            this.resultHtml = html;
            clearInterval(this.timerInterval);
            window.scrollTo({top: 0});
        } catch (_) {
            this.saveStatus = 'Sonuç alınamadı. Cevapların bu sayfada duruyor.';
        } finally {
            this.isFinishing = false;
        }
        return;
    }
    this.isFinishing = true;
    await this.saveQueue;
    if (!timedOut && Object.keys(this.failedAnswers).length > 0) {
        this.isFinishing = false;
        this.confirmFinishModal = false;
        this.saveStatus = 'Kaydedilemeyen cevaplar var. Yeniden Kaydet düğmesini kullanın.';
        return;
    }
    form?.submit();
},

// Only members' topic tests resume on a later visit. Reload keeps the current run.
prepareAttempt() {
    if (config.gradeUrl) {
        window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
        return true;
    }
    const navigation = window.performance?.getEntriesByType('navigation')?.[0];
    if (!config.resumeAllowed && !config.freshStart && navigation?.type !== 'reload') {
        document.getElementById('restart-attempt-form')?.requestSubmit();
        return false;
    }
    window.addEventListener('pageshow', event => {
        if (event.persisted) {
            if (config.resumeAllowed) window.location.reload();
            else document.getElementById('restart-attempt-form')?.requestSubmit();
        }
    });
    return true;
},

savePosition(position) {
    if (config.gradeUrl) return;
    if (this.isFinishing || this.isLeaving) return this.saveQueue;
    this.saveQueue = this.saveQueue.then(async () => {
        try {
            const response = await this.requestWithTimeout(config.positionUrl, {
                method: 'POST', keepalive: true, headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': config.csrfToken },
                body: JSON.stringify({ position })
            });
            if (!response.ok) throw new Error('Position was not saved');
            this.failedPosition = null;
        } catch (error) {
            this.failedPosition = position;
        } finally {
            this.refreshSaveStatus();
        }
    });
    return this.saveQueue;
},
