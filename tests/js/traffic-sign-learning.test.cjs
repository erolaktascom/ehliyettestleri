const {test} = require('node:test');
const assert = require('node:assert/strict');
const fs = require('node:fs');
const path = require('node:path');
const source = fs.readFileSync(path.join(__dirname,'../../resources/views/partials/traffic-sign-learning.blade.php'),'utf8');
const signs = [1,2,3].map(id => ({id, name:'İşaret '+id, reviewUrl:'/review/'+id}));
function harness(config={}, fetch=async()=>({ok:true,json:async()=>({success:true})})) {
    const signLearner = new Function('fetch',source+';return signLearner;')(fetch);
    const runner = signLearner({isMember:true,signs,positionUrl:'/position',...config});
    runner.init();
    return {runner};
}
test('guest browsing moves only in memory and marking a sign is unavailable', async () => {
    const guest = () => harness({isMember:false}, () => assert.fail('unexpected request')).runner;
    const first = guest();
    await first.next();
    assert.equal(first.currentId, 2);
    await first.saveReview('known');
    assert.equal(first.knownCount, 0);
    assert.equal(guest().currentId, 1);
});
test('unlearned filter advances without skipping and completion has no stale card',async()=>{
    const {runner}=harness();await runner.setFilter('unlearned');
    await runner.saveReview('known');assert.equal(runner.currentId,2);
    await runner.saveReview('known');assert.equal(runner.currentId,3);
    await runner.saveReview('known');assert.equal(runner.currentId,null);assert.equal(runner.completed,true);assert.equal(runner.progressPercent,100);
    await runner.setFilter('all');assert.equal(runner.currentId,1);assert.equal(runner.signs.length,3);
});
test('review filter removes learned signs and can be repopulated',async()=>{
    const {runner}=harness({isMember:true,initialStatuses:{1:'review',2:'review'},initialFilter:'review',initialSignId:2});
    await runner.saveReview('known');assert.equal(runner.currentId,1);assert.equal(runner.reviewCount,1);
    await runner.saveReview('known');assert.equal(runner.currentId,null);assert.equal(runner.completed,false);
    await runner.setFilter('all');await runner.saveReview('review');assert.equal(runner.reviewCount,1);
});
test('pending save blocks duplicate clicks, filters and navigation',async()=>{
    let resolve;let calls=0;const {runner}=harness({isMember:true},()=>{calls++;return new Promise(r=>resolve=r);});
    const saving=runner.saveReview('known');await runner.saveReview('review');await runner.next();await runner.setFilter('review');
    assert.equal(calls,1);assert.equal(runner.currentId,1);assert.equal(runner.filter,'all');
    resolve({ok:true,json:async()=>({success:true})});await saving;
    assert.equal(runner.currentId,2);assert.equal(runner.knownCount,1);assert.equal(runner.busy,false);
});
test('failed HTTP request retains card and statuses; retry sends identical explicit state',async()=>{
    let ok=false;const payloads=[];const {runner}=harness({isMember:true},async(u,o)=>{payloads.push(JSON.parse(o.body));return {ok,json:async()=>({success:true})};});
    await runner.saveReview('known');assert.equal(runner.currentId,1);assert.equal(runner.knownCount,0);assert.ok(runner.error);
    ok=true;await runner.retry();assert.deepEqual(payloads[0],payloads[1]);assert.equal(runner.currentId,2);assert.equal(runner.error,'');
});
test('timed out navigation releases controls and retains previous position',async()=>{
    const {runner}=harness({isMember:true,requestTimeoutMs:5},()=>new Promise(()=>{}));
    await runner.next();assert.equal(runner.currentId,1);assert.equal(runner.busy,false);assert.ok(runner.error);
});
test('missing or unpublished resumed sign falls back and empty categories are usable',()=>{
    const a=harness({isMember:true,initialSignId:99,initialStatuses:{99:'known',1:'known'}}).runner;
    assert.equal(a.currentId,1);assert.equal(a.knownCount,1);
    const b=harness({signs:[]}).runner;assert.equal(b.currentSign,null);assert.equal(b.progressPercent,0);assert.equal(b.completed,false);
});
