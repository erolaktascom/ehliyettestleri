function signLearner(config) {
    return {
        signs: config.signs,
        statuses: {},
        currentId: null,
        filter: 'all',
        mapExpanded: false,
        busy: false,
        error: '',
        failedAction: null,
        brokenImages: {},

        init() {
            const state = config.isMember ? {
                statuses: config.initialStatuses, currentId: config.initialSignId, filter: config.initialFilter,
            } : {};
            if (!config.isMember && typeof window !== 'undefined') {
                window.addEventListener('pageshow', event => { if (event.persisted) window.location.reload(); });
            }
            for (const sign of this.signs) {
                if (['known', 'review'].includes(state.statuses?.[sign.id])) this.statuses[sign.id] = state.statuses[sign.id];
            }
            this.filter = ['all', 'unlearned', 'review'].includes(state.filter) ? state.filter : 'all';
            const ids = this.filteredSigns.map(sign => sign.id);
            this.currentId = ids.includes(Number(state.currentId)) ? Number(state.currentId) : (ids[0] ?? null);
        },

        listFor(statuses = this.statuses, filter = this.filter) {
            return this.signs.filter(sign => filter === 'all' || (filter === 'review' ? statuses[sign.id] === 'review' : statuses[sign.id] !== 'known'));
        },
        get filteredSigns() { return this.listFor(); },
        get currentSign() { return this.signs.find(sign => sign.id === this.currentId) || null; },
        get currentIndex() { return this.filteredSigns.findIndex(sign => sign.id === this.currentId); },
        get knownCount() { return this.signs.filter(sign => this.statuses[sign.id] === 'known').length; },
        get reviewCount() { return this.signs.filter(sign => this.statuses[sign.id] === 'review').length; },
        get progressPercent() { return this.signs.length ? Math.round(this.knownCount / this.signs.length * 100) : 0; },
        get completed() { return this.signs.length > 0 && this.knownCount === this.signs.length; },
        statusLabel(id) { return this.statuses[id] === 'known' ? 'Öğrenildi' : (this.statuses[id] === 'review' ? 'Tekrar edilecek' : 'Henüz işaretlenmedi'); },
        imageFor(sign) { return sign && !this.brokenImages[sign.id] ? sign.image : null; },

        async request(url, payload) {
            const controller = new AbortController();
            let timeout;
            try {
                return await Promise.race([
                    (async () => {
                        const response = await fetch(url, {
                            method: 'POST', signal: controller.signal,
                            headers: {'Content-Type': 'application/json', Accept: 'application/json', 'X-CSRF-TOKEN': config.csrfToken},
                            body: JSON.stringify(payload),
                        });
                        if (!response.ok) throw new Error('Request failed');
                        const data = await response.json();
                        if (data.success !== true) throw new Error('Invalid response');
                        return data;
                    })(),
                    new Promise((_, reject) => { timeout = setTimeout(() => { controller.abort(); reject(new Error('Timeout')); }, config.requestTimeoutMs ?? 15000); }),
                ]);
            } finally { clearTimeout(timeout); }
        },

        async moveTo(id, filter = this.filter) {
            if (this.busy || !['all', 'unlearned', 'review'].includes(filter)) return;
            const ids = this.listFor(this.statuses, filter).map(sign => sign.id);
            if ((id === null && ids.length) || (id !== null && !ids.includes(id))) return;
            this.busy = true; this.error = ''; this.failedAction = null;
            try {
                if (config.isMember) await this.request(config.positionUrl, {sign_id: id, filter});
                this.currentId = id; this.filter = filter; this.mapExpanded = false;
            } catch (_) {
                this.error = 'Konum kaydedilemedi. Bağlantını kontrol edip yeniden dene.';
                this.failedAction = {type: 'move', id, filter};
            } finally { this.busy = false; }
        },

        async selectSign(id) {
            await this.moveTo(id);
            if (!this.error && typeof window !== 'undefined' && window.matchMedia('(max-width: 1023px)').matches) {
                this.$nextTick(() => this.$refs.mapButton?.focus());
            }
        },
        setFilter(filter) {
            if (!config.isMember || this.busy) return;
            const ids = this.listFor(this.statuses, filter).map(sign => sign.id);
            return this.moveTo(ids.includes(this.currentId) ? this.currentId : (ids[0] ?? null), filter);
        },
        next() { const next = this.filteredSigns[this.currentIndex + 1]; if (next) return this.moveTo(next.id); },
        prev() { const prev = this.filteredSigns[this.currentIndex - 1]; if (prev) return this.moveTo(prev.id); },

        async saveReview(status) {
            if (!config.isMember || this.busy || !this.currentSign || !['known', 'review'].includes(status)) return;
            const sign = this.currentSign;
            const oldIndex = this.currentIndex;
            const newStatuses = {...this.statuses, [sign.id]: status};
            const futureIds = this.listFor(newStatuses).map(item => item.id);
            const retainedIndex = futureIds.indexOf(sign.id);
            const nextId = retainedIndex >= 0 ? (futureIds[retainedIndex + 1] ?? sign.id) : (futureIds[Math.min(oldIndex, futureIds.length - 1)] ?? null);
            this.busy = true; this.error = ''; this.failedAction = null;
            try {
                await this.request(sign.reviewUrl, {status, next_sign_id: nextId, filter: this.filter});
                this.statuses = newStatuses; this.currentId = nextId; this.mapExpanded = false;
            } catch (_) {
                this.error = 'Öğrenme durumu kaydedilemedi. Yeniden deneyebilirsin.';
                this.failedAction = {type: 'review', status, id: sign.id};
            } finally { this.busy = false; }
        },

        retry() {
            if (this.busy || !this.failedAction) return;
            const action = this.failedAction;
            if (action.type === 'move') return this.moveTo(action.id, action.filter);
            if (action.id === this.currentId) return this.saveReview(action.status);
        },
    };
}
