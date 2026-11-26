@props(['query' => ''])
<form method="GET" action="{{ route('search') }}" role="search">
    <label for="site-search" class="block text-xs font-bold text-slate-600 mb-2">Sitede ara</label>
    <div class="flex gap-2">
        <input id="site-search" name="q" type="search" value="{{ $query }}" required maxlength="100"
            placeholder="Test, ders veya yazı ara…"
            class="min-w-0 w-full rounded-xl border border-slate-300 bg-white px-3 py-3 text-sm focus:outline-none focus:ring-2 focus:ring-brand-500">
        <button class="site-button-primary shrink-0" type="submit">Ara</button>
    </div>
</form>
