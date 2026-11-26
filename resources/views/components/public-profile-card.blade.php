@props(['profile', 'kind', 'postCount' => null])
<section class="site-card overflow-hidden">
    <div class="h-24 sm:h-32 bg-gradient-to-r from-brand-900 via-brand-700 to-emerald-600" aria-hidden="true"></div>
    <div class="px-6 pb-7 sm:px-8 sm:pb-9">
        <div class="flex flex-wrap items-end justify-between gap-4 -mt-10 relative">
            @if($profile['avatar'])
                <img src="{{ $profile['avatar'] }}" alt="{{ $profile['name'] }} profil fotoğrafı" width="96" height="96" class="size-24 rounded-2xl object-cover bg-white border-4 border-white shadow-sm">
            @else
                <span class="grid size-24 shrink-0 place-items-center rounded-2xl bg-brand-50 border-4 border-white text-brand-900 text-3xl font-extrabold shadow-sm">{{ $profile['initials'] }}</span>
            @endif
            <span class="px-3 py-1.5 rounded-full border border-brand-100 bg-brand-50 text-brand-800 text-xs font-bold">{{ $kind }}</span>
        </div>
        <h1 class="mt-5 text-2xl sm:text-3xl font-extrabold tracking-tight text-ink break-words">{{ $profile['name'] }}</h1>
        <div class="mt-3 flex flex-wrap gap-x-5 gap-y-2 text-xs text-muted">
            @if($profile['joined']) <span>{{ $profile['joined']->locale('tr')->translatedFormat('F Y') }} tarihinden beri üye</span> @endif
            @if($postCount !== null) <span>{{ $postCount }} yayımlanmış yazı</span> @endif
        </div>
        @if($profile['biography'])
            <div class="mt-6 pt-5 border-t border-slate-100">
                <h2 class="text-sm font-bold text-ink mb-2">Hakkında</h2>
                <p class="text-sm leading-7 text-muted whitespace-pre-line break-words max-w-3xl">{{ $profile['biography'] }}</p>
            </div>
        @endif
    </div>
</section>