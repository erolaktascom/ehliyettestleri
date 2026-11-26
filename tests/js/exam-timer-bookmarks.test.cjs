const { test } = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const vm = require('node:vm');
const root = path.join(__dirname, '../..');
const actions = fs.readFileSync(path.join(root,'resources/views/partials/exam-answer-actions.blade.php'),'utf8');
const bookmarks = fs.readFileSync(path.join(root,'resources/views/partials/question-bookmark-actions.blade.php'),'utf8');
const makeBookmarks = (fetch, config={}) => new Function('config','fetch',`return ({${bookmarks}})`)({toggleSaveUrl:'/soru',csrfToken:'test',...config},fetch);

test('bookmark request sets explicit state and ignores duplicate clicks while pending', async () => {
    let resolve, calls=0, body;
    const runner=makeBookmarks((url,options)=>{calls++;body=JSON.parse(options.body);return new Promise(r=>resolve=r);});
    const pending=runner.toggleBookmark(5);
    await runner.toggleBookmark(5);
    assert.equal(calls,1); assert.deepEqual(body,{saved:true});
    resolve({ok:true,json:async()=>({success:true,saved:true})}); await pending;
    assert.deepEqual(runner.savedQuestionIds,[5]);
    const remove=runner.toggleBookmark(5); assert.deepEqual(body,{saved:false});
    resolve({ok:true,json:async()=>({success:true,saved:false})}); await remove;
    assert.deepEqual(runner.savedQuestionIds,[]);
});

test('failed bookmarks preserve state and retry the same desired state', async () => {
    let ok=false; const states=[];
    const runner=makeBookmarks(async(url,options)=>{states.push(JSON.parse(options.body).saved);return {ok,json:async()=>({success:true,saved:true})};});
    await runner.toggleBookmark(8); assert.deepEqual(runner.savedQuestionIds,[]); assert.ok(runner.bookmarkError);
    ok=true; await runner.toggleBookmark(8); assert.deepEqual(states,[true,true]); assert.deepEqual(runner.savedQuestionIds,[8]); assert.equal(runner.bookmarkError,'');
});

test('stalled bookmark request releases pending state after timeout',async()=>{
    const runner=makeBookmarks(()=>new Promise(()=>{}),{requestTimeoutMs:5});
    await runner.toggleBookmark(3); assert.equal(runner.bookmarkPending[3],undefined); assert.ok(runner.bookmarkError);
});

function timerHarness() {
    let clock=500000, tick, visible, removed=false, cleared=0, finishes=0;
    const source=fs.readFileSync(path.join(root,'resources/views/pages/exams/take.blade.php'),'utf8').match(/<script>([\s\S]*?)<\/script>/)[1]
        .replace("@include('partials.exam-answer-actions')",actions).replace(/@json\([^\n]*\)/g,'[]');
    const document={visibilityState:'visible',addEventListener(name,fn){visible=fn;},removeEventListener(){removed=true;},getElementById(){return {};} };
    const context={Date:{now:()=>clock},questionBookmarks:()=>({}),setInterval(fn){tick=fn;return 1;},clearInterval(){cleared++;},document};
    vm.createContext(context);vm.runInContext(source,context);
    const runner=context.examRunner({serverNow:1000000,expiresAt:1600000,remainingSeconds:600});
    runner.finishExam=()=>{runner.isFinishing=true;finishes++;}; runner.startTimer();
    return {runner,document,advance(ms){clock+=ms;},tick(){tick();},visible(){visible();},stats:()=>({removed,cleared,finishes})};
}

test('deadline timer accounts for delayed ticks and client/server clock difference',()=>{
    const h=timerHarness(); assert.equal(h.runner.remainingSeconds,600);
    h.advance(120000); h.tick(); assert.equal(h.runner.remainingSeconds,480);
    h.advance(1500); h.tick(); assert.equal(h.runner.remainingSeconds,479);
});

test('returning to a visible tab finishes an expired exam once and cleans listeners',()=>{
    const h=timerHarness(); h.document.visibilityState='hidden'; h.advance(700000);
    h.visible(); assert.equal(h.stats().finishes,0);
    h.document.visibilityState='visible';h.visible();assert.equal(h.runner.remainingSeconds,0);assert.equal(h.stats().finishes,1);
    h.visible();assert.equal(h.stats().finishes,1);
    h.runner.destroy();assert.equal(h.stats().removed,true);assert.ok(h.stats().cleared);
});
