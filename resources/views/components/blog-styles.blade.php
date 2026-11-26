@once
@push('styles')
<style>
    .blog-catalog { padding: 20px 0 56px; }
    .blog-catalog-heading { padding: 40px 32px; text-align: center; }
    .blog-catalog-heading h1 { margin: 0; color: #0f172a; font-size: clamp(1.75rem, 4vw, 2.5rem); font-weight: 800; line-height: 1.2; letter-spacing: -.035em; }
    .blog-catalog-heading p { max-width: 64ch; margin: 14px auto 0; color: #64748b; font-size: 1rem; line-height: 1.75; }
    .blog-filters { display: flex; flex-wrap: wrap; gap: 8px; margin: 28px 0; }
    .blog-filter { display: inline-flex; align-items: center; gap: 8px; min-height: 44px; padding: 10px 18px; border: 1px solid #dfe6f1; border-radius: 999px; background: #fff; color: #475569; font-size: .8125rem; font-weight: 700; }
    .blog-filter:hover { border-color: #b5c1fc; color: #263fe5; }
    .blog-filter[aria-current="page"] { color: #fff; background: #293be7; border-color: #293be7; }
    .blog-filter-count { opacity: .7; font-size: .75rem; }
    .blog-post-grid { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 24px; }
    .blog-post-card { position: relative; display: flex; flex-direction: column; min-width: 0; overflow: hidden; border: 1px solid #dfe6f1; border-radius: 18px; background: #fff; box-shadow: 0 2px 4px #0f172a04; transition: border-color .18s, box-shadow .18s; }
    .blog-post-card:hover { border-color: #b5c1fc; box-shadow: 0 8px 24px #263fe512; }
    .blog-post-cover { display: grid; place-items: center; aspect-ratio: 16 / 9; background: linear-gradient(135deg, #eef2ff, #dfe6ff); border-bottom: 1px solid #e4e9f7; color: #a5b4fc; font-size: 3.5rem; font-weight: 800; }
    .blog-post-cover img { width: 100%; height: 100%; aspect-ratio: 16 / 9; object-fit: cover; }
    .blog-post-body { flex: 1; padding: 24px 24px 20px; }
    .blog-post-category { display: block; margin-bottom: 10px; color: #059669; font-size: .6875rem; font-weight: 800; letter-spacing: .06em; text-transform: uppercase; overflow-wrap: anywhere; }
    .blog-post-title { color: #0f172a; font-size: 1.125rem; line-height: 1.5; font-weight: 800; letter-spacing: -.015em; overflow-wrap: anywhere; }
    .blog-post-card:hover .blog-post-title { color: #293be7; }
    .blog-post-link::after { position: absolute; inset: 0; content: ''; z-index: 1; }
    .blog-post-card:focus-within { outline: 3px solid #a5b4fc; outline-offset: 3px; }
    .blog-post-summary { margin-top: 12px; font-size: .875rem; line-height: 1.8; color: #64748b; display: -webkit-box; -webkit-line-clamp: 3; -webkit-box-orient: vertical; overflow: hidden; overflow-wrap: anywhere; }
    .blog-post-footer { display: flex; align-items: center; gap: 12px; border-top: 1px solid #edf0f6; margin: 0 24px; padding: 18px 0; }
    .blog-post-author { flex: 1; min-width: 0; display: grid; gap: 4px; }
    .blog-post-author.with-avatar { display: flex; align-items: center; gap: 8px; }
    .blog-author-avatar { flex: none; display: grid; place-items: center; width: 32px; height: 32px; border-radius: 50%; object-fit: cover; background: #eef2ff; color: #293be7; font-size: .75rem; font-weight: 700; }
    .blog-post-author strong { color: #475569; font-size: .75rem; font-weight: 600; overflow-wrap: anywhere; }
    .blog-post-author time { color: #64748b; font-size: .6875rem; }
    .blog-post-read { display: inline-flex; align-items: center; gap: 7px; flex-shrink: 0; color: #293be7; font-size: .75rem; font-weight: 700; }
    .blog-post-read svg { width: 16px; height: 16px; }
    .blog-post-empty { grid-column: 1 / -1; padding: 40px 24px; text-align: center; color: #64748b; }
    .blog-filter:focus-visible { outline: 3px solid #a5b4fc; outline-offset: 3px; }
    @media (max-width: 1023px) { .blog-post-grid { grid-template-columns: repeat(2, minmax(0, 1fr)); } }
    @media (max-width: 639px) {
        .blog-catalog { padding: 16px 0 32px; }
        .blog-catalog-heading { padding: 28px 20px; }
        .blog-catalog-heading p { font-size: .9375rem; }
        .blog-post-grid { grid-template-columns: minmax(0, 1fr); gap: 20px; }
        .blog-post-body { padding: 20px; }
        .blog-post-footer { margin-inline: 20px; }
    }
</style>
@endpush
@endonce
