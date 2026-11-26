@once
@push('styles')
<style>
    .study-catalog { padding: 20px 0 56px; }
    .study-catalog .catalog-grid { display: grid; grid-template-columns: repeat(2,minmax(0,1fr)); gap: 16px; margin-top: 28px; }
    .study-catalog .catalog-tile { background: #fff; border: 1px solid #dfe6f1; border-radius: 18px; box-shadow: 0 2px 4px #17255404; overflow: hidden; display: flex; flex-direction: column; transition: border-color .18s,box-shadow .18s; }
    .study-catalog .catalog-tile:hover { border-color: #9caaf5; box-shadow: 0 6px 22px #2339d20c; }
    .study-catalog .catalog-tile-main { display: flex; align-items: center; gap: 18px; padding: 24px; flex: 1; min-height: 132px; }
    .study-catalog .catalog-number { width: 58px; height: 68px; flex-shrink: 0; border: 1px solid #e0e6ff; border-radius: 13px; background: #f0f3ff; color: #2b3cc8; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 2px; }
    .study-catalog .catalog-number small { font-size: 8px; font-weight: 800; letter-spacing: 1.4px; }
    .study-catalog .catalog-number strong { font-size: 27px; line-height: 1.2; letter-spacing: -1px; font-weight: 800; }
    .study-catalog .catalog-copy { flex: 1; min-width: 0; }
    .study-catalog .catalog-copy h3 { color: #14213d; font-size: 19px; font-weight: 800; line-height: 1.4; letter-spacing: -.4px; margin: 0 0 9px; }
    .study-catalog .catalog-status { display: inline-flex; align-items: center; gap: 6px; font-size: 11px; font-weight: 600; color: #64748b; }
    .study-catalog .catalog-status svg { width: 13px; height: 13px; flex-shrink: 0; }
    .study-catalog .catalog-status-dot { width: 6px; height: 6px; border-radius: 50%; background: #a5b4cf; }
    .study-catalog .catalog-tile .study-card-arrow { width: 44px; height: 44px; border-radius: 50%; border: 1px solid #dfe5fd; background: #eff3ff; color: #2b3cc8; }
    .study-catalog .catalog-tile .study-card-controls { gap: 8px; }
    .study-catalog .catalog-tile .study-card-repeat-button { border-radius: 50%; background: #fff; }
    .study-catalog .catalog-tile:hover .study-card-arrow { background: #293bdb; border-color: #293bdb; color: #fff; }
    .study-catalog .catalog-result { display: flex; align-items: center; justify-content: space-between; gap: 12px; padding: 12px 24px; border-top: 1px solid #edf0f6; background: #fafbfe; }
    .study-catalog .catalog-result-counts { display: flex; flex-wrap: wrap; gap: 12px; font-size: 11px; color: #64748b; }
    .study-catalog .catalog-result-counts b { font-weight: 700; color: #334155; }
    .study-catalog .catalog-score { white-space: nowrap; font-size: 11px; color: #64748b; }
    .study-catalog .catalog-score strong { font-size: 17px; font-weight: 800; color: #263759; }
    .study-catalog .catalog-passed .catalog-status { color: #15803d; }
    .study-catalog .catalog-failed .catalog-status { color: #be3652; }
    .study-catalog .catalog-locked .catalog-status { color: #a16207; }
    .study-catalog .catalog-locked .catalog-number { color: #94702e; border-color: #f0e5cd; background: #faf7ef; }
    .study-catalog .catalog-locked .study-card-arrow { color: #a16207; background: #faf7ef; border-color: #f0e5cd; }
    .study-catalog .catalog-locked:hover { border-color: #d6bc85; }
    .study-catalog .catalog-locked:hover .study-card-arrow { background: #a16207; color: #fff; border-color: #a16207; }
    .study-catalog .catalog-empty { grid-column: 1/-1; padding: 48px 24px; text-align: center; background: #fff; border: 1px dashed #cbd5e1; border-radius: 18px; color: #64748b; font-size: 14px; }
    @media(max-width:767px) { .study-catalog .catalog-grid { grid-template-columns: minmax(0,1fr); gap: 12px; margin-top: 20px; } }
    @media(max-width:479px) {
        .study-catalog { padding: 16px 0 36px; }
        .study-catalog .catalog-tile-main { padding: 18px 16px; gap: 12px; min-height: 110px; }
        .study-catalog .catalog-number { width: 46px; height: 58px; border-radius: 11px; }
        .study-catalog .catalog-number strong { font-size: 23px; }
        .study-catalog .catalog-number small { font-size: 7px; }
        .study-catalog .catalog-copy h3 { font-size: 16px; margin-bottom: 6px; }
        .study-catalog .catalog-tile .study-card-controls { gap: 4px; }
        .study-catalog .catalog-result { padding: 10px 16px; }
        .study-catalog .catalog-result-counts { gap: 9px; }
    }
    @media(prefers-reduced-motion:reduce) { .study-catalog .catalog-tile { transition: none; } }
    .study-catalog .catalog-active .catalog-status { color: #a16207; }
    .study-catalog .catalog-number svg { width: 25px; height: 25px; }
    .study-catalog .catalog-entry-arrow { display:grid; place-items:center; width:44px; height:44px; border-radius:50%; border:1px solid #dfe5fd; background:#eff3ff; color:#2b3cc8; flex-shrink:0; }
    .study-catalog .catalog-entry-arrow svg { width:20px; height:20px; }
    .study-catalog a.catalog-tile:hover .catalog-entry-arrow { background:#293bdb; color:white; }
    .study-catalog a.catalog-tile:focus-visible { outline:3px solid #516bf0; outline-offset:3px; }
    .study-catalog .catalog-copy h3:last-child { margin-bottom:0; }
    .study-catalog .catalog-progress-footer { border-top:1px solid #edf0f6; background:#fafbfe; padding:14px 24px; font-size:11px; color:#64748b; }
    .study-catalog .catalog-progress-meta { display:flex; justify-content:space-between; gap:12px; }
    .study-catalog .catalog-progress-meta strong { color:#2b3cc8; white-space:nowrap; }
    .study-catalog .catalog-progress-track { height:4px; border-radius:8px; background:#e7ecf7; margin-top:9px; overflow:hidden; }
    .study-catalog .catalog-progress-track span { display:block; height:100%; background:#516bf0; border-radius:inherit; }
</style>
@endpush
@endonce