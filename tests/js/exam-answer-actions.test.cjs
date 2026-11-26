const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');

const source = fs.readFileSync(path.join(__dirname, '../../resources/views/partials/exam-answer-actions.blade.php'), 'utf8');
const createRunner = (fetch, answers = {}) => new Function('config', 'fetch', `return ({${source}})`)(
    { saveAnswerUrl: '/answer', csrfToken: 'test', initialAnswers: answers }, fetch
);

test('member exams restart on a fresh visit, reload keeps answers, member topics resume', () => {
    let restarted = 0;
    global.document = { getElementById() { return { requestSubmit() { restarted++; } }; } };
    global.window = { performance: { getEntriesByType() { return [{ type: 'navigate' }]; } }, addEventListener() {} };
    const runner = config => new Function('config', `return ({${source}})`)(config);
    try {
        assert.equal(runner({ freshStart: true, resumeAllowed: false }).prepareAttempt(), true);
        assert.equal(restarted, 0);
        assert.equal(runner({ freshStart: false, resumeAllowed: false }).prepareAttempt(), false);
        assert.equal(restarted, 1);
        assert.equal(runner({ freshStart: false, resumeAllowed: true }).prepareAttempt(), true);
        window.performance.getEntriesByType = () => [{ type: 'reload' }];
        assert.equal(runner({ freshStart: false, resumeAllowed: false }).prepareAttempt(), true);
        assert.equal(restarted, 1);
    } finally { delete global.document; delete global.window; }
});

test('position saves stay ordered and exit waits for the final position and explicit leave request', async () => {
    const calls = [];
    let destination;
    global.window = { location: { assign(url) { destination = url; } } };
    const runner = new Function('config', 'fetch', `return ({${source}})`)(
        { positionUrl: '/position', leaveUrl: '/leave', csrfToken: 'csrf' },
        async (url, options) => { calls.push([url, options.body]); return { ok: true }; }
    );
    try {
        runner.savePosition(1);
        runner.savePosition(2);
        await runner.leaveTest('/list');
        assert.deepEqual(calls.map(([url]) => url), ['/position', '/position', '/leave']);
        assert.deepEqual(calls.slice(0,2).map(([,body]) => JSON.parse(body).position), [1,2]);
        assert.equal(destination, '/list');
    } finally { delete global.window; }
});

test('failed position prevents exit and is retried before leaving', async () => {
    let ok = false;
    let destination;
    global.window = { location: { assign(url) { destination = url; } } };
    const runner = new Function('config', 'fetch', `return ({${source}})`)(
        { positionUrl: '/position', leaveUrl: '/leave', csrfToken: 'csrf' }, async () => ({ ok })
    );
    try {
        await runner.savePosition(2);
        await runner.leaveTest('/list');
        assert.equal(destination, undefined);
        assert.equal(runner.failedPosition, 2);
        ok = true;
        await runner.retryAnswers();
        await runner.leaveTest('/list');
        assert.equal(runner.failedPosition, null);
        assert.equal(destination, '/list');
    } finally { delete global.window; }
});

test('leaving waits for pending saves and does not finish the attempt', async () => {
    let resolveSave;
    let destination;
    global.window = { location: { assign(url) { destination = url; } } };
    try {
        const runner = createRunner(() => new Promise(resolve => { resolveSave = resolve; }));
        runner.selectAnswer(1, 'A');
        const leaving = runner.leaveTest('/konu-testleri');
        await new Promise(resolve => setImmediate(resolve));
        assert.equal(destination, undefined);
        await runner.finishExam({ submit() { assert.fail('Exit must not finish'); } });
        resolveSave({ ok: true });
        await leaving;
        assert.equal(destination, '/konu-testleri');
        assert.equal(runner.isFinishing, false);
    } finally { delete global.window; }
});

test('failed saves keep the user in the test until retry succeeds', async () => {
    let ok = false;
    let destination;
    global.window = { location: { assign(url) { destination = url; } } };
    try {
        const runner = createRunner(async () => ({ ok }));
        await runner.selectAnswer(1, 'A');
        await runner.leaveTest('/tests');
        assert.equal(destination, undefined);
        assert.equal(runner.isLeaving, false);
        ok = true;
        await runner.retryAnswers();
        await runner.leaveTest('/tests');
        assert.equal(destination, '/tests');
    } finally { delete global.window; }
});

test('timeout finishing takes priority over an exit waiting for a save', async () => {
    let resolveSave;
    let submitted = false;
    global.window = { location: { assign() { assert.fail('Do not leave during timeout submission'); } } };
    try {
        const runner = createRunner(() => new Promise(resolve => { resolveSave = resolve; }));
        runner.selectAnswer(1, 'A');
        const leaving = runner.leaveTest('/tests');
        const finishing = runner.finishExam({ submit() { submitted = true; } }, true);
        await new Promise(resolve => setImmediate(resolve));
        resolveSave({ ok: true });
        await Promise.all([leaving, finishing]);
        assert.equal(submitted, true);
    } finally { delete global.window; }
});

test('both exam pages include valid JavaScript after the shared actions are inserted', () => {
    for (const page of ['exams', 'topic-tests']) {
        const template = fs.readFileSync(path.join(__dirname, `../../resources/views/pages/${page}/take.blade.php`), 'utf8');
        const script = template.match(/<script>([\s\S]*?)<\/script>/)[1]
            .replace("@include('partials.exam-answer-actions')", source)
            .replace(/@json\([^\n]*\)/g, '[]');
        assert.doesNotThrow(() => new vm.Script(script));
        assert.match(template, /<x-test-session-dialogs/);
        const dialogs = fs.readFileSync(path.join(__dirname, '../../resources/views/components/test-session-dialogs.blade.php'), 'utf8');
        assert.match(dialogs, /@submit\.prevent="finishExam\(\$event.target\)"/);
        assert.match(template, /@click="retryAnswers\(\)"/);
    }
});

test('placeholder answers are excluded from the answered count', () => {
    const runner = createRunner(() => {}, { 1: null, 2: '', 3: 'B' });
    assert.deepEqual(runner.answers, { 3: 'B' });
});

test('HTTP failures remain visible and prevent finishing until retry succeeds', async () => {
    let ok = false;
    const runner = createRunner(async () => ({ ok }));
    let submitted = false;
    const form = { submit() { submitted = true; } };
    await runner.selectAnswer(1, 'A');
    assert.deepEqual(runner.failedAnswers, { 1: 'A' });
    assert.doesNotMatch(runner.saveStatus, /✓/);
    await runner.finishExam(form);
    assert.equal(submitted, false);
    ok = true;
    await runner.retryAnswers();
    await runner.finishExam(form);
    assert.equal(submitted, true);
});

test('rapid answers stay ordered and finishing waits for the last save', async () => {
    const calls = [];
    const resolvers = [];
    const runner = createRunner((url, options) => {
        calls.push(JSON.parse(options.body).selected_option);
        return new Promise(resolve => resolvers.push(resolve));
    });
    runner.selectAnswer(1, 'A');
    runner.selectAnswer(1, 'B');
    let submitted = false;
    const finish = runner.finishExam({ submit() { submitted = true; } });
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(calls, ['A']);
    resolvers[0]({ ok: true });
    await new Promise(resolve => setImmediate(resolve));
    assert.deepEqual(calls, ['A', 'B']);
    assert.equal(submitted, false);
    resolvers[1]({ ok: true });
    await finish;
    assert.equal(submitted, true);
    assert.equal(runner.answers[1], 'B');
});

test('network errors do not break subsequent saves', async () => {
    let attempts = 0;
    const runner = createRunner(async () => {
        if (++attempts === 1) throw new Error('offline');
        return { ok: true };
    });
    runner.selectAnswer(1, 'A');
    await runner.selectAnswer(1, 'B');
    assert.deepEqual(runner.failedAnswers, {});
    assert.equal(runner.pendingSaves, 0);
});


test('successful saves are silent and successful position retry clears the error', async () => {
    let ok = false;
    const runner = createRunner(async () => ({ ok }));
    await runner.savePosition(2);
    assert.notEqual(runner.saveStatus, '');
    ok = true;
    await runner.retryAnswers();
    assert.equal(runner.failedPosition, null);
    assert.equal(runner.saveStatus, '');
    await runner.selectAnswer(1, 'A');
    assert.equal(runner.saveStatus, '');
});

test('hanging position requests time out and cannot indefinitely block automatic finish', async () => {
    let signal, submitted = false;
    const runner = new Function('config', 'fetch', `return ({${source}})`)(
        { requestTimeoutMs: 5, positionUrl: '/position' }, (url, options) => { signal = options.signal; return new Promise(() => {}); }
    );
    runner.savePosition(2);
    await runner.finishExam({ submit() { submitted = true; } }, true);
    assert.equal(signal.aborted, true);
    assert.equal(runner.failedPosition, 2);
    assert.equal(submitted, true);
});

test('hanging exit requests release the exit button and show a retryable error', async () => {
    const runner = new Function('config', 'fetch', `return ({${source}})`)(
        { requestTimeoutMs: 5, leaveUrl: '/leave' }, () => new Promise(() => {})
    );
    await runner.leaveTest('/list');
    assert.equal(runner.isLeaving, false);
    assert.match(runner.saveStatus, /Çıkış yapılamadı/);
});

test('exams and guest tests require confirmation; member topic tests exit directly', async () => {
    for (const resumeAllowed of [false, true]) {
        const runner = new Function('config', `return ({${source}})`)({ resumeAllowed });
        let left;
        runner.leaveTest = async url => { left = url; };
        await runner.requestLeave('/list');
        assert.equal(runner.confirmLeaveModal, !resumeAllowed);
        assert.equal(left, resumeAllowed ? '/list' : undefined);
        if (!resumeAllowed) {
            await runner.confirmLeave();
            assert.equal(left, '/list');
            assert.equal(runner.confirmLeaveModal, false);
        }
    }
});

test('dialog focus wraps in both directions without focusing disabled controls', () => {
    const runner = createRunner(async () => ({ ok: true }));
    let focused, prevented = 0;
    const first = { getClientRects: () => [1], focus() { focused = 'first'; } };
    const last = { getClientRects: () => [1], focus() { focused = 'last'; } };
    const hidden = { getClientRects: () => [], focus() { assert.fail('Hidden control focused'); } };
    const dialog = { querySelectorAll: () => [first, last, hidden] };
    runner.trapDialogFocus({ currentTarget: dialog, target: last, shiftKey: false, preventDefault() { prevented++; } });
    assert.equal(focused, 'first');
    runner.trapDialogFocus({ currentTarget: dialog, target: first, shiftKey: true, preventDefault() { prevented++; } });
    assert.equal(focused, 'last');
    assert.equal(prevented, 2);
});

test('guest answers and navigation stay on the page and only finishing requests a result', async () => {
    const requests = [];
    const make = () => new Function('config', 'fetch', `return ({${source}})`)(
        {gradeUrl: '/grade', csrfToken: 'test'}, async (url, options) => {
            requests.push([url, JSON.parse(options.body)]);
            return {ok: true, json: async () => ({html: '<p>Sonuç</p>'})};
        });
    global.window = {scrollTo() {}};
    try {
        const guest = make();
        guest.questionsList = [10, 20];
        guest.selectAnswer(10, 'A');
        guest.savePosition(1);
        assert.deepEqual(requests, []);
        assert.deepEqual(make().answers, {});
        await guest.finishExam();
        assert.deepEqual(requests, [['/grade', {question_ids: [10, 20], answers: {10:'A'}}]]);
        assert.equal(guest.resultHtml, '<p>Sonuç</p>');
        assert.equal(make().resultHtml, '');
    } finally { delete global.window; }
});

test('guest grading refreshes an expired token once and resubmits the same answers', async () => {
    for (const retryStatus of [200, 419]) {
        const requests = [];
        const responses = [
            {status: 419},
            {ok: true, json: async () => ({token: 'fresh'})},
            {status: retryStatus, ok: retryStatus === 200, json: async () => ({html: 'Sonuç'})},
        ];
        const config = {gradeUrl: '/grade', sessionTokenUrl: '/session', csrfToken: 'expired'};
        const fetch = async (url, options) => {
            requests.push({url, options});
            return responses.shift();
        };
        const guest = new Function('config', 'fetch', 'window', `return ({${source}})`)(config, fetch, {scrollTo() {}});
        guest.questionsList = [10, 20];
        guest.selectAnswer(20, 'B');
        await guest.finishExam(null, true);

        assert.deepEqual(requests.map(request => request.url), ['/grade', '/session', '/grade']);
        assert.equal(requests[0].options.headers['X-CSRF-TOKEN'], 'expired');
        assert.equal(requests[2].options.headers['X-CSRF-TOKEN'], 'fresh');
        assert.equal(requests[0].options.body, requests[2].options.body);
        assert.deepEqual(guest.answers, {20: 'B'});
        assert.equal(guest.resultHtml, retryStatus === 200 ? 'Sonuç' : '');
        assert.equal(Boolean(guest.saveStatus), retryStatus !== 200);
        assert.equal(guest.isFinishing, false);
    }
});
